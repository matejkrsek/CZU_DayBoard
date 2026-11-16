<?php

declare(strict_types=1);

namespace DayBoard\Services;

use DayBoard\Core\Cache;
use DayBoard\Core\Http;

/**
 * Vyhledávání internetových rádií v otevřeném katalogu Radio Browser
 * (https://www.radio-browser.info, bez klíče). Katalog běží na několika
 * rovnocenných serverech – když jeden neodpoví, zkusí se další.
 */
final class RadioService
{
    private const SERVERS = ['de1.api.radio-browser.info', 'de2.api.radio-browser.info', 'all.api.radio-browser.info'];

    /** @return list<array{name:string,stream_url:string,station_uuid:string,info:string}> */
    public static function search(string $query): array
    {
        $query = trim($query);
        if (mb_strlen($query) < 2) {
            return [];
        }

        return Cache::remember('radio-search:' . mb_strtolower($query), 3600, static function () use ($query): array {
            $params = http_build_query([
                'name' => $query, 'limit' => 12, 'hidebroken' => 'true', 'order' => 'clickcount', 'reverse' => 'true',
            ]);
            $raw = null;
            $lastError = null;
            foreach (self::SERVERS as $server) {
                try {
                    $raw = Http::getJson('https://' . $server . '/json/stations/search?' . $params, [], 5);
                    break;
                } catch (\Throwable $e) {
                    $lastError = $e;
                }
            }
            if ($raw === null) {
                throw $lastError ?? new \RuntimeException('Katalog rádií neodpovídá.');
            }

            $stations = [];
            foreach ($raw as $station) {
                $url = (string) ($station['url_resolved'] ?? $station['url'] ?? '');
                if (!self::isStreamUrl($url)) {
                    continue;
                }
                $details = array_filter([
                    (string) ($station['country'] ?? ''),
                    (string) ($station['codec'] ?? ''),
                    !empty($station['bitrate']) ? $station['bitrate'] . ' kb/s' : '',
                ]);
                $stations[] = [
                    'name' => mb_substr(trim((string) ($station['name'] ?? '')), 0, 60),
                    'stream_url' => $url,
                    'station_uuid' => (string) ($station['stationuuid'] ?? ''),
                    'info' => implode(' · ', $details),
                ];
            }
            return $stations;
        });
    }

    public static function isStreamUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false && preg_match('~^https?://~i', $url) === 1 && strlen($url) <= 500;
    }
}
