<?php

declare(strict_types=1);

namespace DayBoard\Models;

use DayBoard\Core\Database;
use DayBoard\Widgets\WidgetRegistry;

/**
 * Rozložení nástěnky. Každý widget má pozici (x, y) a velikost (w, h) v buňkách
 * mřížky o šířce WidgetRegistry::COLS sloupců. Stejná pravidla (skládání bez
 * překryvů a mezer) používá i JavaScript v režimu úprav.
 */
final class Layouts
{
    /**
     * Rozložení, které se uživateli zobrazí – jen widgety, které má povolené.
     *
     * @return list<array{key:string,x:int,y:int,w:int,h:int}>
     */
    public static function forUser(array $user): array
    {
        $allowed = Household::allowedWidgets($user);

        // Host/dítě si rozložení neupravuje; nový člen začíná s výchozím.
        if ($user['role'] === 'guest' || (int) $user['layout_saved'] === 0) {
            return self::pack($allowed);
        }

        $items = [];
        foreach (Database::all('SELECT * FROM layouts WHERE user_id = ?', [$user['id']]) as $row) {
            if (in_array($row['widget_key'], $allowed, true)) {
                $items[] = [
                    'key' => (string) $row['widget_key'],
                    'x' => (int) $row['x'], 'y' => (int) $row['y'],
                    'w' => (int) $row['w'], 'h' => (int) $row['h'],
                ];
            }
        }
        // Správce mohl některý widget mezitím zakázat – vzniklé mezery se zavřou.
        return self::compact($items);
    }

    /** @param list<array{key:string,x:int,y:int,w:int,h:int}> $items */
    public static function save(int $userId, array $items): void
    {
        Database::transaction(static function () use ($userId, $items): void {
            Database::run('DELETE FROM layouts WHERE user_id = ?', [$userId]);
            foreach ($items as $item) {
                Database::run(
                    'INSERT INTO layouts (user_id, widget_key, x, y, w, h) VALUES (?, ?, ?, ?, ?, ?)',
                    [$userId, $item['key'], $item['x'], $item['y'], $item['w'], $item['h']]
                );
            }
            Database::run('UPDATE users SET layout_saved = 1 WHERE id = ?', [$userId]);
        });
    }

    /**
     * Ověří rozložení poslané z prohlížeče. Nikdy nevěříme datům od klienta:
     * neznámé nebo nepovolené widgety a hodnoty mimo mřížku odmítneme.
     *
     * @param list<string> $allowedKeys
     * @return list<array{key:string,x:int,y:int,w:int,h:int}>
     */
    public static function validate(mixed $raw, array $allowedKeys): array
    {
        if (!is_array($raw) || count($raw) > count($allowedKeys)) {
            throw new \InvalidArgumentException('Neplatné rozložení.');
        }
        $registry = WidgetRegistry::available();
        $items = [];
        foreach ($raw as $entry) {
            $key = is_array($entry) ? ($entry['key'] ?? null) : null;
            if (!is_string($key) || !in_array($key, $allowedKeys, true) || isset($items[$key])) {
                throw new \InvalidArgumentException('Rozložení obsahuje nepovolený widget.');
            }
            foreach (['x', 'y', 'w', 'h'] as $field) {
                if (!isset($entry[$field]) || !is_int($entry[$field])) {
                    throw new \InvalidArgumentException('Rozložení má neplatné souřadnice.');
                }
            }
            $widget = $registry[$key];
            $w = max($widget['minW'], min(WidgetRegistry::COLS, $entry['w']));
            $h = max($widget['minH'], min(WidgetRegistry::MAX_H, $entry['h']));
            $items[$key] = [
                'key' => $key,
                'x' => max(0, min(WidgetRegistry::COLS - $w, $entry['x'])),
                'y' => max(0, min(200, $entry['y'])),
                'w' => $w,
                'h' => $h,
            ];
        }
        return self::compact(array_values($items));
    }

    /**
     * Výchozí rozložení: widgety se postupně umisťují na první volné místo
     * (zleva doprava, shora dolů) ve výchozí velikosti.
     *
     * @param list<string> $keys
     * @return list<array{key:string,x:int,y:int,w:int,h:int}>
     */
    public static function pack(array $keys): array
    {
        $items = [];
        foreach (WidgetRegistry::available() as $key => $widget) {
            if (!in_array($key, $keys, true)) {
                continue;
            }
            $item = ['key' => $key, 'x' => 0, 'y' => 0, 'w' => $widget['w'], 'h' => $widget['h']];
            for ($y = 0; ; $y++) {
                for ($x = 0; $x <= WidgetRegistry::COLS - $item['w']; $x++) {
                    $candidate = ['x' => $x, 'y' => $y] + $item;
                    if (self::firstCollision($items, $candidate) === null) {
                        $items[] = $candidate;
                        continue 3;
                    }
                }
            }
        }
        return self::sort($items);
    }

    /**
     * Svislé zhuštění: každý widget vystoupá co nejvýš, překryvy se odsunou dolů.
     *
     * @param list<array{key:string,x:int,y:int,w:int,h:int}> $items
     * @return list<array{key:string,x:int,y:int,w:int,h:int}>
     */
    public static function compact(array $items): array
    {
        $placed = [];
        foreach (self::sort($items) as $item) {
            while ($item['y'] > 0 && self::firstCollision($placed, ['y' => $item['y'] - 1] + $item) === null) {
                $item['y']--;
            }
            while (($hit = self::firstCollision($placed, $item)) !== null) {
                $item['y'] = $hit['y'] + $hit['h'];
            }
            $placed[] = $item;
        }
        return self::sort($placed);
    }

    /** Pořadí čtení: shora dolů, zleva doprava (určuje i pořadí na mobilu). */
    private static function sort(array $items): array
    {
        usort($items, static fn (array $a, array $b): int => [$a['y'], $a['x']] <=> [$b['y'], $b['x']]);
        return $items;
    }

    private static function firstCollision(array $items, array $candidate): ?array
    {
        foreach ($items as $item) {
            if (
                $item['key'] !== $candidate['key']
                && $item['x'] < $candidate['x'] + $candidate['w'] && $item['x'] + $item['w'] > $candidate['x']
                && $item['y'] < $candidate['y'] + $candidate['h'] && $item['y'] + $item['h'] > $candidate['y']
            ) {
                return $item;
            }
        }
        return null;
    }
}
