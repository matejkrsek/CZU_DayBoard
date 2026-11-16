<?php

declare(strict_types=1);

namespace DayBoard\Services;

use DayBoard\Core\Cache;
use DayBoard\Core\Config;
use DayBoard\Core\Http;
use DayBoard\Models\Household;
use DayBoard\Models\Users;

/**
 * Aktuální doba jízdy autem do přednastavených cílů se zohledněním provozu.
 * TomTom Routing API počítá trasu, TomTom Geocoding API převádí adresy na souřadnice.
 */
final class TrafficService
{
    public static function isConfigured(): bool
    {
        return (string) Config::get('TOMTOM_API_KEY', '') !== '';
    }

    /**
     * @return array{state:string,demo:bool,origin:string,routes:list<array<string,mixed>>}
     *         state: ok | empty (žádné cíle) | unconfigured (chybí API klíč)
     */
    public static function forUser(array $user): array
    {
        $destinations = Users::destinations((int) $user['id']);
        $home = Household::location();
        $origin = ['label' => 'Domov', 'lat' => $home['lat'], 'lon' => $home['lon'], 'address' => $home['name']];
        $targets = $destinations;

        // Uživatel může nechat cesty počítat z některého svého cíle (např. z práce) –
        // pak se mezi cíle doplní cesta domů.
        foreach ($destinations as $index => $destination) {
            if ((int) $destination['id'] === (int) ($user['origin_destination_id'] ?? 0)) {
                $origin = $destination;
                unset($targets[$index]);
                array_unshift($targets, ['id' => 0, 'label' => 'Domov', 'address' => $home['name'], 'lat' => $home['lat'], 'lon' => $home['lon']]);
                break;
            }
        }
        $targets = array_values($targets);

        $base = ['demo' => false, 'origin' => (string) $origin['label'], 'routes' => []];
        if ($targets === []) {
            return ['state' => 'empty'] + $base;
        }
        if (!self::isConfigured()) {
            return Config::isDemo()
                ? ['state' => 'ok', 'demo' => true, 'routes' => self::demo($targets)] + $base
                : ['state' => 'unconfigured'] + $base;
        }

        $originPoint = self::coordinates($origin);
        $routes = [];
        foreach ($targets as $target) {
            $point = self::coordinates($target);
            $routes[] = $originPoint === null || $point === null
                ? ['label' => $target['label'], 'error' => 'Adresu se nepodařilo najít']
                : ['label' => $target['label']] + self::route($originPoint, $point);
        }
        return ['state' => 'ok', 'routes' => self::withShare($routes)] + $base;
    }

    /**
     * Souřadnice místa; chybějící se dohledají podle adresy a uloží k cíli.
     *
     * @return array{0:float,1:float}|null
     */
    private static function coordinates(array $place): ?array
    {
        if ($place['lat'] !== null && $place['lon'] !== null) {
            return [(float) $place['lat'], (float) $place['lon']];
        }
        try {
            $point = Cache::remember('tomtom-geocode:' . mb_strtolower((string) $place['address']), 30 * 86400, static function () use ($place): array {
                $raw = Http::getJson(
                    'https://api.tomtom.com/search/2/geocode/' . rawurlencode((string) $place['address']) . '.json?'
                    . http_build_query(['key' => Config::get('TOMTOM_API_KEY'), 'limit' => 1, 'countrySet' => 'CZ', 'language' => 'cs-CZ'])
                );
                $position = $raw['results'][0]['position'] ?? null;
                if (!is_array($position)) {
                    throw new \RuntimeException('Adresa nenalezena.');
                }
                return [(float) $position['lat'], (float) $position['lon']];
            });
        } catch (\Throwable) {
            return null;
        }
        if (!empty($place['id'])) {
            Users::setDestinationCoordinates((int) $place['id'], $point[0], $point[1]);
        }
        return $point;
    }

