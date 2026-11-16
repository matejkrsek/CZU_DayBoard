<?php

declare(strict_types=1);

namespace DayBoard\Widgets;

use DayBoard\Core\Auth;
use DayBoard\Core\Config;
use DayBoard\Core\View;
use DayBoard\Models\Household;
use DayBoard\Models\Shopping;
use DayBoard\Services\CalendarService;
use DayBoard\Services\LocationService;
use DayBoard\Services\NamedayService;
use DayBoard\Services\NewsService;
use DayBoard\Services\TrafficService;
use DayBoard\Services\TransitService;
use DayBoard\Services\WeatherService;

/** Vykreslení obsahu (těla) widgetu ze šablony templates/widgets/{klíč}.php. */
final class WidgetRenderer
{
    /**
     * Chyba jednoho widgetu (např. výpadek externího API) nesmí shodit celou
     * nástěnku – místo obsahu se zobrazí srozumitelná hláška.
     */
    public static function body(string $key, array $user): string
    {
        try {
            return View::partial('widgets/' . $key, self::data($key, $user) + ['user' => $user]);
        } catch (\Throwable $e) {
            error_log('DayBoard widget ' . $key . ': ' . $e->getMessage());
            return View::partial('widgets/_error', [
                'message' => 'Data se teď nepodařilo načíst.',
                'detail' => Config::isProduction() ? null : $e->getMessage(),
            ]);
        }
    }

    /** @return array<string,mixed> */
    private static function data(string $key, array $user): array
    {
        return match ($key) {
            'weather' => ['weather' => WeatherService::forecast()],
            'calendar' => ['calendar' => CalendarService::today($user)],
            'nameday' => ['day' => NamedayService::today()],
            'traffic' => ['traffic' => TrafficService::forUser($user)],
            'transit' => ['transit' => TransitService::forUser($user)],
            'news' => ['news' => NewsService::forUser($user)],
            'shopping' => ['items' => Shopping::items(), 'canDelete' => Auth::can('shopping.delete', $user)],
            'map' => ['locations' => LocationService::members()],
            'radio' => ['stations' => Household::stations()],
            default => throw new \InvalidArgumentException('Neznámý widget: ' . $key),
        };
    }
}
