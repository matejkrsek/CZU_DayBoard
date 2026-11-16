<?php

declare(strict_types=1);

namespace DayBoard\Controllers;

use DayBoard\Core\Auth;
use DayBoard\Core\HttpError;
use DayBoard\Models\Household;
use DayBoard\Models\Users;
use DayBoard\Services\GeocodingService;
use DayBoard\Services\NewsService;
use DayBoard\Services\RadioService;
use DayBoard\Widgets\WidgetRegistry;

/** Správa domácnosti – dostupná pouze správci. */
final class HouseholdController extends Controller
{
    private const PERMISSION = 'household.manage';

    public static function show(): void
    {
        $user = self::user(self::PERMISSION);
        self::render($user, [], []);
    }

    /** Uloží role členů, povolené widgety a sdílená nastavení. */
    public static function save(): void
    {
        $user = self::user(self::PERMISSION);
        $errors = [];

        // Role členů
        $members = Users::all();
        $roles = [];
        foreach ($members as $member) {
            $posted = $_POST['roles'][$member['id']] ?? $member['role'];
            $roles[(int) $member['id']] = is_string($posted) && isset(Users::ROLES[$posted]) ? $posted : (string) $member['role'];
        }
        if (!in_array('admin', $roles, true)) {
            $errors['roles'] = 'Domácnost musí mít alespoň jednoho správce.';
        } elseif ($roles[(int) $user['id']] !== 'admin') {
            $errors['roles'] = 'Roli správce si nemůžete odebrat sami – požádejte jiného správce.';
        }

        // Sdílená nastavení
        $locationName = input('location_name');
        $timezone = input('timezone');
        $rssUrl = input('default_rss_url');
        $location = null;

        if ($locationName === '' || mb_strlen($locationName) > 80) {
            $errors['location_name'] = 'Zadejte lokalitu domácnosti (nejvýše 80 znaků).';
        } elseif ($locationName !== Household::get('location_name')) {
            $location = self::resolveLocation($locationName);
            if ($location === null) {
                $errors['location_name'] = 'Lokalitu „' . $locationName . '“ se nepodařilo najít. Zkuste název obce.';
            }
        }
        if (!in_array($timezone, \DateTimeZone::listIdentifiers(), true)) {
            $errors['timezone'] = 'Vyberte časové pásmo z nabídky.';
        }
        if ($rssUrl === '') {
            $errors['default_rss_url'] = 'Zadejte výchozí RSS zdroj sportovních novinek.';
        } elseif ($rssUrl !== Household::get('default_rss_url')) {
            $rssError = mb_strlen($rssUrl) > 300 ? 'Adresa RSS zdroje je příliš dlouhá.' : NewsService::validate($rssUrl);
            if ($rssError !== null) {
                $errors['default_rss_url'] = $rssError;
            }
        }

        $stations = self::postedStations();
        if (count($stations) > Household::MAX_STATIONS) {
            $errors['stations'] = 'Oblíbených stanic může být nejvýše ' . Household::MAX_STATIONS . '.';
        }

        if ($errors !== []) {
            self::render($user, $errors, $_POST, 422);
            return;
        }

        foreach ($roles as $id => $role) {
            Users::update($id, ['role' => $role]);
        }
        foreach (array_keys(WidgetRegistry::available()) as $key) {
            Household::setWidgetAccess(
                $key,
                !empty($_POST['widgets'][$key]['members']),
                !empty($_POST['widgets'][$key]['guest'])
            );
        }
        if ($location !== null) {
            Household::set('location_name', $locationName);
            Household::set('location_lat', (string) $location['lat']);
            Household::set('location_lon', (string) $location['lon']);
        }
        Household::set('timezone', $timezone);
        Household::set('default_rss_url', $rssUrl);
        Household::set('guest_shopping_add_only', checked_input('guest_shopping_add_only') ? '1' : '0');
        Household::set('guest_can_customize', checked_input('guest_can_customize') ? '1' : '0');
        Household::saveStations($stations);

        flash('success', 'Nastavení domácnosti bylo uloženo.');
        redirect('/domacnost');
    }

