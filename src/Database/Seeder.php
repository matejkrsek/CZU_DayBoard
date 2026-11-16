<?php

declare(strict_types=1);

namespace DayBoard\Database;

use DayBoard\Core\Database;
use DayBoard\Models\Household;
use DayBoard\Models\Users;

/**
 * Ukázková domácnost, kterou se naplní nová databáze – aplikaci tak lze hned
 * po naklonování vyzkoušet ve všech třech rolích.
 */
final class Seeder
{
    public const DEMO_PASSWORD = 'dayboard';

    /** Ukázkové účty: [přihlašovací jméno, zobrazované jméno, role]. */
    public const DEMO_ACCOUNTS = [
        ['miroslav@domacnost.cz', 'Miroslav', 'admin'],
        ['jana@domacnost.cz', 'Jana', 'member'],
        ['tomas@domacnost.cz', 'Tomáš', 'member'],
        ['eliska@domacnost.cz', 'Eliška', 'guest'],
    ];

    public static function run(): void
    {
        Database::transaction(static function (): void {
            $ids = [];
            foreach (self::DEMO_ACCOUNTS as [$login, $name, $role]) {
                $ids[$name] = Users::create($login, $name, self::DEMO_PASSWORD, $role, false);
            }

            foreach (Household::DEFAULTS as $key => $value) {
                Database::run('INSERT INTO settings (key, value) VALUES (?, ?)', [$key, $value]);
            }

            // Host/dítě vidí jen widgety vybrané správcem (viz wireframe Správa domácnosti).
            $guestWidgets = ['weather', 'nameday', 'shopping', 'map', 'radio'];
            foreach (['weather', 'calendar', 'nameday', 'traffic', 'transit', 'news', 'shopping', 'map', 'radio'] as $key) {
                Database::run(
                    'INSERT INTO widget_access (widget_key, members_enabled, guest_enabled) VALUES (?, 1, ?)',
                    [$key, (int) in_array($key, $guestWidgets, true)]
                );
            }

            // Cíle cest: [štítek, adresa, zeměpisná šířka, délka] – veřejná místa v Praze a okolí.
            $destinations = [
                'Miroslav' => [
                    ['Škola', 'náměstí Jiřího z Poděbrad, Praha 3', 50.07770, 14.45000],
                    ['Práce', 'Na Pankráci, Praha 4', 50.05100, 14.43910],
                    ['Babička', 'Masarykovo náměstí, Říčany', 49.99170, 14.65430],
                ],
                'Jana' => [
                    ['Práce', 'Karlovo náměstí, Praha 2', 50.07560, 14.41900],
                    ['Sportovní hala', 'Strahov, Praha 6', 50.08020, 14.38770],
                ],
            ];
            foreach ($destinations as $name => $rows) {
                foreach ($rows as $position => [$label, $address, $lat, $lon]) {
                    Database::run(
                        'INSERT INTO destinations (user_id, label, address, lat, lon, position) VALUES (?, ?, ?, ?, ?, ?)',
                        [$ids[$name], $label, $address, $lat, $lon, $position]
                    );
                }
            }

            $stops = [
                'Miroslav' => ['Hlavní nádraží', 'Nádraží Holešovice', 'Černý Most'],
                'Jana' => ['Anděl', 'Karlovo náměstí'],
                'Tomáš' => ['Dejvická'],
            ];
            foreach ($stops as $name => $names) {
                Users::saveStops($ids[$name], $names);
            }

            $shopping = [['Mléko', 0], ['Chléb', 1], ['Vejce', 0], ['Jablka', 0], ['Toaletní papír', 0], ['Kávová zrna', 0]];
            foreach ($shopping as [$item, $done]) {
                Database::run('INSERT INTO shopping_items (name, done, created_by) VALUES (?, ?, ?)', [$item, $done, $ids['Jana']]);
            }

            $stations = [
                ['ČRo Radiožurnál', 'https://rozhlas.stream/radiozurnal_mp3_128.mp3'],
                ['ČRo Dvojka', 'https://rozhlas.stream/dvojka_mp3_128.mp3'],
                ['Rádio Beat', 'https://icecast2.play.cz/beat128aac'],
            ];
            foreach ($stations as $position => [$name, $url]) {
                Database::run('INSERT INTO radio_stations (name, stream_url, position) VALUES (?, ?, ?)', [$name, $url, $position]);
            }
        });
    }
}
