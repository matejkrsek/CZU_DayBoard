<?php

declare(strict_types=1);

namespace DayBoard\Core;

/**
 * Jednoduchý HTTP klient pro volání externích API (cURL, případně PHP streamy).
 * Krátké časové limity – widget raději ukáže chybu, než aby stránka visela.
 */
final class Http
{
    private const USER_AGENT = 'DayBoard/1.0 (self-hosted family dashboard)';
    private const MAX_BYTES = 3_000_000;

    /**
     * @param array<string,string> $headers
     * @param bool $guard true pro adresy zadané uživatelem (RSS): povolí jen veřejné servery
     */
    public static function get(string $url, array $headers = [], int $timeout = 6, bool $guard = false): string
    {
        if (!$guard) {
            return self::request('GET', $url, $headers, null, $timeout, true)['body'];
        }

        // Přesměrování procházíme ručně, aby každý cíl prošel kontrolou.
        for ($hop = 0; $hop < 4; $hop++) {
            $pin = self::assertPublicUrl($url);
            $response = self::request('GET', $url, $headers, null, $timeout, false, $pin);
            if ($response['status'] >= 300 && $response['status'] < 400 && $response['location'] !== '') {
                $url = self::resolveUrl($response['location'], $url);
                continue;
            }
            return $response['body'];
        }
        throw new HttpException('Příliš mnoho přesměrování.', 0);
    }

    /**
     * @param array<string,string> $headers
     * @return array<mixed>
     */
    public static function getJson(string $url, array $headers = [], int $timeout = 6): array
    {
        return self::decode(self::get($url, $headers + ['Accept' => 'application/json'], $timeout));
    }

    /**
     * @param array<string,string> $fields
     * @return array<mixed>
     */
    public static function postForm(string $url, array $fields, int $timeout = 8): array
    {
        $response = self::request(
            'POST',
            $url,
            ['Content-Type' => 'application/x-www-form-urlencoded', 'Accept' => 'application/json'],
            http_build_query($fields),
            $timeout,
            false
        );
        return $response['body'] === '' ? [] : self::decode($response['body']);
    }

    /** @return array<mixed> */
    private static function decode(string $body): array
    {
        $data = json_decode($body, true);
        if (!is_array($data)) {
            throw new HttpException('Server vrátil neplatnou odpověď (očekáván JSON).', 0, $body);
        }
        return $data;
    }

    /**
     * Ověří, že adresa míří na veřejný server (ne do domácí sítě nebo na localhost).
     * Vrací dvojici [host, ip], na kterou se má spojení "připíchnout".
     *
     * @return array{0:string,1:string}
     */
    public static function assertPublicUrl(string $url): array
    {
        $parts = parse_url($url);
        $scheme = strtolower($parts['scheme'] ?? '');
        $host = $parts['host'] ?? '';
        if (!in_array($scheme, ['http', 'https'], true) || $host === '') {
            throw new HttpException('Adresa musí začínat http:// nebo https://.', 0);
        }

        $ips = filter_var(trim($host, '[]'), FILTER_VALIDATE_IP) ? [trim($host, '[]')] : (gethostbynamel($host) ?: []);
        if ($ips === []) {
            throw new HttpException('Server „' . $host . '“ se nepodařilo najít.', 0);
        }
        foreach ($ips as $ip) {
            $public = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
            if ($public === false) {
                throw new HttpException('Adresa míří do vnitřní sítě, což není povoleno.', 0);
            }
        }
        return [$host, $ips[0]];
    }

    private static function resolveUrl(string $location, string $base): string
    {
        if (preg_match('~^https?://~i', $location) === 1) {
            return $location;
        }
        $parts = parse_url($base);
        $origin = ($parts['scheme'] ?? 'https') . '://' . ($parts['host'] ?? '') . (isset($parts['port']) ? ':' . $parts['port'] : '');
        if (str_starts_with($location, '//')) {
            return ($parts['scheme'] ?? 'https') . ':' . $location;
        }
        if (str_starts_with($location, '/')) {
            return $origin . $location;
        }
        return $origin . rtrim(dirname($parts['path'] ?? '/'), '/\\') . '/' . $location;
    }