    // --- Členové domácnosti ---------------------------------------------------------------

    public static function createMember(): void
    {
        self::user(self::PERMISSION);
        [$data, $error] = self::validateMember(null);
        if ($error === null && mb_strlen($data['password']) < Users::MIN_PASSWORD_LENGTH) {
            $error = 'Dočasné heslo musí mít alespoň ' . Users::MIN_PASSWORD_LENGTH . ' znaků.';
        }
        if ($error !== null) {
            flash('error', $error);
            redirect('/domacnost');
        }

        Users::create($data['login'], $data['display_name'], $data['password'], $data['role'], $data['must_change']);
        flash('success', 'Člen ' . $data['display_name'] . ' byl přidán.');
        redirect('/domacnost');
    }

    public static function updateMember(string $id): void
    {
        $user = self::user(self::PERMISSION);
        $member = self::member($id);
        [$data, $error] = self::validateMember($member);

        if ($error === null && $data['password'] !== '' && mb_strlen($data['password']) < Users::MIN_PASSWORD_LENGTH) {
            $error = 'Nové heslo musí mít alespoň ' . Users::MIN_PASSWORD_LENGTH . ' znaků.';
        }
        if ($error === null && $member['role'] === 'admin' && $data['role'] !== 'admin') {
            if ((int) $member['id'] === (int) $user['id']) {
                $error = 'Roli správce si nemůžete odebrat sami – požádejte jiného správce.';
            } elseif (Users::countAdmins() <= 1) {
                $error = 'Domácnost musí mít alespoň jednoho správce.';
            }
        }
        if ($error !== null) {
            flash('error', $error);
            redirect('/domacnost');
        }

        Users::update((int) $member['id'], [
            'login' => $data['login'], 'display_name' => $data['display_name'], 'role' => $data['role'],
        ]);
        if ($data['password'] !== '') {
            // Správce heslo jen nastaví; člen si ho při dalším přihlášení může změnit.
            Users::setPassword((int) $member['id'], $data['password'], $data['must_change']);
            Auth::forgetDevices((int) $member['id']);
        }
        flash('success', 'Údaje člena ' . $data['display_name'] . ' byly uloženy.');
        redirect('/domacnost');
    }

    public static function deleteMember(string $id): void
    {
        $user = self::user(self::PERMISSION);
        $member = self::member($id);

        if ((int) $member['id'] === (int) $user['id']) {
            flash('error', 'Vlastní účet odebrat nelze.');
        } elseif ($member['role'] === 'admin' && Users::countAdmins() <= 1) {
            flash('error', 'Domácnost musí mít alespoň jednoho správce.');
        } else {
            Users::delete((int) $member['id']);
            flash('success', 'Člen ' . $member['display_name'] . ' byl odebrán.');
        }
        redirect('/domacnost');
    }

    // --- Pomocné metody -------------------------------------------------------------------

    private static function render(array $user, array $errors, array $posted, int $status = 200): void
    {
        $settings = Household::settings();
        $access = Household::widgetAccess();
        $stations = array_map(
            static fn (array $row): array => ['name' => $row['name'], 'stream_url' => $row['stream_url'], 'station_uuid' => (string) $row['station_uuid']],
            Household::stations()
        );

        // Po neúspěšném uložení zůstanou ve formuláři hodnoty, které správce zadal.
        if ($posted !== []) {
            foreach (['location_name', 'timezone', 'default_rss_url'] as $key) {
                $settings[$key] = input($key);
            }
            $settings['guest_shopping_add_only'] = checked_input('guest_shopping_add_only') ? '1' : '0';
            $settings['guest_can_customize'] = checked_input('guest_can_customize') ? '1' : '0';
            foreach ($access as $key => $value) {
                $access[$key] = ['members' => !empty($posted['widgets'][$key]['members']), 'guest' => !empty($posted['widgets'][$key]['guest'])];
            }
            $stations = self::postedStations();
        }

        self::page('app/household', $user, [
            'title' => 'Správa domácnosti',
            'active' => 'household',
            'members' => Users::all(),
            'postedRoles' => is_array($posted['roles'] ?? null) ? $posted['roles'] : [],
            'settings' => $settings,
            'access' => $access,
            'widgets' => WidgetRegistry::all(),
            'stations' => $stations,
            'timezones' => \DateTimeZone::listIdentifiers(\DateTimeZone::EUROPE),
            'errors' => $errors,
        ], $status);
    }

