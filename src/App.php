<?php

declare(strict_types=1);

namespace DayBoard;

use DayBoard\Controllers\ApiController;
use DayBoard\Controllers\AuthController;
use DayBoard\Controllers\DashboardController;
use DayBoard\Controllers\HouseholdController;
use DayBoard\Controllers\PublicController;
use DayBoard\Controllers\SettingsController;
use DayBoard\Core\Config;
use DayBoard\Core\Csrf;
use DayBoard\Core\HttpError;
use DayBoard\Core\Router;
use DayBoard\Core\View;

/** Sestavení aplikace: bezpečnostní hlavičky, routy a zpracování chyb. */
final class App
{
    public static function run(): void
    {
        try {
            self::sendSecurityHeaders();

            $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
            $path = self::path();

            // Jedna stránka = jedna adresa: /funkce/ přesměrujeme na /funkce.
            if ($path !== '/' && str_ends_with($path, '/') && $method === 'GET') {
                redirect(rtrim($path, '/'), 301);
            }
            if ($method === 'POST' && !Csrf::isValid()) {
                throw new HttpError(419, 'Platnost formuláře vypršela. Načtěte stránku znovu a akci zopakujte.');
            }
            if (!self::routes()->dispatch($method, $path)) {
                throw new HttpError(404);
            }
        } catch (HttpError $e) {
            self::renderError($e->status, $e->getMessage());
        } catch (\Throwable $e) {
            error_log('DayBoard: ' . $e);
            self::renderError(500, Config::isProduction() ? '' : $e->getMessage() . ' (' . basename($e->getFile()) . ':' . $e->getLine() . ')');
        }
    }

    private static function routes(): Router
    {
        $router = new Router();

        // Veřejná prezentační část (SEO)
        foreach (['/', '/funkce', '/jak-to-funguje', '/soukromi', '/caste-dotazy', '/navody'] as $page) {
            $router->get($page, static fn () => PublicController::page($page));
        }
        $router->get('/navody/{slug}', static fn (string $slug) => PublicController::page('/navody/' . $slug));
        $router->get('/robots.txt', [PublicController::class, 'robots']);
        $router->get('/sitemap.xml', [PublicController::class, 'sitemap']);

        // Přihlášení
        $router->get('/prihlaseni', [AuthController::class, 'showLogin']);
        $router->post('/prihlaseni', [AuthController::class, 'login']);
        $router->post('/odhlaseni', [AuthController::class, 'logout']);
        $router->get('/zmena-hesla', [AuthController::class, 'showPassword']);
        $router->post('/zmena-hesla', [AuthController::class, 'changePassword']);

        // Aplikace po přihlášení
        $router->get('/nastenka', [DashboardController::class, 'show']);
        $router->get('/nastaveni', [SettingsController::class, 'show']);
        $router->post('/nastaveni', [SettingsController::class, 'save']);
        $router->post('/nastaveni/google/pripojit', [SettingsController::class, 'googleConnect']);
        $router->get('/nastaveni/google/callback', [SettingsController::class, 'googleCallback']);
        $router->post('/nastaveni/google/odpojit', [SettingsController::class, 'googleDisconnect']);
        $router->get('/domacnost', [HouseholdController::class, 'show']);
        $router->post('/domacnost', [HouseholdController::class, 'save']);
        $router->post('/domacnost/clenove', [HouseholdController::class, 'createMember']);
        $router->post('/domacnost/clenove/{id}', [HouseholdController::class, 'updateMember']);
        $router->post('/domacnost/clenove/{id}/odebrat', [HouseholdController::class, 'deleteMember']);

        // JSON API pro JavaScript nástěnky
        $router->get('/api/widgets/{key}', [ApiController::class, 'widget']);
        $router->post('/api/layout', [ApiController::class, 'saveLayout']);
        $router->post('/api/shopping', [ApiController::class, 'shoppingAdd']);
        $router->post('/api/shopping/clear-done', [ApiController::class, 'shoppingClearDone']);
        $router->post('/api/shopping/{id}/toggle', [ApiController::class, 'shoppingToggle']);
        $router->post('/api/shopping/{id}/delete', [ApiController::class, 'shoppingDelete']);
        $router->get('/api/locations', [ApiController::class, 'locations']);
        $router->get('/api/geocode', [ApiController::class, 'geocode']);
        $router->get('/api/radio/search', [ApiController::class, 'radioSearch']);

        return $router;
    }

    /** Cesta požadavku bez základní cesty aplikace a bez parametrů. */
    private static function path(): string
    {
        $path = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
        $base = base_path();
        if ($base !== '' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base));
        }
        return '/' . ltrim($path, '/');
    }

    private static function sendSecurityHeaders(): void
    {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), camera=(), microphone=()');
        // Skripty jen z vlastního serveru; obrázky a zvuk i odjinud (mapové dlaždice,
        // náhledy článků, streamy rádií). Formuláře smí vést jen k nám a na souhlas Googlu.
        header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; "
            . "img-src 'self' data: https:; media-src https: http:; connect-src 'self'; "
            . "frame-ancestors 'none'; base-uri 'self'; form-action 'self' https://accounts.google.com");
        header_remove('X-Powered-By');
    }

    private static function renderError(int $status, string $message): void
    {
        $isApi = str_starts_with(self::path(), '/api/');
        $titles = [
            403 => 'Sem nemáte přístup',
            404 => 'Stránka nenalezena',
            405 => 'Nepodporovaný požadavek',
            419 => 'Platnost formuláře vypršela',
            500 => 'Něco se pokazilo',
        ];
        $title = $titles[$status] ?? 'Chyba';
        $defaults = [
            403 => 'K této části DayBoardu nemá váš účet oprávnění.',
            404 => 'Na této adrese nic není. Možná se stránka přesunula, nebo je v odkazu překlep.',
            500 => 'Na serveru došlo k chybě. Zkuste to prosím za chvíli znovu.',
        ];
        $message = $message !== '' ? $message : ($defaults[$status] ?? '');

        if ($isApi) {
            json_response(['error' => $message !== '' ? $message : $title], $status);
        }

        if (!headers_sent()) {
            header('X-Robots-Tag: noindex');
        }
        View::send(View::render('site/error', [
            'page' => [
                'title' => $title . ' | DayBoard', 'description' => '', 'nav' => '',
                'h1' => $title, 'robots' => 'noindex',
            ],
            'path' => null,
            'status' => $status,
            'message' => $message,
        ], 'site'), $status);
    }
}
