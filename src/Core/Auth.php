<?php

declare(strict_types=1);

namespace DayBoard\Core;

use DayBoard\Models\Household;
use DayBoard\Models\Users;

/**
 * Přihlašování a řízení přístupu podle rolí (RBAC).
 * Role: admin = správce domácnosti, member = člen, guest = host/dítě.
 */
final class Auth
{
    private const REMEMBER_COOKIE = 'dayboard_remember';
    private const REMEMBER_DAYS = 30;
    private const MAX_ATTEMPTS = 5;
    private const ATTEMPT_WINDOW = 600; // 10 minut

    /**
     * Matice oprávnění – odpovídá tabulce 2 v úvodní studii.
     * Výjimky pro hosta/dítě řízené nastavením správce řeší metoda can().
     */
    private const PERMISSIONS = [
        'dashboard.view'      => ['admin', 'member', 'guest'],
        'layout.edit'         => ['admin', 'member'],            // výběr widgetů a úprava rozložení
        'settings.appearance' => ['admin', 'member'],            // host/dítě jen se svolením správce
        'settings.personal'   => ['admin', 'member'],            // připojené účty, cíle cest, zastávky
        'household.manage'    => ['admin'],                      // členové, role, povolené widgety, sdílená nastavení
        'shopping.add'        => ['admin', 'member', 'guest'],
        'shopping.delete'     => ['admin', 'member'],            // host/dítě standardně jen přidává
    ];

    private static ?array $user = null;
    private static bool $resolved = false;

    public static function user(): ?array
    {
        if (self::$resolved) {
            return self::$user;
        }
        self::$resolved = true;

        Session::resume();
        $id = Session::isActive() ? ($_SESSION['user_id'] ?? null) : null;
        if ($id !== null) {
            $user = Users::find((int) $id);
            // Účet mezitím někdo odebral nebo se změnilo heslo -> přihlášení přestává platit.
            if ($user === null || !hash_equals(self::stamp($user), (string) ($_SESSION['auth_stamp'] ?? ''))) {
                unset($_SESSION['user_id'], $_SESSION['auth_stamp']);
                $user = null;
            }
            self::$user = $user;
        }
        if (self::$user === null) {
            $id = self::loginFromRememberCookie();
            if ($id !== null && (self::$user = Users::find($id)) !== null) {
                $_SESSION['auth_stamp'] = self::stamp(self::$user);
            }
        }
        return self::$user;
    }

