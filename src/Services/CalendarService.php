<?php

declare(strict_types=1);

namespace DayBoard\Services;

use DayBoard\Core\Cache;
use DayBoard\Core\Config;
use DayBoard\Core\Crypto;
use DayBoard\Core\Http;
use DayBoard\Core\HttpException;
use DayBoard\Models\Users;
use DayBoard\Support\Czech;

/**
 * Dnešní program z Google Kalendáře uživatele (Google Calendar API, OAuth 2.0).
 *
 * Postup: uživatel v Osobním nastavení klikne na „Připojit účet Google“, Google ho
 * po souhlasu vrátí na /nastaveni/google/callback s jednorázovým kódem a ten se
 * vymění za tokeny. Tokeny jsou v databázi uložené šifrovaně; aplikace žádá jen
 * o čtení kalendáře.
 */
final class CalendarService
{
    private const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const REVOKE_URL = 'https://oauth2.googleapis.com/revoke';
    private const EVENTS_URL = 'https://www.googleapis.com/calendar/v3/calendars/primary/events';
    private const SCOPES = 'openid email https://www.googleapis.com/auth/calendar.readonly';

    public static function isConfigured(): bool
    {
        return (string) Config::get('GOOGLE_CLIENT_ID', '') !== '' && (string) Config::get('GOOGLE_CLIENT_SECRET', '') !== '';
    }

    public static function redirectUri(): string
    {
        return absolute_url('/nastaveni/google/callback');
    }

    /** Adresa, na které uživatel udělí souhlas. $state chrání před podvržením odpovědi. */
    public static function authUrl(string $state): string
    {
        return self::AUTH_URL . '?' . http_build_query([
            'client_id' => Config::get('GOOGLE_CLIENT_ID'),
            'redirect_uri' => self::redirectUri(),
            'response_type' => 'code',
            'scope' => self::SCOPES,
            'access_type' => 'offline',   // chceme i refresh token
            'prompt' => 'consent',
            'state' => $state,
        ]);
    }

    /** Vymění jednorázový kód za tokeny a uloží je k uživateli. */
    public static function connect(int $userId, string $code): void
    {
        $tokens = Http::postForm(self::TOKEN_URL, [
            'code' => $code,
            'client_id' => (string) Config::get('GOOGLE_CLIENT_ID'),
            'client_secret' => (string) Config::get('GOOGLE_CLIENT_SECRET'),
            'redirect_uri' => self::redirectUri(),
            'grant_type' => 'authorization_code',
        ]);
        if (empty($tokens['access_token']) || empty($tokens['refresh_token'])) {
            throw new \RuntimeException('Google nevrátil přístupové tokeny.');
        }

        Users::update($userId, [
            'google_email' => self::emailFromIdToken((string) ($tokens['id_token'] ?? '')),
            'google_access_token' => Crypto::encrypt((string) $tokens['access_token']),
            'google_refresh_token' => Crypto::encrypt((string) $tokens['refresh_token']),
            'google_token_expires' => time() + (int) ($tokens['expires_in'] ?? 3600),
        ]);
        Cache::forget('calendar:' . $userId . ':' . date('Y-m-d'));
    }

    /** Odpojí účet: zruší souhlas u Googlu a smaže tokeny. */
    public static function disconnect(array $user): void
    {
        $token = Crypto::decrypt($user['google_refresh_token'] ?? null);
        if ($token !== null) {
            try {
                Http::postForm(self::REVOKE_URL, ['token' => $token]);
            } catch (\Throwable) {
                // Souhlas už mohl být zrušen na straně Googlu – tokeny smažeme tak jako tak.
            }
        }
        self::clearTokens((int) $user['id']);
    }

    /**
     * @return array{state:string,demo:bool,events:list<array<string,mixed>>}
     *         state: ok | disconnected | unconfigured | expired
     */
    public static function today(array $user): array
    {
        $connected = !empty($user['google_refresh_token']);
        if (!$connected) {
            if (Config::isDemo()) {
                return ['state' => 'ok', 'demo' => true, 'events' => self::annotate(self::demo())];
            }
            return ['state' => self::isConfigured() ? 'disconnected' : 'unconfigured', 'demo' => false, 'events' => []];
        }

        try {
            $events = Cache::remember(
                'calendar:' . $user['id'] . ':' . date('Y-m-d'),
                300,
                static fn (): array => self::fetch($user)
            );
        } catch (HttpException $e) {
            // 400/401 = souhlas byl odvolán nebo token přestal platit; účet je třeba připojit znovu.
            if (in_array($e->status, [400, 401], true)) {
                self::clearTokens((int) $user['id']);
                return ['state' => 'expired', 'demo' => false, 'events' => []];
            }
            throw $e;
        }
        return ['state' => 'ok', 'demo' => false, 'events' => self::annotate($events)];
    }

