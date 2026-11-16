<?php

declare(strict_types=1);

namespace DayBoard\Controllers;

use DayBoard\Core\HttpError;
use DayBoard\Models\Household;
use DayBoard\Models\Layouts;
use DayBoard\Models\Shopping;
use DayBoard\Services\GeocodingService;
use DayBoard\Services\LocationService;
use DayBoard\Services\RadioService;
use DayBoard\Widgets\WidgetRenderer;

/**
 * JSON API, které volá JavaScript nástěnky. Každá akce znovu ověřuje přihlášení
 * i oprávnění – skrytí tlačítka v prohlížeči není zabezpečení.
 */
final class ApiController extends Controller
{
    /** Obsah widgetu jako HTML (načítání AJAXem a průběžná obnova). */
    public static function widget(string $key): void
    {
        $user = self::user('dashboard.view');
        self::requireWidget($user, $key);
        json_response(['html' => WidgetRenderer::body($key, $user)]);
    }

    public static function saveLayout(): void
    {
        $user = self::user('layout.edit');
        try {
            $items = Layouts::validate(self::body()['items'] ?? null, Household::allowedWidgets($user));
        } catch (\InvalidArgumentException $e) {
            json_response(['error' => $e->getMessage()], 422);
        }
        Layouts::save((int) $user['id'], $items);
        flash('success', 'Rozložení nástěnky bylo uloženo.');
        json_response(['ok' => true]);
    }

    // --- Sdílený nákupní seznam -----------------------------------------------------------

    public static function shoppingAdd(): void
    {
        $user = self::user('shopping.add');
        self::requireWidget($user, 'shopping');
        if (!Shopping::add((string) (self::body()['name'] ?? ''), (int) $user['id'])) {
            json_response(['error' => 'Položku se nepodařilo přidat. Zadejte její název.'], 422);
        }
        self::shoppingResponse($user);
    }

    public static function shoppingToggle(string $id): void
    {
        $user = self::user('shopping.add');
        self::requireWidget($user, 'shopping');
        Shopping::toggle((int) $id);
        self::shoppingResponse($user);
    }

    public static function shoppingDelete(string $id): void
    {
        // Host/dítě smí položky jen přidávat (pokud správce neurčí jinak).
        $user = self::user('shopping.delete');
        self::requireWidget($user, 'shopping');
        Shopping::delete((int) $id);
        self::shoppingResponse($user);
    }

    public static function shoppingClearDone(): void
    {
        $user = self::user('shopping.delete');
        self::requireWidget($user, 'shopping');
        Shopping::clearDone();
        self::shoppingResponse($user);
    }

    private static function shoppingResponse(array $user): never
    {
        json_response(['html' => WidgetRenderer::body('shopping', $user)]);
    }

    // --- Mapa, lokalita, rádio ------------------------------------------------------------

    public static function locations(): void
    {
        $user = self::user('dashboard.view');
        self::requireWidget($user, 'map');
        json_response(LocationService::members());
    }

    public static function geocode(): void
    {
        self::user('household.manage');
        self::external(static fn (): array => ['results' => GeocodingService::search((string) ($_GET['q'] ?? ''))]);
    }

    public static function radioSearch(): void
    {
        self::user('household.manage');
        self::external(static fn (): array => ['results' => RadioService::search((string) ($_GET['q'] ?? ''))]);
    }

    // --- Pomocné metody -------------------------------------------------------------------

    /** Widget musí být uživateli povolen správcem. */
    private static function requireWidget(array $user, string $key): void
    {
        if (!in_array($key, Household::allowedWidgets($user), true)) {
            throw new HttpError(403, 'Tento widget nemáte povolený.');
        }
    }

    /** @return array<mixed> Tělo požadavku ve formátu JSON. */
    private static function body(): array
    {
        $data = json_decode((string) file_get_contents('php://input'), true);
        return is_array($data) ? $data : [];
    }

    /** Volání externí služby: výpadek vrátíme jako srozumitelnou chybu 502. */
    private static function external(callable $producer): never
    {
        try {
            json_response($producer());
        } catch (\Throwable $e) {
            error_log('DayBoard API: ' . $e->getMessage());
            json_response(['error' => 'Externí služba teď neodpovídá. Zkuste to prosím za chvíli.'], 502);
        }
    }
}
