<?php

declare(strict_types=1);

namespace DayBoard\Controllers;

use DayBoard\Core\Auth;
use DayBoard\Core\HttpError;
use DayBoard\Models\Household;
use DayBoard\Models\Users;
use DayBoard\Services\CalendarService;
use DayBoard\Services\NewsService;

/** Osobní nastavení: vzhled, připojené účty, cíle cest a oblíbené zastávky. */
final class SettingsController extends Controller
{
    public const THEMES = ['light' => 'Světlý', 'dark' => 'Tmavý', 'system' => 'Podle systému'];
    public const DENSITIES = ['compact' => 'Kompaktní', 'standard' => 'Standardní', 'comfortable' => 'Volná'];

    public static function show(): void
    {
        $user = self::user();
        self::render($user, self::formFromUser($user), []);
    }

    public static function save(): void
    {
        $user = self::user();
        $canAppearance = Auth::can('settings.appearance', $user);
        $canPersonal = Auth::can('settings.personal', $user);
        if (!$canAppearance && !$canPersonal) {
            throw new HttpError(403, 'Osobní nastavení vám správce domácnosti nepovolil měnit.');
        }

        $form = self::formFromUser($user);
        $errors = [];
        $fields = [];

        if ($canAppearance) {
            $form['theme'] = input('theme');
            $form['density'] = input('density');
            $form['home_page'] = input('home_page');
            foreach (['show_greeting', 'show_scope_labels', 'confirm_remove'] as $toggle) {
                $form[$toggle] = checked_input($toggle);
                $fields[$toggle] = (int) $form[$toggle];
            }
            if (!isset(self::THEMES[$form['theme']])) {
                $errors['theme'] = 'Vyberte motiv z nabídky.';
            }
            if (!isset(self::DENSITIES[$form['density']])) {
                $errors['density'] = 'Vyberte hustotu mřížky z nabídky.';
            }
            if (!isset(self::homePages($user)[$form['home_page']])) {
                $errors['home_page'] = 'Vyberte domovskou stránku z nabídky.';
            }
            $fields += ['theme' => $form['theme'], 'density' => $form['density'], 'home_page' => $form['home_page']];
        }

        if ($canPersonal) {
            $form['display_name'] = input('display_name');
            $form['rss_url'] = input('rss_url');
            $form['origin'] = input('origin', 'home');
            $form['share_location'] = checked_input('share_location');
            $form['destinations'] = self::postedDestinations();
            $form['stops'] = self::postedStops();

            if ($form['display_name'] === '' || mb_strlen($form['display_name']) > 40) {
                $errors['display_name'] = 'Zadejte zobrazované jméno (nejvýše 40 znaků).';
            }
            if ($form['rss_url'] !== '' && $form['rss_url'] !== (string) $user['rss_url']) {
                // Nový zdroj rovnou zkusíme načíst, ať se chyba neukáže až na nástěnce.
                $rssError = mb_strlen($form['rss_url']) > 300 ? 'Adresa RSS zdroje je příliš dlouhá.' : NewsService::validate($form['rss_url']);
                if ($rssError !== null) {
                    $errors['rss_url'] = $rssError;
                }
            }
            foreach ($form['destinations'] as $index => $destination) {
                if ($destination['label'] === '' || $destination['address'] === '') {
                    $errors['destinations'] = 'U každého cíle cesty vyplňte název i adresu.';
                } elseif (mb_strlen($destination['label']) > 30 || mb_strlen($destination['address']) > 120) {
                    $errors['destinations'] = 'Název cíle může mít nejvýše 30 a adresa 120 znaků.';
                }
            }
            if (count($form['destinations']) > Users::MAX_DESTINATIONS) {
                $errors['destinations'] = 'Cíle cest mohou být nejvýše ' . Users::MAX_DESTINATIONS . '.';
            }
            if (count($form['stops']) > Users::MAX_STOPS) {
                $errors['stops'] = 'Oblíbených zastávek může být nejvýše ' . Users::MAX_STOPS . '.';
            }

            $fields += [
                'display_name' => $form['display_name'],
                'rss_url' => $form['rss_url'] !== '' ? $form['rss_url'] : null,
                'share_location' => (int) $form['share_location'],
            ];
        }

        if ($errors !== []) {
            self::render($user, $form, $errors, 422);
            return;
        }

        if ($canPersonal) {
            $userId = (int) $user['id'];
            Users::saveDestinations($userId, $form['destinations']);
            Users::saveStops($userId, $form['stops']);
            // Výchozí bod cest smí být jen „domov“ nebo některý z vlastních cílů.
            $ownIds = array_map('intval', array_column(Users::destinations($userId), 'id'));
            $origin = (int) $form['origin'];
            $fields['origin_destination_id'] = in_array($origin, $ownIds, true) ? $origin : null;
        }
        Users::update((int) $user['id'], $fields);

        flash('success', 'Nastavení bylo uloženo.');
        redirect('/nastaveni');
    }