    /**
     * @param array{0:float,1:float} $from
     * @param array{0:float,1:float} $to
     * @return array<string,mixed>
     */
    private static function route(array $from, array $to): array
    {
        $key = sprintf('tomtom-route:%.4f,%.4f:%.4f,%.4f', $from[0], $from[1], $to[0], $to[1]);
        try {
            return Cache::remember($key, 300, static function () use ($from, $to): array {
                $raw = Http::getJson(
                    sprintf('https://api.tomtom.com/routing/1/calculateRoute/%F,%F:%F,%F/json?', $from[0], $from[1], $to[0], $to[1])
                    . http_build_query(['key' => Config::get('TOMTOM_API_KEY'), 'traffic' => 'true', 'travelMode' => 'car', 'routeType' => 'fastest'])
                );
                $summary = $raw['routes'][0]['summary'] ?? null;
                if (!is_array($summary)) {
                    throw new \RuntimeException('Trasu se nepodařilo spočítat.');
                }
                $minutes = (int) ceil($summary['travelTimeInSeconds'] / 60);
                $delay = (int) round(($summary['trafficDelayInSeconds'] ?? 0) / 60);
                return [
                    'minutes' => $minutes,
                    'delay' => $delay,
                    'km' => round($summary['lengthInMeters'] / 1000, 1),
                    'level' => self::level($minutes, $delay),
                ];
            });
        } catch (\Throwable) {
            return ['error' => 'Trasu se nepodařilo spočítat'];
        }
    }

    /** Hustota provozu podle podílu zdržení na celkové době jízdy. */
    private static function level(int $minutes, int $delay): string
    {
        $ratio = $minutes > 0 ? $delay / $minutes : 0;
        return $ratio >= 0.3 ? 'jam' : ($ratio >= 0.12 ? 'slow' : 'ok');
    }

    /**
     * Doplní délku proužku: podíl doby jízdy vůči nejdelší cestě (v procentech).
     *
     * @param list<array<string,mixed>> $routes
     * @return list<array<string,mixed>>
     */
    private static function withShare(array $routes): array
    {
        $longest = max(1, ...array_map(static fn (array $route): int => (int) ($route['minutes'] ?? 0), $routes));
        return array_map(
            static fn (array $route): array => $route + ['share' => (int) round(100 * ($route['minutes'] ?? 0) / $longest)],
            $routes
        );
    }

    /**
     * Ukázková data pro vyzkoušení bez API klíče: doba jízdy se odhadne ze vzdálenosti
     * vzdušnou čarou (u cíle bez souřadnic z adresy) a ve špičce (7–9 a 15–18 h) narůstá.
     *
     * @param list<array<string,mixed>> $targets
     * @return list<array<string,mixed>>
     */
    private static function demo(array $targets): array
    {
        $home = Household::location();
        $hour = (int) date('G');
        $rush = ($hour >= 7 && $hour < 9) || ($hour >= 15 && $hour < 18);
        $routes = [];
        foreach ($targets as $target) {
            $km = $target['lat'] !== null && $target['lon'] !== null
                ? 1.3 * self::distanceKm($home['lat'], $home['lon'], (float) $target['lat'], (float) $target['lon'])
                : 4 + crc32((string) $target['address']) % 22;
            $base = (int) round(6 + $km * 1.6);
            $delay = $rush ? (int) round($base * (0.15 + (crc32((string) $target['label']) % 20) / 100)) : (int) (date('i') % 3);
            $routes[] = [
                'label' => $target['label'],
                'minutes' => $base + $delay,
                'delay' => $delay,
                'km' => round($km, 1),
                'level' => self::level($base + $delay, $delay),
            ];
        }
        return self::withShare($routes);
    }

    /** Vzdálenost dvou bodů vzdušnou čarou v kilometrech (haversinův vzorec). */
    private static function distanceKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $a = sin(deg2rad($lat2 - $lat1) / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin(deg2rad($lon2 - $lon1) / 2) ** 2;
        return 6371 * 2 * asin(min(1.0, sqrt($a)));
    }
}
