<?php

declare(strict_types=1);

namespace DayBoard\Core;

/**
 * Souborová cache pro odpovědi externích API (storage/cache).
 * Stránka díky ní nečeká na cizí servery a šetří se limity API.
 */
final class Cache
{
    private static function file(string $key): string
    {
        return DAYBOARD_ROOT . '/storage/cache/' . sha1($key) . '.json';
    }

    /**
     * Vrátí hodnotu z cache, nebo ji získá voláním $producer a na $ttl sekund uloží.
     * Když zdroj selže a v cache je starší hodnota, vrátí se raději ta.
     */
    public static function remember(string $key, int $ttl, callable $producer): mixed
    {
        $file = self::file($key);
        $entry = null;
        if (is_file($file)) {
            $entry = json_decode((string) @file_get_contents($file), true);
            if (is_array($entry) && ($entry['expires'] ?? 0) > time()) {
                return $entry['value'];
            }
        }

        try {
            $value = $producer();
        } catch (\Throwable $e) {
            if (is_array($entry) && array_key_exists('value', $entry)) {
                return $entry['value'];
            }
            throw $e;
        }

        self::put($key, $value, $ttl);
        return $value;
    }

    public static function put(string $key, mixed $value, int $ttl): void
    {
        $file = self::file($key);
        if (!is_dir(dirname($file))) {
            @mkdir(dirname($file), 0775, true);
        }
        // Zápis přes dočasný soubor, aby souběžný požadavek nečetl půlku dat.
        $tmp = $file . '.' . bin2hex(random_bytes(4)) . '.tmp';
        $json = json_encode(['expires' => time() + $ttl, 'value' => $value], JSON_UNESCAPED_UNICODE);
        if (@file_put_contents($tmp, $json, LOCK_EX) !== false) {
            @rename($tmp, $file) || @unlink($tmp);
        }

        if (random_int(1, 200) === 1) {
            self::prune();
        }
    }

    public static function forget(string $key): void
    {
        @unlink(self::file($key));
    }

    /** Smaže soubory cache starší než týden. */
    private static function prune(): void
    {
        foreach (glob(DAYBOARD_ROOT . '/storage/cache/*.{json,tmp}', GLOB_BRACE) ?: [] as $file) {
            if (filemtime($file) < time() - 7 * 86400) {
                @unlink($file);
            }
        }
    }
}