    /**
     * Otisk hesla uložený v session. Po změně hesla přestane souhlasit, a účet se
     * tak odhlásí na všech ostatních zařízeních.
     */
    private static function stamp(array $user): string
    {
        return substr(hash('sha256', (string) $user['password_hash']), 0, 32);
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    /** Vyžaduje přihlášení; nepřihlášeného pošle na přihlašovací stránku. */
    public static function requireUser(): array
    {
        $user = self::user();
        if ($user === null) {
            if (str_starts_with(self::currentPath(), '/api/')) {
                json_response(['error' => 'Přihlášení vypršelo. Přihlaste se prosím znovu.'], 401);
            }
            redirect('/prihlaseni');
        }
        if ((int) $user['must_change_password'] === 1 && self::currentPath() !== '/zmena-hesla') {
            redirect('/zmena-hesla');
        }
        return $user;
    }

    public static function requirePermission(string $permission): array
    {
        $user = self::requireUser();
        if (!self::can($permission, $user)) {
            throw new HttpError(403, 'K této části nemáte oprávnění.');
        }
        return $user;
    }

    public static function can(string $permission, ?array $user = null): bool
    {
        $user ??= self::user();
        if ($user === null) {
            return false;
        }
        $role = (string) $user['role'];
        if (in_array($role, self::PERMISSIONS[$permission] ?? [], true)) {
            return true;
        }
        // Výjimky, které hostovi/dítěti může povolit správce ve sdílených nastaveních.
        if ($role === 'guest') {
            return match ($permission) {
                'settings.appearance' => Household::get('guest_can_customize') === '1',
                'shopping.delete' => Household::get('guest_shopping_add_only') !== '1',
                default => false,
            };
        }
        return false;
    }

    /**
     * Pokus o přihlášení. Vrací null při úspěchu, jinak text chyby pro uživatele.
     */
    public static function attempt(string $login, string $password, bool $remember): ?string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $since = time() - self::ATTEMPT_WINDOW;
        Database::run('DELETE FROM login_attempts WHERE attempted_at < ?', [$since]);

        $failed = (int) Database::value(
            'SELECT COUNT(*) FROM login_attempts WHERE login = ? AND ip = ? AND attempted_at >= ?',
            [$login, $ip, $since]
        );
        if ($failed >= self::MAX_ATTEMPTS) {
            return 'Příliš mnoho neúspěšných pokusů. Zkuste to znovu za 10 minut.';
        }

        $user = Users::findByLogin($login);
        // Hash se ověřuje i pro neexistující účet, aby odpověď trvala stejně dlouho.
        // (Otisk náhodného, nikde neuloženého řetězce – nejde jím projít.)
        $hash = $user['password_hash'] ?? '$2y$10$BFceprUox8/kc4jgLdTM9.tjkD75dN5lpTcxg0gd253IjMGD7VpCu';
        if (!password_verify($password, $hash) || $user === null) {
            Database::run('INSERT INTO login_attempts (login, ip, attempted_at) VALUES (?, ?, ?)', [mb_substr($login, 0, 190), $ip, time()]);
            return 'Nesprávné přihlašovací jméno nebo heslo.';
        }

        Database::run('DELETE FROM login_attempts WHERE login = ?', [$login]);
        if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
            Users::setPassword((int) $user['id'], $password, (bool) $user['must_change_password']);
            $user = Users::find((int) $user['id']) ?? $user;
        }
        self::login($user, $remember);
        return null;
    }

    public static function login(array $user, bool $remember = false): void
    {
        Session::regenerate();
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['auth_stamp'] = self::stamp($user);
        unset($_SESSION['_csrf']);
        self::$user = $user;
        self::$resolved = true;
        if ($remember) {
            self::issueRememberToken((int) $user['id']);
        }
    }

    public static function logout(): void
    {
        $cookie = $_COOKIE[self::REMEMBER_COOKIE] ?? '';
        if (is_string($cookie) && str_contains($cookie, ':')) {
            Database::run('DELETE FROM remember_tokens WHERE selector = ?', [explode(':', $cookie, 2)[0]]);
        }
        self::setRememberCookie('', time() - 3600);
        Session::destroy();
        self::$user = null;
        self::$resolved = true;
    }

    /** Odhlásí účet ze všech zařízení (po změně hesla nebo odebrání člena). */
    public static function forgetDevices(int $userId): void
    {
        Database::run('DELETE FROM remember_tokens WHERE user_id = ?', [$userId]);
    }

    // --- Trvalé přihlášení ("Zůstat přihlášen") -------------------------------------------

    private static function issueRememberToken(int $userId): void
    {
        $selector = bin2hex(random_bytes(9));
        $validator = bin2hex(random_bytes(32));
        $expires = time() + self::REMEMBER_DAYS * 86400;
        // V databázi je jen otisk – únik databáze neumožní přihlášení.
        Database::run(
            'INSERT INTO remember_tokens (selector, token_hash, user_id, expires_at) VALUES (?, ?, ?, ?)',
            [$selector, hash('sha256', $validator), $userId, $expires]
        );
        self::setRememberCookie($selector . ':' . $validator, $expires);
    }

    private static function loginFromRememberCookie(): ?int
    {
        $cookie = $_COOKIE[self::REMEMBER_COOKIE] ?? '';
        if (!is_string($cookie) || !str_contains($cookie, ':')) {
            return null;
        }
        [$selector, $validator] = explode(':', $cookie, 2);
        $row = Database::row('SELECT * FROM remember_tokens WHERE selector = ?', [$selector]);
        Database::run('DELETE FROM remember_tokens WHERE selector = ? OR expires_at < ?', [$selector, time()]);

        if ($row === null || (int) $row['expires_at'] < time() || !hash_equals($row['token_hash'], hash('sha256', $validator))) {
            self::setRememberCookie('', time() - 3600);
            return null;
        }

        // Token je jednorázový: po použití se vymění za nový.
        Session::regenerate();
        $_SESSION['user_id'] = (int) $row['user_id'];
        self::issueRememberToken((int) $row['user_id']);
        return (int) $row['user_id'];
    }

    private static function setRememberCookie(string $value, int $expires): void
    {
        if (headers_sent()) {
            return;
        }
        setcookie(self::REMEMBER_COOKIE, $value, [
            'expires' => $expires,
            'path' => base_path() . '/',
            'httponly' => true,
            'secure' => is_https(),
            'samesite' => 'Lax',
        ]);
    }

    private static function currentPath(): string
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $base = base_path();
        return $base !== '' && str_starts_with($path, $base) ? (substr($path, strlen($base)) ?: '/') : $path;
    }
}