    // --- Google Kalendář (OAuth 2.0) ------------------------------------------------------

    public static function googleConnect(): void
    {
        self::user('settings.personal');
        if (!CalendarService::isConfigured()) {
            flash('error', 'Připojení ke Googlu není na tomto serveru nastavené (chybí GOOGLE_CLIENT_ID v souboru .env).');
            redirect('/nastaveni');
        }
        $state = bin2hex(random_bytes(16));
        $_SESSION['google_oauth_state'] = $state;
        header('Location: ' . CalendarService::authUrl($state), true, 303);
        exit;
    }

    public static function googleCallback(): void
    {
        $user = self::user('settings.personal');
        $expected = (string) ($_SESSION['google_oauth_state'] ?? '');
        unset($_SESSION['google_oauth_state']);
        $state = (string) ($_GET['state'] ?? '');
        $code = (string) ($_GET['code'] ?? '');

        if ($expected === '' || !hash_equals($expected, $state)) {
            flash('error', 'Připojení účtu Google se nepodařilo ověřit. Zkuste to prosím znovu.');
        } elseif (isset($_GET['error']) || $code === '') {
            flash('info', 'Připojení účtu Google bylo zrušeno.');
        } else {
            try {
                CalendarService::connect((int) $user['id'], $code);
                flash('success', 'Google Kalendář je připojen. Widget Dnešní program teď ukazuje vaše události.');
            } catch (\Throwable $e) {
                error_log('DayBoard Google OAuth: ' . $e->getMessage());
                flash('error', 'Účet Google se nepodařilo připojit. Zkontrolujte nastavení OAuth klienta a zkuste to znovu.');
            }
        }
        redirect('/nastaveni');
    }

    public static function googleDisconnect(): void
    {
        $user = self::user('settings.personal');
        CalendarService::disconnect($user);
        flash('success', 'Účet Google byl odpojen.');
        redirect('/nastaveni');
    }

    // --- Pomocné metody -------------------------------------------------------------------

    private static function render(array $user, array $form, array $errors, int $status = 200): void
    {
        self::page('app/settings', $user, [
            'title' => 'Osobní nastavení',
            'active' => 'settings',
            'form' => $form,
            'errors' => $errors,
            'canAppearance' => Auth::can('settings.appearance', $user),
            'canPersonal' => Auth::can('settings.personal', $user),
            'homePages' => self::homePages($user),
            'googleConfigured' => CalendarService::isConfigured(),
            'defaultRss' => Household::get('default_rss_url'),
            'homeName' => Household::get('location_name'),
        ], $status);
    }

    /** @return array<string,string> */
    private static function homePages(array $user): array
    {
        $pages = ['dashboard' => 'Nástěnka', 'settings' => 'Osobní nastavení'];
        if (Auth::can('household.manage', $user)) {
            $pages['household'] = 'Správa domácnosti';
        }
        return $pages;
    }

    /** @return array<string,mixed> Hodnoty formuláře podle uložených dat. */
    private static function formFromUser(array $user): array
    {
        $userId = (int) $user['id'];
        return [
            'theme' => (string) $user['theme'],
            'density' => (string) $user['density'],
            'home_page' => (string) $user['home_page'],
            'show_greeting' => (bool) $user['show_greeting'],
            'show_scope_labels' => (bool) $user['show_scope_labels'],
            'confirm_remove' => (bool) $user['confirm_remove'],
            'display_name' => (string) $user['display_name'],
            'rss_url' => (string) $user['rss_url'],
            'origin' => $user['origin_destination_id'] !== null ? (string) $user['origin_destination_id'] : 'home',
            'share_location' => (bool) $user['share_location'],
            'destinations' => array_map(
                static fn (array $row): array => ['id' => (int) $row['id'], 'label' => (string) $row['label'], 'address' => (string) $row['address']],
                Users::destinations($userId)
            ),
            'stops' => Users::stops($userId),
        ];
    }

    /** @return list<array{id:int,label:string,address:string}> */
    private static function postedDestinations(): array
    {
        $rows = [];
        foreach ((array) ($_POST['dest'] ?? []) as $row) {
            if (!is_array($row)) {
                continue;
            }
            $label = trim((string) ($row['label'] ?? ''));
            $address = trim((string) ($row['address'] ?? ''));
            if ($label === '' && $address === '') {
                continue; // prázdný řádek znamená „bez cíle“
            }
            $rows[] = ['id' => (int) ($row['id'] ?? 0), 'label' => $label, 'address' => $address];
        }
        return $rows;
    }

    /** @return list<string> */
    private static function postedStops(): array
    {
        $stops = [];
        foreach ((array) ($_POST['stops'] ?? []) as $stop) {
            $stop = is_string($stop) ? mb_substr(trim($stop), 0, 60) : '';
            if ($stop !== '' && !in_array($stop, $stops, true)) {
                $stops[] = $stop;
            }
        }
        return $stops;
    }
}
