<?php

declare(strict_types=1);

namespace DayBoard\Core;

/**
 * Ochrana proti CSRF: každý formulář (pole _csrf) i AJAX požadavek
 * (hlavička X-CSRF-Token) musí poslat token uložený v session.
 */
final class Csrf
{
    public static function token(): string
    {
        Session::start();
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    public static function isValid(): bool
    {
        Session::resume();
        $expected = Session::isActive() ? ($_SESSION['_csrf'] ?? '') : '';
        $given = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        return is_string($given) && $expected !== '' && hash_equals($expected, $given);
    }
}
