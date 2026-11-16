<?php

declare(strict_types=1);

namespace DayBoard\Services;

use DayBoard\Core\Cache;
use DayBoard\Core\Http;
use DayBoard\Models\Household;
use DayBoard\Support\Czech;

/**
 * Počasí pro lokalitu domácnosti z Open-Meteo (https://open-meteo.com).
 * API je zdarma a bez klíče; odpověď se cachuje na 15 minut.
 */
final class WeatherService
{
    /** Kódy počasí WMO -> [popis, ikona pro den, ikona pro noc]. */
    private const CODES = [
        0 => ['Jasno', 'sun', 'moon'],
        1 => ['Skoro jasno', 'sun', 'moon'],
        2 => ['Polojasno', 'cloud-sun', 'cloud-moon'],
        3 => ['Zataženo', 'cloud', 'cloud'],
        45 => ['Mlha', 'fog', 'fog'],
        48 => ['Mrznoucí mlha', 'fog', 'fog'],
        51 => ['Slabé mrholení', 'drizzle', 'drizzle'],
        53 => ['Mrholení', 'drizzle', 'drizzle'],
        55 => ['Silné mrholení', 'drizzle', 'drizzle'],
        56 => ['Mrznoucí mrholení', 'drizzle', 'drizzle'],
        57 => ['Mrznoucí mrholení', 'drizzle', 'drizzle'],
        61 => ['Slabý déšť', 'rain', 'rain'],
        63 => ['Déšť', 'rain', 'rain'],
        65 => ['Silný déšť', 'rain', 'rain'],
        66 => ['Mrznoucí déšť', 'rain', 'rain'],
        67 => ['Mrznoucí déšť', 'rain', 'rain'],
        71 => ['Slabé sněžení', 'snow', 'snow'],
        73 => ['Sněžení', 'snow', 'snow'],
        75 => ['Husté sněžení', 'snow', 'snow'],
        77 => ['Sněhová zrna', 'snow', 'snow'],
        80 => ['Přeháňky', 'rain', 'rain'],
        81 => ['Přeháňky', 'rain', 'rain'],
        82 => ['Silné přeháňky', 'rain', 'rain'],
        85 => ['Sněhové přeháňky', 'snow', 'snow'],
        86 => ['Sněhové přeháňky', 'snow', 'snow'],
        95 => ['Bouřka', 'storm', 'storm'],
        96 => ['Bouřka s kroupami', 'storm', 'storm'],
        99 => ['Bouřka s kroupami', 'storm', 'storm'],
    ];

    /**
     * @return array{location:string,temp:int,feels:int,wind:int,desc:string,icon:string,sunrise:string,sunset:string,days:list<array<string,mixed>>}
     */
    public static function forecast(): array
    {
        $location = Household::location();
        $timezone = Household::get('timezone');
        $cacheKey = sprintf('weather:%.3f:%.3f:%s', $location['lat'], $location['lon'], $timezone);

        $data = Cache::remember($cacheKey, 900, static fn (): array => self::fetch($location['lat'], $location['lon'], $timezone));
        return ['location' => $location['name']] + $data;
    }

    /** @return array<string,mixed> */
    private static function fetch(float $lat, float $lon, string $timezone): array
    {
        $raw = Http::getJson('https://api.open-meteo.com/v1/forecast?' . http_build_query([
            'latitude' => $lat,
            'longitude' => $lon,
            'current' => 'temperature_2m,apparent_temperature,weather_code,wind_speed_10m,is_day',
            'daily' => 'weather_code,temperature_2m_max,temperature_2m_min,sunrise,sunset',
            'timezone' => $timezone,
            'forecast_days' => 5,
        ]));

        $current = $raw['current'] ?? null;
        $daily = $raw['daily'] ?? null;
        if (!is_array($current) || !is_array($daily)) {
            throw new \RuntimeException('Neočekávaná odpověď služby počasí.');
        }

        $now = self::describe((int) $current['weather_code'], (int) ($current['is_day'] ?? 1) === 1);
        $days = [];
        foreach ($daily['time'] as $i => $date) {
            if ($i === 0) {
                continue; // dnešek ukazuje aktuální stav
            }
            $info = self::describe((int) $daily['weather_code'][$i]);
            $days[] = [
                'label' => Czech::DAYS_SHORT[(int) (new \DateTimeImmutable($date))->format('w')],
                'max' => (int) round((float) $daily['temperature_2m_max'][$i]),
                'min' => (int) round((float) $daily['temperature_2m_min'][$i]),
                'desc' => $info['desc'],
                'icon' => $info['icon'],
            ];
        }

        return [
            'temp' => (int) round((float) $current['temperature_2m']),
            'feels' => (int) round((float) $current['apparent_temperature']),
            'wind' => (int) round((float) $current['wind_speed_10m']),
            'desc' => $now['desc'],
            'icon' => $now['icon'],
            'sunrise' => substr((string) ($daily['sunrise'][0] ?? ''), 11, 5),
            'sunset' => substr((string) ($daily['sunset'][0] ?? ''), 11, 5),
            'days' => $days,
        ];
    }

    /** @return array{desc:string,icon:string} */
    public static function describe(int $code, bool $isDay = true): array
    {
        $entry = self::CODES[$code] ?? ['Neznámé počasí', 'cloud', 'cloud'];
        return ['desc' => $entry[0], 'icon' => $isDay ? $entry[1] : $entry[2]];
    }
}
