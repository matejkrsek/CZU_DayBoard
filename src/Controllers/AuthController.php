<?php

declare(strict_types=1);

namespace DayBoard\Controllers;

use DayBoard\Core\Auth;
use DayBoard\Core\Config;
use DayBoard\Core\View;
use DayBoard\Database\Seeder;
use DayBoard\Models\Users;

/** Přihlášení, odhlášení a změna hesla. */
final class AuthController extends Controller
{
    public static function showLogin(): void
    {
        if (Auth::check()) {
            redirect(self::homeFor(Auth::user()));
        }
        self::renderLogin();
    }

    public static function login(): void
    {
        $login = input('login');
        $password = (string) ($_POST['password'] ?? '');
        if ($login === '' || $password === '') {
            self::renderLogin($login, 'Vyplňte přihlašovací jméno i heslo.');
            return;
        }

        $error = Auth::attempt($login, $password, checked_input('remember'));
        if ($error !== null) {
            self::renderLogin($login, $error);
            return;
        }
        redirect(self::homeFor(Auth::user()));
    }

    public static function logout(): void
    {
        Auth::logout();
        redirect('/prihlaseni');
    }

    public static function showPassword(): void
    {
        $user = self::user();
        self::page('app/password', $user, ['title' => 'Změna hesla', 'active' => '', 'errors' => []]);
    }

    public static function changePassword(): void
    {
        $user = self::user();
        $current = (string) ($_POST['current_password'] ?? '');
        $new = (string) ($_POST['new_password'] ?? '');
        $repeat = (string) ($_POST['new_password_repeat'] ?? '');

        $errors = [];
        if (!password_verify($current, (string) $user['password_hash'])) {
            $errors['current_password'] = 'Současné heslo nesouhlasí.';
        }
        if (mb_strlen($new) < Users::MIN_PASSWORD_LENGTH) {
            $errors['new_password'] = 'Nové heslo musí mít alespoň ' . Users::MIN_PASSWORD_LENGTH . ' znaků.';
        } elseif ($new === $current) {
            $errors['new_password'] = 'Nové heslo se musí lišit od současného.';
        }
        if ($new !== $repeat) {
            $errors['new_password_repeat'] = 'Hesla se neshodují.';
        }

        if ($errors !== []) {
            self::page('app/password', $user, ['title' => 'Změna hesla', 'active' => '', 'errors' => $errors], 422);
            return;
        }

        Users::setPassword((int) $user['id'], $new);
        Auth::forgetDevices((int) $user['id']);
        // Změna hesla zneplatní všechna přihlášení; toto zařízení přihlásíme znovu.
        $fresh = Users::find((int) $user['id']);
        Auth::login($fresh);
        flash('success', 'Heslo bylo změněno.');
        redirect(self::homeFor($fresh));
    }

    private static function renderLogin(string $login = '', ?string $error = null): void
    {
        header('Cache-Control: no-store');
        header('X-Robots-Tag: noindex');
        View::send(View::render('app/login', [
            'title' => 'Přihlášení',
            'login' => $login,
            'error' => $error,
            'demoAccounts' => Config::isDemo() ? Seeder::DEMO_ACCOUNTS : [],
        ], 'auth'), $error === null ? 200 : 422);
    }

    /** Kam po přihlášení: podle volby „Domovská stránka po přihlášení“. */
    private static function homeFor(?array $user): string
    {
        if ($user === null) {
            return '/prihlaseni';
        }
        if ((int) $user['must_change_password'] === 1) {
            return '/zmena-hesla';
        }
        return match (true) {
            $user['home_page'] === 'settings' => '/nastaveni',
            $user['home_page'] === 'household' && Auth::can('household.manage', $user) => '/domacnost',
            default => '/nastenka',
        };
    }
}
