<?php

declare(strict_types=1);

namespace DayBoard\Core;

use PDO;
use PDOStatement;

/**
 * Přístup k SQLite databázi přes PDO. Při prvním spuštění se databáze sama
 * vytvoří ze souboru database/schema.sql a naplní ukázkovou domácností.
 */
final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        $path = Config::path('DB_PATH', 'storage/dayboard.sqlite');
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $pdo = new PDO('sqlite:' . $path, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec('PRAGMA busy_timeout = 5000');
        self::$pdo = $pdo;

        $hasSchema = $pdo->query("SELECT name FROM sqlite_master WHERE type = 'table' AND name = 'users'")->fetch();
        if (!$hasSchema) {
            // Schéma i ukázková data v jedné transakci: při chybě nezůstane napůl založená databáze.
            self::transaction(static function () use ($pdo): void {
                $pdo->exec((string) file_get_contents(DAYBOARD_ROOT . '/database/schema.sql'));
                \DayBoard\Database\Seeder::run();
            });
        }

        return $pdo;
    }

    /** @param array<int|string,mixed> $params */
    public static function run(string $sql, array $params = []): PDOStatement
    {
        $statement = self::pdo()->prepare($sql);
        $statement->execute($params);
        return $statement;
    }

    /** @param array<int|string,mixed> $params */
    public static function row(string $sql, array $params = []): ?array
    {
        $row = self::run($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    /**
     * @param array<int|string,mixed> $params
     * @return list<array<string,mixed>>
     */
    public static function all(string $sql, array $params = []): array
    {
        return self::run($sql, $params)->fetchAll();
    }

    /** @param array<int|string,mixed> $params */
    public static function value(string $sql, array $params = []): mixed
    {
        $value = self::run($sql, $params)->fetchColumn();
        return $value === false ? null : $value;
    }

    public static function lastId(): int
    {
        return (int) self::pdo()->lastInsertId();
    }

    /**
     * Provede více změn atomicky – buď všechny, nebo žádnou.
     * Volání lze vnořovat: vnitřní se stane součástí vnější transakce.
     */
    public static function transaction(callable $callback): mixed
    {
        $pdo = self::pdo();
        if ($pdo->inTransaction()) {
            return $callback();
        }
        $pdo->beginTransaction();
        try {
            $result = $callback();
            $pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