    private static function member(string $id): array
    {
        $member = ctype_digit($id) ? Users::find((int) $id) : null;
        if ($member === null) {
            throw new HttpError(404, 'Člen domácnosti nebyl nalezen.');
        }
        return $member;
    }

    /**
     * Ověří údaje z okna „Přidat / upravit člena“.
     *
     * @return array{0:array{login:string,display_name:string,role:string,password:string,must_change:bool},1:?string}
     */
    private static function validateMember(?array $existing): array
    {
        $data = [
            'display_name' => input('display_name'),
            'login' => input('login'),
            'role' => input('role'),
            'password' => (string) ($_POST['password'] ?? ''),
            'must_change' => checked_input('must_change'),
        ];

        $error = null;
        if ($data['display_name'] === '' || mb_strlen($data['display_name']) > 40) {
            $error = 'Zadejte jméno člena (nejvýše 40 znaků).';
        } elseif (mb_strlen($data['login']) < 3 || mb_strlen($data['login']) > 190 || preg_match('~\s~u', $data['login']) === 1) {
            $error = 'E-mail nebo uživatelské jméno musí mít 3 až 190 znaků bez mezer.';
        } elseif (!isset(Users::ROLES[$data['role']])) {
            $error = 'Vyberte roli z nabídky.';
        } else {
            $owner = Users::findByLogin($data['login']);
            if ($owner !== null && (int) $owner['id'] !== (int) ($existing['id'] ?? 0)) {
                $error = 'Přihlašovací jméno „' . $data['login'] . '“ už používá jiný člen.';
            }
        }
        return [$data, $error];
    }

    /**
     * Souřadnice lokality: buď je vybral správce z našeptávače (JavaScript je
     * poslal ve skrytých polích), nebo se dohledají podle názvu.
     *
     * @return array{lat:float,lon:float}|null
     */
    private static function resolveLocation(string $name): ?array
    {
        $lat = input('location_lat');
        $lon = input('location_lon');
        if (input('location_resolved') === $name && is_numeric($lat) && is_numeric($lon)
            && abs((float) $lat) <= 90 && abs((float) $lon) <= 180) {
            return ['lat' => round((float) $lat, 5), 'lon' => round((float) $lon, 5)];
        }
        try {
            $results = GeocodingService::search($name);
        } catch (\Throwable) {
            return null;
        }
        return isset($results[0]) ? ['lat' => $results[0]['lat'], 'lon' => $results[0]['lon']] : null;
    }

    /** @return list<array{name:string,stream_url:string,station_uuid:string}> */
    private static function postedStations(): array
    {
        $stations = [];
        foreach ((array) ($_POST['stations'] ?? []) as $station) {
            if (!is_array($station)) {
                continue;
            }
            $name = mb_substr(trim((string) ($station['name'] ?? '')), 0, 60);
            $url = trim((string) ($station['stream_url'] ?? ''));
            $uuid = trim((string) ($station['station_uuid'] ?? ''));
            if ($name !== '' && RadioService::isStreamUrl($url)) {
                $stations[] = [
                    'name' => $name,
                    'stream_url' => $url,
                    'station_uuid' => preg_match('~^[0-9a-f-]{36}$~i', $uuid) === 1 ? $uuid : '',
                ];
            }
        }
        return $stations;
    }
}
