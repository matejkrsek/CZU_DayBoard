<?php

declare(strict_types=1);

// Front controller – jediný vstupní bod aplikace.
// Spuštění pro vývoj: php -S localhost:8000 -t public public/index.php

if (PHP_SAPI === 'cli-server') {
    // Vestavěný server PHP: existující statické soubory (CSS, JS, obrázky) obslouží sám.
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    if ($path !== '/' && !str_contains($path, '..') && is_file(__DIR__ . $path)) {
        return false;
    }
}

require dirname(__DIR__) . '/src/bootstrap.php';

DayBoard\App::run();
