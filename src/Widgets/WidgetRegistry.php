<?php

declare(strict_types=1);

namespace DayBoard\Widgets;

/**
 * Katalog widgetů nástěnky. Nový widget = záznam zde + šablona
 * templates/widgets/{klíč}.php + větev ve WidgetRenderer::data().
 *
 * Pořadí záznamů určuje výchozí rozložení (viz Layouts::pack()).
 */
final class WidgetRegistry
{
    /** Počet sloupců mřížky nástěnky. */
    public const COLS = 4;
    /** Největší výška widgetu v buňkách. */
    public const MAX_H = 4;

    public const SCOPE_LABELS = [
        'shared' => 'Sdílené',
        'personal' => 'Osobní',
        'custom' => 'Dle nastavení',
        'extension' => 'Možné rozšíření',
    ];

    /**
     * title    – název v záhlaví widgetu
     * scope    – typ údajů (viz SCOPE_LABELS)
     * w, h     – výchozí velikost v buňkách; minW, minH – nejmenší povolená
     * async    – true: obsah se načítá AJAXem až po vykreslení stránky (externí API)
     * refresh  – interval automatické obnovy v sekundách (0 = neobnovovat)
     * available – false: plánované rozšíření, zatím nelze přidat
     *
     * @return array<string,array<string,mixed>>
     */
    public static function all(): array
    {
        return [
            'weather' => [
                'title' => 'Počasí', 'icon' => 'cloud-sun', 'scope' => 'shared',
                'w' => 1, 'h' => 1, 'async' => true, 'refresh' => 900,
                'summary' => 'Aktuální počasí a předpověď pro lokalitu domácnosti.',
            ],
            'calendar' => [
                'title' => 'Dnešní program', 'icon' => 'calendar', 'scope' => 'personal',
                'w' => 2, 'h' => 2, 'async' => true, 'refresh' => 300,
                'summary' => 'Události z připojeného Google Kalendáře uživatele.',
            ],
            'nameday' => [
                'title' => 'Svátek a denní informace', 'icon' => 'star', 'scope' => 'shared',
                'w' => 1, 'h' => 1, 'async' => true, 'refresh' => 3600,
                'summary' => 'Svátek, datum a další stručné informace k danému dni.',
            ],
            'traffic' => [
                'title' => 'Doprava autem', 'icon' => 'car', 'scope' => 'personal',
                'w' => 1, 'h' => 1, 'async' => true, 'refresh' => 300,
                'summary' => 'Aktuální doba jízdy do tří přednastavených míst včetně provozu.',
            ],
            'transit' => [
                'title' => 'Odjezdy MHD', 'icon' => 'tram', 'scope' => 'personal',
                'w' => 1, 'h' => 1, 'async' => true, 'refresh' => 30,
                'summary' => 'Nejbližší odjezdy z oblíbených zastávek.',
            ],
            'news' => [
                'title' => 'Sportovní novinky', 'icon' => 'news', 'scope' => 'custom',
                'w' => 2, 'h' => 1, 'async' => true, 'refresh' => 600,
                'summary' => 'Aktuální články a zprávy z nastaveného RSS zdroje.',
            ],
            'shopping' => [
                'title' => 'Nákupní seznam', 'icon' => 'cart', 'scope' => 'shared',
                'w' => 1, 'h' => 2, 'async' => false, 'refresh' => 20,
                'summary' => 'Společný seznam položek pro domácnost.',
            ],
            'map' => [
                'title' => 'Poloha členů', 'icon' => 'pin', 'scope' => 'shared',
                'w' => 1, 'h' => 2, 'async' => false, 'refresh' => 0,
                'summary' => 'Mapa se sdílenou polohou vybraných členů domácnosti.',
            ],
            'radio' => [
                'title' => 'Rádio', 'icon' => 'radio', 'scope' => 'shared',
                'w' => 2, 'h' => 1, 'async' => false, 'refresh' => 0,
                'summary' => 'Oblíbené internetové rozhlasové stanice domácnosti.',
            ],
            'photos' => [
                'title' => 'Fotoalbum', 'icon' => 'photo', 'scope' => 'extension',
                'w' => 2, 'h' => 2, 'async' => false, 'refresh' => 0, 'available' => false,
                'summary' => 'Zobrazení fotografií z domácího úložiště NAS.',
            ],
        ];
    }

    /**
     * Widgety, které lze skutečně použít, doplněné o výchozí hodnoty.
     *
     * @return array<string,array<string,mixed>>
     */
    public static function available(): array
    {
        static $available = null;
        if ($available === null) {
            $available = [];
            foreach (self::all() as $key => $widget) {
                if (($widget['available'] ?? true) === true) {
                    $available[$key] = $widget + ['minW' => 1, 'minH' => 1];
                }
            }
        }
        return $available;
    }

    public static function scopeLabel(string $scope): string
    {
        return self::SCOPE_LABELS[$scope] ?? '';
    }
}
