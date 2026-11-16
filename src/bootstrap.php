<?php

declare(strict_types=1);

use DayBoard\Core\Config;

define('DAYBOARD_ROOT', dirname(__DIR__));

// Jednoduchý autoloader: DayBoard\Core\Router -> src/Core/Router.php
spl_autoload_register(static function (string $class): void {
    $prefix = 'DayBoard\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $file = DAYBOARD_ROOT . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

require __DIR__ . '/helpers.php';

Config::load(DAYBOARD_ROOT . '/.env');

$isDev = Config::get('APP_ENV', 'development') !== 'production';
error_reporting(E_ALL);
ini_set('display_errors', $isDev ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', DAYBOARD_ROOT . '/storage/logs/php-error.log');

// Aplikace potřebuje několik běžných rozšíření PHP – raději srozumitelná hláška než bílá stránka.
$missing = array_filter(
    ['pdo_sqlite', 'mbstring', 'openssl', 'simplexml'],
    static fn (string $ext): bool => !extension_loaded($ext)
);
if ($missing !== []) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo "DayBoard: v PHP chybí rozšíření: " . implode(', ', $missing) . ".\n";
    echo "Povolte je v souboru php.ini (řádky extension=...) a restartujte server.\n";
    exit(1);
}

mb_internal_encoding('UTF-8');
date_default_timezone_set('Europe/Prague');
