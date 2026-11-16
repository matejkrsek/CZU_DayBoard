<?php

declare(strict_types=1);

namespace DayBoard\Services;

use DayBoard\Core\Cache;
use DayBoard\Core\Config;
use DayBoard\Core\Http;
use DayBoard\Models\Users;

/**
 * Nejbližší odjezdy MHD z oblíbených zastávek – Golemio API (Pražská integrovaná doprava).
 * Dokumentace: https://api.golemio.cz/pid/docs/openapi/ (endpoint /v2/pid/departureboards).
 */
final class TransitService
{
    private const PER_STOP = 2;

    public static function isConfigured(): bool
    {
        return (string) Config::get('GOLEMIO_API_KEY', '') !== '';
    }

    /**
     * @return array{state:string,demo:bool,departures:list<array<string,mixed>>}
     *         state: ok | empty (žádné zastávky) | unconfigured (chybí API klíč)
     */
    public static function forUser(array $user): array
    {
        $stops = Users::stops((int) $user['id']);
        if ($stops === []) {
            return ['state' => 'empty', 'demo' => false, 'departures' => []];
        }
        if (!self::isConfigured()) {
            return Config::isDemo()
                ? ['state' => 'ok', 'demo' => true, 'departures' => self::demo($stops)]
                : ['state' => 'unconfigured', 'demo' => false, 'departures' => []];
        }

        $departures = Cache::remember('transit:' . implode('|', $stops), 30, static fn (): array => self::fetch($stops));

        // Minuty do odjezdu se počítají až teď, aby seděly i u odpovědi z cache.
        $now = time();
        $result = [];
        foreach ($departures as $departure) {
            $minutes = (int) floor(($departure['time'] - $now) / 60);
            if ($minutes >= 0) {
                $result[] = $departure + ['minutes' => $minutes];
            }
        }
        return ['state' => 'ok', 'demo' => false, 'departures' => self::limitPerStop($result)];
    }

    /**
     * @param list<string> $stops
     * @return list<array<string,mixed>>
     */
    private static function fetch(array $stops): array
    {
        // Pole se v dotazu posílají ve tvaru names[]=...&names[]=... (vyžaduje to Golemio).
        $query = http_build_query([
            'names' => $stops,
            'minutesAfter' => 90,
            'limit' => 60,
            'order' => 'real',
            'preferredTimezone' => 'Europe_Prague',
        ]);
        $query = (string) preg_replace('~%5B\d+%5D~', '%5B%5D', $query); // names[0] -> names[]
        $raw = Http::getJson(
            'https://api.golemio.cz/v2/pid/departureboards?' . $query,
            ['X-Access-Token' => (string) Config::get('GOLEMIO_API_KEY')]
        );

        $stopNames = [];
        foreach ($raw['stops'] ?? [] as $stop) {
            $stopNames[$stop['stop_id']] = $stop['stop_name'];
        }

        $departures = [];
        foreach ($raw['departures'] ?? [] as $departure) {
            if (!empty($departure['trip']['is_canceled'])) {
                continue;
            }
            $time = strtotime((string) ($departure['departure_timestamp']['predicted'] ?? $departure['departure_timestamp']['scheduled'] ?? ''));
            if ($time === false) {
                continue;
            }
            $departures[] = [
                'line' => (string) ($departure['route']['short_name'] ?? '?'),
                'type' => self::vehicle((int) ($departure['route']['type'] ?? 3)),
                'headsign' => (string) ($departure['trip']['headsign'] ?? ''),
                'stop' => (string) ($stopNames[$departure['stop']['id'] ?? ''] ?? ''),
                'time' => $time,
                'delay' => !empty($departure['delay']['is_available']) ? (int) ($departure['delay']['minutes'] ?? 0) : 0,
            ];
        }
        usort($departures, static fn (array $a, array $b): int => $a['time'] <=> $b['time']);
        return $departures;
    }

    /** Typ linky podle GTFS route_type. */
    private static function vehicle(int $type): string
    {
        return match ($type) {
            0 => 'tram',
            1 => 'metro',
            2 => 'train',
            4 => 'ferry',
            default => 'bus',
        };
    }

    /**
     * Z každé zastávky nejvýše několik nejbližších spojů, celkově seřazeno podle času.
     *
     * @param list<array<string,mixed>> $departures
     * @return list<array<string,mixed>>
     */
    private static function limitPerStop(array $departures): array
    {
        $counts = [];
        $result = [];
        foreach ($departures as $departure) {
            $counts[$departure['stop']] = ($counts[$departure['stop']] ?? 0) + 1;
            if ($counts[$departure['stop']] <= self::PER_STOP) {
                $result[] = $departure;
            }
        }
        return $result;
    }

    /**
     * Ukázkové odjezdy pro vyzkoušení bez API klíče. Linky jsou smyšlené, časy se
     * odvozují od aktuálního času, aby se widget choval jako živý.
     *
     * @param list<string> $stops
     * @return list<array<string,mixed>>
     */
    private static function demo(array $stops): array
    {
        $lines = [
            ['A', 'metro', 'Depo Hostivař', 4], ['22', 'tram', 'Bílá Hora', 6], ['B', 'metro', 'Zličín', 5],
            ['C', 'metro', 'Háje', 4], ['9', 'tram', 'Sídliště Řepy', 7], ['136', 'bus', 'Jižní Město', 10],
        ];
        $now = time();
        $departures = [];
        foreach ($stops as $index => $stop) {
            $seed = crc32($stop);
            foreach ([0, 1] as $n) {
                [$line, $type, $headsign, $interval] = $lines[($seed + $index + $n * 3) % count($lines)];
                // Spoj jezdí v pravidelném intervalu; spočítáme, za kolik minut pojede příští.
                $phase = ($seed % ($interval * 60)) + $n * 95;
                $wait = $interval * 60 - (($now + $phase) % ($interval * 60));
                $departures[] = [
                    'line' => $line, 'type' => $type, 'headsign' => $headsign, 'stop' => $stop,
                    'time' => $now + $wait, 'delay' => 0, 'minutes' => intdiv($wait, 60),
                ];
            }
        }
        usort($departures, static fn (array $a, array $b): int => $a['time'] <=> $b['time']);
        return $departures;
    }
}