    /** @return list<array<string,mixed>> */
    private static function fetch(array $user): array
    {
        $dayStart = new \DateTimeImmutable('today');
        $raw = Http::getJson(self::EVENTS_URL . '?' . http_build_query([
            'timeMin' => $dayStart->format(DATE_RFC3339),
            'timeMax' => $dayStart->modify('+1 day')->format(DATE_RFC3339),
            'singleEvents' => 'true',    // opakované události rozepsat na jednotlivé výskyty
            'orderBy' => 'startTime',
            'maxResults' => 25,
        ]), ['Authorization' => 'Bearer ' . self::accessToken($user)]);

        $events = [];
        foreach ($raw['items'] ?? [] as $item) {
            if (($item['status'] ?? '') === 'cancelled') {
                continue;
            }
            $allDay = isset($item['start']['date']);
            $start = strtotime((string) ($item['start']['dateTime'] ?? $item['start']['date'] ?? ''));
            $end = strtotime((string) ($item['end']['dateTime'] ?? $item['end']['date'] ?? ''));
            if ($start === false) {
                continue;
            }
            $events[] = [
                'title' => trim((string) ($item['summary'] ?? '')) ?: '(bez názvu)',
                'location' => trim((string) ($item['location'] ?? '')),
                'allDay' => $allDay,
                'start' => $start,
                'end' => $end === false ? $start : $end,
            ];
        }
        return $events;
    }

    /** Platný přístupový token; po vypršení se obnoví pomocí refresh tokenu. */
    private static function accessToken(array $user): string
    {
        $access = Crypto::decrypt($user['google_access_token'] ?? null);
        if ($access !== null && (int) $user['google_token_expires'] > time() + 60) {
            return $access;
        }

        $refresh = Crypto::decrypt($user['google_refresh_token'] ?? null);
        if ($refresh === null) {
            throw new HttpException('Chybí refresh token.', 401);
        }
        $tokens = Http::postForm(self::TOKEN_URL, [
            'refresh_token' => $refresh,
            'client_id' => (string) Config::get('GOOGLE_CLIENT_ID'),
            'client_secret' => (string) Config::get('GOOGLE_CLIENT_SECRET'),
            'grant_type' => 'refresh_token',
        ]);
        if (empty($tokens['access_token'])) {
            throw new HttpException('Token se nepodařilo obnovit.', 401);
        }
        Users::update((int) $user['id'], [
            'google_access_token' => Crypto::encrypt((string) $tokens['access_token']),
            'google_token_expires' => time() + (int) ($tokens['expires_in'] ?? 3600),
        ]);
        return (string) $tokens['access_token'];
    }

    private static function clearTokens(int $userId): void
    {
        Users::update($userId, [
            'google_email' => null, 'google_access_token' => null,
            'google_refresh_token' => null, 'google_token_expires' => null,
        ]);
        Cache::forget('calendar:' . $userId . ':' . date('Y-m-d'));
    }

    /**
     * E-mail z ID tokenu (JWT). Token přišel přímo od Googlu šifrovaným spojením,
     * proto stačí přečíst jeho obsah; slouží jen k zobrazení v nastavení.
     */
    private static function emailFromIdToken(string $idToken): ?string
    {
        $parts = explode('.', $idToken);
        if (count($parts) !== 3) {
            return null;
        }
        $payload = json_decode((string) base64_decode(strtr($parts[1], '-_', '+/')), true);
        $email = is_array($payload) ? ($payload['email'] ?? null) : null;
        return is_string($email) ? mb_substr($email, 0, 190) : null;
    }

    /**
     * Doplní údaje pro zobrazení: čas, délku a to, zda událost už proběhla nebo právě běží.
     *
     * @param list<array<string,mixed>> $events
     * @return list<array<string,mixed>>
     */
    private static function annotate(array $events): array
    {
        $now = time();
        return array_map(static function (array $event) use ($now): array {
            $minutes = (int) round(($event['end'] - $event['start']) / 60);
            return $event + [
                'time' => $event['allDay'] ? 'celý den' : date('H:i', $event['start']),
                'duration' => $event['allDay'] || $minutes <= 0 ? '' : Czech::duration($minutes),
                'past' => !$event['allDay'] && $event['end'] <= $now,
                'current' => !$event['allDay'] && $event['start'] <= $now && $event['end'] > $now,
            ];
        }, $events);
    }

    /**
     * Ukázkový program pro vyzkoušení bez účtu Google (odpovídá wireframu nástěnky).
     *
     * @return list<array<string,mixed>>
     */
    private static function demo(): array
    {
        $day = strtotime('today');
        $event = static fn (string $time, int $minutes, string $title): array => [
            'title' => $title, 'location' => '', 'allDay' => false,
            'start' => $day + (int) substr($time, 0, 2) * 3600 + (int) substr($time, 3, 2) * 60,
            'end' => $day + (int) substr($time, 0, 2) * 3600 + (int) substr($time, 3, 2) * 60 + $minutes * 60,
        ];
        return [
            $event('08:00', 15, 'Odvoz dětí do školy'),
            $event('09:30', 60, 'Porada týmu'),
            $event('12:00', 60, 'Oběd s kolegou'),
            $event('15:00', 45, 'Zubař – Eliška'),
            $event('17:30', 90, 'Trénink'),
            $event('19:00', 60, 'Rodinná večeře'),
        ];
    }
}
