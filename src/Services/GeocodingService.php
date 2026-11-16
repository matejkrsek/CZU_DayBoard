<?php

declare(strict_types=1);

namespace DayBoard\Services;

use DayBoard\Core\Cache;
use DayBoard\Core\Http;

/** Vyhledání obce podle názvu (Open-Meteo Geocoding API, bez klíče). */
final class GeocodingService
{
    /** @return list<array{name:string,label:string,lat:float,lon:float}> */
    public static function search(string $query): array
    {
        $query = trim($query);
        if (mb_strlen($query) < 2) {
            return [];
        }

        return Cache::remember('geocode:' . mb_strtolower($query), 7 * 86400, static function () use ($query): array {
            $raw = Http::getJson('https://geocoding-api.open-meteo.com/v1/search?' . http_build_query([
                'name' => $query, 'count' => 6, 'language' => 'cs', 'format' => 'json',
            ]));

            $results = [];
            foreach ($raw['results'] ?? [] as $place) {
                $parts = array_unique(array_filter([$place['name'] ?? '', $place['admin1'] ?? '', $place['country'] ?? '']));
                $results[] = [
                    'name' => (string) $place['name'],
                    'label' => implode(', ', $parts),
                    'lat' => round((float) $place['latitude'], 5),
                    'lon' => round((float) $place['longitude'], 5),
                ];
            }
            return $results;
        });
    }
}
