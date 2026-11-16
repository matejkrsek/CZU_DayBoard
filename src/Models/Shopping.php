<?php

declare(strict_types=1);

namespace DayBoard\Models;

use DayBoard\Core\Database;

/** Sdílený nákupní seznam domácnosti. */
final class Shopping
{
    public const MAX_ITEMS = 200;
    public const MAX_NAME_LENGTH = 80;

    /** @return list<array<string,mixed>> Nekoupené položky první, koupené na konci. */
    public static function items(): array
    {
        return Database::all(
            'SELECT s.*, u.display_name AS author FROM shopping_items s
             LEFT JOIN users u ON u.id = s.created_by
             ORDER BY s.done, s.id'
        );
    }

    /** Přidá položku. Vrací false, pokud je název prázdný nebo je seznam plný. */
    public static function add(string $name, int $userId): bool
    {
        $name = trim((string) preg_replace('~\s+~u', ' ', $name));
        $name = mb_substr($name, 0, self::MAX_NAME_LENGTH);
        if ($name === '' || (int) Database::value('SELECT COUNT(*) FROM shopping_items') >= self::MAX_ITEMS) {
            return false;
        }
        // Stejná nekoupená položka už na seznamu je – podruhé ji nepřidáváme.
        // (Porovnání řeší PHP, protože SQLite neumí malá/velká písmena s diakritikou.)
        foreach (Database::all('SELECT name FROM shopping_items WHERE done = 0') as $row) {
            if (mb_strtolower((string) $row['name']) === mb_strtolower($name)) {
                return true;
            }
        }
        Database::run('INSERT INTO shopping_items (name, created_by) VALUES (?, ?)', [$name, $userId]);
        return true;
    }

    public static function toggle(int $id): void
    {
        Database::run('UPDATE shopping_items SET done = 1 - done WHERE id = ?', [$id]);
    }

    public static function delete(int $id): void
    {
        Database::run('DELETE FROM shopping_items WHERE id = ?', [$id]);
    }

    public static function clearDone(): void
    {
        Database::run('DELETE FROM shopping_items WHERE done = 1');
    }
}
