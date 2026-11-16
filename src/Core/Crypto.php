<?php

declare(strict_types=1);

namespace DayBoard\Core;

/**
 * Šifrování citlivých hodnot v databázi (tokeny Google) pomocí AES-256-GCM.
 * Klíč se při prvním použití vygeneruje do storage/app.key (není v Gitu).
 */
final class Crypto
{
    private static function key(): string
    {
        $fromEnv = (string) Config::get('APP_KEY', '');
        if ($fromEnv !== '') {
            return hash('sha256', $fromEnv, true);
        }

        $file = DAYBOARD_ROOT . '/storage/app.key';
        if (!is_file($file)) {
            file_put_contents($file, base64_encode(random_bytes(32)), LOCK_EX);
            @chmod($file, 0600);
        }
        $key = base64_decode(trim((string) file_get_contents($file)), true);
        if ($key === false || strlen($key) !== 32) {
            throw new \RuntimeException('Soubor storage/app.key je poškozený.');
        }
        return $key;
    }

    public static function encrypt(string $plain): string
    {
        $iv = random_bytes(12);
        $tag = '';
        $cipher = openssl_encrypt($plain, 'aes-256-gcm', self::key(), OPENSSL_RAW_DATA, $iv, $tag);
        if ($cipher === false) {
            throw new \RuntimeException('Šifrování se nezdařilo.');
        }
        return base64_encode($iv . $tag . $cipher);
    }

    /** Vrací null, pokud hodnotu nelze dešifrovat (např. po výměně klíče). */
    public static function decrypt(?string $payload): ?string
    {
        if ($payload === null || $payload === '') {
            return null;
        }
        $raw = base64_decode($payload, true);
        if ($raw === false || strlen($raw) < 29) {
            return null;
        }
        $plain = openssl_decrypt(substr($raw, 28), 'aes-256-gcm', self::key(), OPENSSL_RAW_DATA, substr($raw, 0, 12), substr($raw, 12, 16));
        return $plain === false ? null : $plain;
    }
}
