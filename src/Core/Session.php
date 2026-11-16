<?php

declare(strict_types=1);

namespace DayBoard\Core;

/**
 * Práce se session. Veřejné stránky session nezakládají (žádné cookies,
 * lépe se cachují) – session vzniká až na přihlašovací stránce.
 */
final class Session
{
    public const NAME = 'dayboard';

    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE || PHP_SAPI === 'cli') {
            return;
        }
        session_name(self::NAME);
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => base_path() . '/',
            'httponly' => true,
            'secure' => is_https(),
            'samesite' => 'Lax',
        ]);
        ini_set('session.use_strict_mode', '1');
        session_start();
    }

    public static function isActive(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    /** Naváže na existující session, ale novou nezakládá. */
    public static function resume(): void
    {
        if (isset($_COOKIE[self::NAME])) {
            self::start();
        }
    }

    /** Nové ID session po přihlášení – obrana proti session fixation. */
    public static function regenerate(): void
    {
        self::start();
        session_regenerate_id(true);
    }

    public static function destroy(): void
    {
        self::start();
        $_SESSION = [];
        $params = session_get_cookie_params();
        setcookie(self::NAME, '', [
            'expires' => time() - 3600,
            'path' => $params['path'],
            'httponly' => true,
            'secure' => $params['secure'],
            'samesite' => 'Lax',
        ]);
        session_destroy();
    }
}
