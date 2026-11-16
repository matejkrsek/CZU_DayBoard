<?php

declare(strict_types=1);

namespace DayBoard\Controllers;

use DayBoard\Core\Auth;
use DayBoard\Core\View;
use DayBoard\Models\Household;

/** Společný základ kontrolerů aplikace po přihlášení. */
abstract class Controller
{
    /**
     * Přihlášený uživatel (případně s požadovaným oprávněním). Zároveň nastaví
     * časové pásmo domácnosti a zakáže ukládání stránky do cache prohlížeče.
     */
    protected static function user(?string $permission = null): array
    {
        $user = $permission === null ? Auth::requireUser() : Auth::requirePermission($permission);

        $timezone = Household::get('timezone');
        if (in_array($timezone, \DateTimeZone::listIdentifiers(), true)) {
            date_default_timezone_set($timezone);
        }
        header('Cache-Control: no-store');
        header('X-Robots-Tag: noindex, nofollow');
        return $user;
    }

    /** Vykreslí stránku aplikace v layoutu s hlavní navigací. */
    protected static function page(string $template, array $user, array $data, int $status = 200): void
    {
        View::send(View::render($template, $data + ['user' => $user], 'app'), $status);
    }
}
