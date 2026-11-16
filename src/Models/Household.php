<?php

declare(strict_types=1);

namespace DayBoard\Models;

use DayBoard\Core\Database;
use DayBoard\Widgets\WidgetRegistry;

/** Sdílená nastavení domácnosti: lokalita, povolené widgety, rozhlasové stanice. */
final class Household
{
    public const DEFAULTS = [
        'location_name' => 'Praha',
        'location_lat' => '50.08804',
        'location_lon' => '14.42076',
        'timezone' => 'Europe/Prague',
        'default_rss_url' => 'https://sport.ceskatelevize.cz/rss',
        'guest_shopping_add_only' => '1',
        'guest_can_customize' => '1',
    ];

    public const MAX_STATIONS = 8;

    /** @var array<string,string>|null */
    private static ?array $settings = null;

    /** @return array<string,string> */
    public static function settings(): array
    {
        if (self::$settings === null) {
            $stored = array_column(Database::all('SELECT key, value FROM settings'), 'value', 'key');
            self::$settings = $stored + self::DEFAULTS;
        }
        return self::$settings;
    }

    public static function get(string $key): string
    {
        return self::settings()[$key] ?? '';
    }

    public static function set(string $key, string $value): void
    {
        Database::run(
            'INSERT INTO settings (key, value) VALUES (?, ?) ON CONFLICT (key) DO UPDATE SET value = excluded.value',
            [$key, $value]
        );
        self::$settings = null;
    }

    /** @return array{name:string,lat:float,lon:float} */
    public static function location(): array
    {
        return [
            'name' => self::get('location_name'),
            'lat' => (float) self::get('location_lat'),
            'lon' => (float) self::get('location_lon'),
        ];
    }

    // --- Povolené widgety -----------------------------------------------------------------

    /** @return array<string,array{members:bool,guest:bool}> */
    public static function widgetAccess(): array
    {
        $stored = array_column(Database::all('SELECT * FROM widget_access'), null, 'widget_key');
        $access = [];
        foreach (WidgetRegistry::available() as $key => $widget) {
            $access[$key] = [
                'members' => isset($stored[$key]) ? (bool) $stored[$key]['members_enabled'] : true,
                'guest' => isset($stored[$key]) ? (bool) $stored[$key]['guest_enabled'] : false,
            ];
        }
        return $access;
    }

    public static function setWidgetAccess(string $key, bool $members, bool $guest): void
    {
        Database::run(
            'INSERT INTO widget_access (widget_key, members_enabled, guest_enabled) VALUES (?, ?, ?)
             ON CONFLICT (widget_key) DO UPDATE SET members_enabled = excluded.members_enabled, guest_enabled = excluded.guest_enabled',
            [$key, (int) $members, (int) $guest]
        );
    }

    /**
     * Widgety, které smí uživatel mít na nástěnce:
     * správce všechny dostupné, člen ty povolené správcem, host/dítě ty vybrané správcem.
     *
     * @return list<string>
     */
    public static function allowedWidgets(array $user): array
    {
        $access = self::widgetAccess();
        return array_values(array_filter(
            array_keys($access),
            static fn (string $key): bool => match ($user['role']) {
                'admin' => true,
                'member' => $access[$key]['members'],
                default => $access[$key]['guest'],
            }
        ));
    }

    // --- Rozhlasové stanice ---------------------------------------------------------------

    /** @return list<array<string,mixed>> */
    public static function stations(): array
    {
        return Database::all('SELECT * FROM radio_stations ORDER BY position, id');
    }

    /** @param list<array{name:string,stream_url:string,station_uuid:string}> $stations */
    public static function saveStations(array $stations): void
    {
        Database::transaction(static function () use ($stations): void {
            Database::run('DELETE FROM radio_stations');
            foreach (array_slice($stations, 0, self::MAX_STATIONS) as $position => $station) {
                Database::run(
                    'INSERT INTO radio_stations (name, stream_url, station_uuid, position) VALUES (?, ?, ?, ?)',
                    [$station['name'], $station['stream_url'], $station['station_uuid'] ?: null, $position]
                );
            }
        });
    }
}
