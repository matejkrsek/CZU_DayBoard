<?php

declare(strict_types=1);

namespace DayBoard\Models;

use DayBoard\Core\Database;

/** Členové domácnosti a jejich osobní data (cíle cest, oblíbené zastávky). */
final class Users
{
    public const ROLES = [
        'admin' => 'Správce domácnosti',
        'member' => 'Člen',
        'guest' => 'Host / dítě',
    ];

    public const MAX_DESTINATIONS = 3;
    public const MAX_STOPS = 6;
    public const MIN_PASSWORD_LENGTH = 8;

    /** Sloupce, které smí měnit metoda update(). */
    private const EDITABLE = [
        'login', 'display_name', 'role', 'must_change_password',
        'theme', 'density', 'home_page', 'show_greeting', 'show_scope_labels', 'confirm_remove',
        'share_location', 'origin_destination_id', 'rss_url', 'layout_saved',
        'google_email', 'google_access_token', 'google_refresh_token', 'google_token_expires',
    ];

    public static function find(int $id): ?array
    {
        return Database::row('SELECT * FROM users WHERE id = ?', [$id]);
    }

    public static function findByLogin(string $login): ?array
    {
        return Database::row('SELECT * FROM users WHERE login = ?', [$login]);
    }

    /** @return list<array<string,mixed>> */
    public static function all(): array
    {
        return Database::all(
            "SELECT * FROM users ORDER BY CASE role WHEN 'admin' THEN 0 WHEN 'member' THEN 1 ELSE 2 END, id"
        );
    }

    public static function create(string $login, string $displayName, string $password, string $role, bool $mustChange): int
    {
        Database::run(
            'INSERT INTO users (login, display_name, password_hash, role, must_change_password) VALUES (?, ?, ?, ?, ?)',
            [$login, $displayName, password_hash($password, PASSWORD_DEFAULT), $role, (int) $mustChange]
        );
        return Database::lastId();
    }

    /** @param array<string,mixed> $fields */
    public static function update(int $id, array $fields): void
    {
        $fields = array_intersect_key($fields, array_flip(self::EDITABLE));
        if ($fields === []) {
            return;
        }
        $assignments = implode(', ', array_map(static fn (string $column): string => $column . ' = ?', array_keys($fields)));
        Database::run('UPDATE users SET ' . $assignments . ' WHERE id = ?', [...array_values($fields), $id]);
    }

    public static function setPassword(int $id, string $password, bool $mustChange = false): void
    {
        Database::run(
            'UPDATE users SET password_hash = ?, must_change_password = ? WHERE id = ?',
            [password_hash($password, PASSWORD_DEFAULT), (int) $mustChange, $id]
        );
    }

    public static function delete(int $id): void
    {
        Database::run('DELETE FROM users WHERE id = ?', [$id]);
    }

    public static function countAdmins(): int
    {
        return (int) Database::value("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    }

    public static function initial(array $user): string
    {
        return mb_strtoupper(mb_substr(trim((string) $user['display_name']), 0, 1)) ?: '?';
    }

    // --- Cíle cest (widget Doprava autem) --------------------------------------------------

    /** @return list<array<string,mixed>> */
    public static function destinations(int $userId): array
    {
        return Database::all('SELECT * FROM destinations WHERE user_id = ? ORDER BY position, id', [$userId]);
    }

    /**
     * Uloží cíle cest. Existující řádky se aktualizují (ID zůstává, protože na něj
     * může odkazovat výchozí bod cest), nové se přidají a chybějící smažou.
     *
     * @param list<array{id:int,label:string,address:string}> $rows
     */
    public static function saveDestinations(int $userId, array $rows): void
    {
        Database::transaction(static function () use ($userId, $rows): void {
            $existing = array_column(self::destinations($userId), null, 'id');
            $kept = [];
            foreach (array_slice($rows, 0, self::MAX_DESTINATIONS) as $position => $row) {
                $current = $existing[$row['id']] ?? null;
                if ($current !== null) {
                    $moved = $current['address'] !== $row['address'];
                    Database::run(
                        'UPDATE destinations SET label = ?, address = ?, position = ?, lat = ?, lon = ? WHERE id = ?',
                        [$row['label'], $row['address'], $position, $moved ? null : $current['lat'], $moved ? null : $current['lon'], $current['id']]
                    );
                    $kept[] = (int) $current['id'];
                } else {
                    Database::run(
                        'INSERT INTO destinations (user_id, label, address, position) VALUES (?, ?, ?, ?)',
                        [$userId, $row['label'], $row['address'], $position]
                    );
                    $kept[] = Database::lastId();
                }
            }
            foreach (array_keys($existing) as $id) {
                if (!in_array((int) $id, $kept, true)) {
                    Database::run('DELETE FROM destinations WHERE id = ?', [$id]);
                    Database::run('UPDATE users SET origin_destination_id = NULL WHERE id = ? AND origin_destination_id = ?', [$userId, $id]);
                }
            }
        });
    }

    public static function setDestinationCoordinates(int $id, float $lat, float $lon): void
    {
        Database::run('UPDATE destinations SET lat = ?, lon = ? WHERE id = ?', [$lat, $lon, $id]);
    }

    // --- Oblíbené zastávky (widget Odjezdy MHD) --------------------------------------------

    /** @return list<string> */
    public static function stops(int $userId): array
    {
        return array_column(Database::all('SELECT name FROM stops WHERE user_id = ? ORDER BY position, id', [$userId]), 'name');
    }

    /** @param list<string> $names */
    public static function saveStops(int $userId, array $names): void
    {
        Database::transaction(static function () use ($userId, $names): void {
            Database::run('DELETE FROM stops WHERE user_id = ?', [$userId]);
            foreach (array_slice(array_values(array_unique($names)), 0, self::MAX_STOPS) as $position => $name) {
                Database::run('INSERT INTO stops (user_id, name, position) VALUES (?, ?, ?)', [$userId, $name, $position]);
            }
        });
    }
}
