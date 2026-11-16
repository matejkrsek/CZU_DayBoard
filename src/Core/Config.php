<?php

declare(strict_types=1);

namespace DayBoard\Core;

/**
 * Konfigurace ze souboru .env (KLÍČ=hodnota). Proměnné prostředí mají přednost,
 * takže stejné klíče lze nastavit i v Dockeru nebo ve webovém serveru.
 */
final class Config
{
    /** @var array<string,string> */
    private static array $values = [];

    public static function load(string $file): void
    {
        self::$values = [];
        if (!is_file($file)) {
            return;
        }
        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $value = trim($value);
            // Hodnota může být v uvozovkách: KEY="text s mezerami"
            if (strlen($value) >= 2 && ($value[0] === '"' || $value[0] === "'") && $value[-1] === $value[0]) {
                $value = substr($value, 1, -1);
            }
            self::$values[trim($key)] = $value;
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $env = getenv($key);
        if ($env !== false && $env !== '') {
            return $env;
        }
        $value = self::$values[$key] ?? '';
        return $value !== '' ? $value : $default;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = self::get($key);
        if ($value === null) {
            return $default;
        }
        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on', 'ano'], true);
    }

    public static function isDemo(): bool
    {
        return self::bool('DEMO_MODE', true);
    }

    public static function isProduction(): bool
    {
        return self::get('APP_ENV', 'development') === 'production';
    }

    /** Cesta k souboru: relativní se vztahuje ke kořeni projektu. */
    public static function path(string $key, string $default): string
    {
        $path = (string) self::get($key, $default);
        $isAbsolute = str_starts_with($path, '/') || preg_match('~^[A-Za-z]:[\\\\/]~', $path) === 1;
        return $isAbsolute ? $path : DAYBOARD_ROOT . '/' . $path;
    }
}