    /**
     * @param array<string,string> $headers
     * @param array{0:string,1:string}|null $pin
     * @return array{status:int,body:string,location:string}
     */
    private static function request(
        string $method,
        string $url,
        array $headers,
        ?string $body,
        int $timeout,
        bool $follow,
        ?array $pin = null
    ): array {
        $headers += ['User-Agent' => self::USER_AGENT];
        $response = function_exists('curl_init')
            ? self::viaCurl($method, $url, $headers, $body, $timeout, $follow, $pin)
            : self::viaStream($method, $url, $headers, $body, $timeout, $follow);

        $isRedirect = $response['status'] >= 300 && $response['status'] < 400;
        if ($response['status'] >= 400 || ($response['status'] < 200 && !$isRedirect)) {
            throw new HttpException('Server odpověděl chybou HTTP ' . $response['status'] . '.', $response['status'], $response['body']);
        }
        return $response;
    }

    /**
     * @param array<string,string> $headers
     * @param array{0:string,1:string}|null $pin
     * @return array{status:int,body:string,location:string}
     */
    private static function viaCurl(string $method, string $url, array $headers, ?string $body, int $timeout, bool $follow, ?array $pin): array
    {
        $buffer = '';
        $location = '';
        $curl = curl_init($url);
        $options = [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => array_map(static fn ($k, $v) => $k . ': ' . $v, array_keys($headers), $headers),
            CURLOPT_CONNECTTIMEOUT => min(4, $timeout),
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_FOLLOWLOCATION => $follow,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_ENCODING => '',
            CURLOPT_HEADERFUNCTION => static function ($curl, string $line) use (&$location): int {
                if (stripos($line, 'Location:') === 0) {
                    $location = trim(substr($line, 9));
                }
                return strlen($line);
            },
            CURLOPT_WRITEFUNCTION => static function ($curl, string $chunk) use (&$buffer): int {
                $buffer .= $chunk;
                // Návratová hodnota menší než délka bloku přenos přeruší (příliš velká odpověď).
                return strlen($buffer) > self::MAX_BYTES ? 0 : strlen($chunk);
            },
        ];
        if ($body !== null) {
            $options[CURLOPT_POSTFIELDS] = $body;
        }
        // PHP na Windows často nemá nastavený soubor s certifikačními autoritami
        // (curl.cainfo). Místo vypínání kontroly certifikátů použijeme úložiště Windows.
        if (PHP_OS_FAMILY === 'Windows' && defined('CURLSSLOPT_NATIVE_CA')
            && !ini_get('curl.cainfo') && !ini_get('openssl.cafile')) {
            $options[CURLOPT_SSL_OPTIONS] = CURLSSLOPT_NATIVE_CA;
        }
        if ($pin !== null) {
            $port = parse_url($url, PHP_URL_PORT) ?: (stripos($url, 'https://') === 0 ? 443 : 80);
            $options[CURLOPT_RESOLVE] = [$pin[0] . ':' . $port . ':' . $pin[1]];
        }
        curl_setopt_array($curl, $options);

        $ok = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if ($ok === false) {
            throw new HttpException('Spojení se nezdařilo: ' . ($error ?: 'neznámá chyba'), 0);
        }
        return ['status' => $status, 'body' => $buffer, 'location' => $location];
    }

    /**
     * Záložní varianta pro PHP bez rozšíření cURL.
     *
     * @param array<string,string> $headers
     * @return array{status:int,body:string,location:string}
     */
    private static function viaStream(string $method, string $url, array $headers, ?string $body, int $timeout, bool $follow): array
    {
        $context = stream_context_create(['http' => [
            'method' => $method,
            'header' => implode("\r\n", array_map(static fn ($k, $v) => $k . ': ' . $v, array_keys($headers), $headers)),
            'content' => $body ?? '',
            'timeout' => $timeout,
            'follow_location' => $follow ? 1 : 0,
            'max_redirects' => 3,
            'ignore_errors' => true,
        ]]);
        $result = @file_get_contents($url, false, $context, 0, self::MAX_BYTES);
        if ($result === false) {
            throw new HttpException('Spojení se nezdařilo.', 0);
        }

        $status = 0;
        $location = '';
        foreach ($http_response_header ?? [] as $line) {
            if (preg_match('~^HTTP/\S+\s+(\d{3})~', $line, $m) === 1) {
                $status = (int) $m[1];
            } elseif (stripos($line, 'Location:') === 0) {
                $location = trim(substr($line, 9));
            }
        }
        return ['status' => $status, 'body' => $result, 'location' => $location];
    }
}
