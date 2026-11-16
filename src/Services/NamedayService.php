<?php

declare(strict_types=1);

namespace DayBoard\Services;

use DayBoard\Core\Cache;
use DayBoard\Core\Http;
use DayBoard\Support\Czech;

/** Svátek a denní informace. Jmeniny a státní svátky dodává svatkyapi.cz. */
final class NamedayService
{
    /** @return array<string,mixed> */
    public static function today(): array
    {
        $today = new \DateTimeImmutable('today');
        $info = [
            'weekday' => Czech::ucfirst(Czech::DAYS[(int) $today->format('w')]),
            'date' => Czech::dayMonth($today) . ' ' . $today->format('Y'),
            'week' => (int) $today->format('W'),
            'dayOfYear' => (int) $today->format('z') + 1,
            'name' => null,
            'holiday' => null,
            'tomorrow' => null,
            'sunrise' => null,
            'sunset' => null,
        ];

        // Datum a číslo týdne umíme sami; když API neodpoví, widget ukáže aspoň to.
        try {
            $day = self::day($today);
            $info['name'] = $day['name'];
            $info['holiday'] = $day['holiday'];
            $info['tomorrow'] = self::day($today->modify('+1 day'))['name'];
        } catch (\Throwable) {
            // jmeniny se nepodařilo načíst
        }

        try {
            $weather = WeatherService::forecast();
            $info['sunrise'] = $weather['sunrise'] ?: null;
            $info['sunset'] = $weather['sunset'] ?: null;
        } catch (\Throwable) {
            // východ a západ slunce jsou jen doplněk
        }

        return $info;
    }

    /** @return array{name:?string,holiday:?string} */
    private static function day(\DateTimeImmutable $date): array
    {
        $iso = $date->format('Y-m-d');
        return Cache::remember('nameday:' . $iso, 2 * 86400, static function () use ($iso): array {
            $raw = Http::getJson('https://svatkyapi.cz/api/day/' . $iso);
            return [
                'name' => isset($raw['name']) && $raw['name'] !== '' ? (string) $raw['name'] : null,
                'holiday' => !empty($raw['isHoliday']) ? (string) ($raw['holidayName'] ?? '') : null,
            ];
        });
    }
}
