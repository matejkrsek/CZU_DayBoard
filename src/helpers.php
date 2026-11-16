<?php

declare(strict_types=1);

// Globální pomocné funkce používané hlavně v šablonách.

use DayBoard\Core\Config;
use DayBoard\Core\Csrf;
use DayBoard\Core\Session;
use DayBoard\Core\View;

/** Escapování výstupu do HTML – používat na vše, co nepochází přímo ze šablony. */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function config(string $key, mixed $default = null): mixed
{
    return Config::get($key, $default);
}

function is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
}

/**
 * Cesta, pod kterou aplikace na serveru běží ('' v kořeni domény, jinak např. '/CZU_DayBoard').
 * Díky tomu funguje web i ve složce XAMPP htdocs bez úprav.
 */
function base_path(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }
    if (PHP_SAPI === 'cli-server' || PHP_SAPI === 'cli') {
        return $base = '';
    }
    $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    if ($dir !== '' && !str_starts_with($uri . '/', $dir . '/')) {
        // Požadavek prošel kořenovým .htaccess, takže v adrese chybí "/public".
        $dir = (string) preg_replace('~/public$~', '', $dir);
    }
    return $base = $dir;
}

/** Adresa uvnitř aplikace: url('/funkce') -> '/funkce' nebo '/CZU_DayBoard/funkce'. */
function url(string $path = '/'): string
{
    return base_path() . '/' . ltrim($path, '/');
}

/** Absolutní adresa pro canonical, sitemap, Open Graph a OAuth. */
function absolute_url(string $path = '/'): string
{
    $appUrl = rtrim((string) config('APP_URL', ''), '/');
    if ($appUrl !== '') {
        return $appUrl . '/' . ltrim($path, '/');
    }
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return (is_https() ? 'https' : 'http') . '://' . $host . url($path);
}

/** Adresa statického souboru s verzí podle času změny (kvůli cache prohlížeče). */
function asset(string $path): string
{
    $path = ltrim($path, '/');
    $file = DAYBOARD_ROOT . '/public/assets/' . $path;
    return url('/assets/' . $path) . '?v=' . (is_file($file) ? filemtime($file) : 0);
}

function redirect(string $path, int $status = 303): never
{
    header('Location: ' . url($path), true, $status);
    exit;
}

function json_response(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

/** Oříznutá textová hodnota z formuláře. */
function input(string $key, string $default = ''): string
{
    $value = $_POST[$key] ?? $default;
    return is_string($value) ? trim($value) : $default;
}

function checked_input(string $key): bool
{
    return !empty($_POST[$key]);
}

function csrf_token(): string
{
    return Csrf::token();
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(Csrf::token()) . '">';
}

/** Jednorázová zpráva zobrazená po přesměrování (typ: success | error | info). */
function flash(string $type, string $message): void
{
    Session::start();
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

/** @return list<array{type:string,message:string}> */
function flashes(): array
{
    if (!Session::isActive()) {
        return [];
    }
    $items = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $items;
}

function partial(string $template, array $data = []): string
{
    return View::partial($template, $data);
}

function icon(string $name, int $size = 20, string $class = ''): string
{
    return DayBoard\Support\Icons::svg($name, $size, $class);
}

/**
 * Značka <img> pro obrázek z public/assets/img. Šířku a výšku přečte ze souboru,
 * takže prohlížeč pro obrázek rezervuje místo a stránka při načítání neposkakuje.
 * Popisný atribut alt je povinný. Pokud soubor chybí, nevypíše se nic.
 *
 * @param array<string,string|int> $attributes další atributy (class, loading, fetchpriority…)
 */
function image_tag(string $file, string $alt, array $attributes = []): string
{
    $path = DAYBOARD_ROOT . '/public/assets/img/' . $file;
    if (!is_file($path)) {
        return '';
    }
    if (!isset($attributes['width'], $attributes['height'])) {
        $size = @getimagesize($path);
        if ($size !== false) {
            $attributes += ['width' => $size[0], 'height' => $size[1]];
        }
    }
    $attributes += ['loading' => 'lazy', 'decoding' => 'async'];

    $html = '<img src="' . e(asset('img/' . $file)) . '" alt="' . e($alt) . '"';
    foreach ($attributes as $name => $value) {
        $html .= ' ' . e($name) . '="' . e($value) . '"';
    }
    return $html . '>';
}

/** Zkrácení textu na daný počet znaků s výpustkou. */
function str_limit(string $text, int $limit): string
{
    return mb_strlen($text) > $limit ? rtrim(mb_substr($text, 0, $limit - 1)) . '…' : $text;
}
