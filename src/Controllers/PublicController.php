<?php

declare(strict_types=1);

namespace DayBoard\Controllers;

use DayBoard\Core\HttpError;
use DayBoard\Core\View;
use DayBoard\Site\Pages;

/**
 * Veřejná prezentační část webu. Stránky nepracují s daty domácnosti,
 * nezakládají session ani nesahají do databáze – jsou rychlé a dají se cachovat.
 */
final class PublicController
{
    public static function page(string $path): void
    {
        $page = Pages::get($path);
        if ($page === null) {
            throw new HttpError(404);
        }
        header('Cache-Control: public, max-age=300');
        View::send(View::render($page['template'], ['page' => $page, 'path' => $path], 'site'));
    }

    public static function robots(): void
    {
        header('Content-Type: text/plain; charset=utf-8');
        echo "User-agent: *\n";
        // Aplikace po přihlášení do vyhledávačů nepatří.
        foreach (['/nastenka', '/nastaveni', '/domacnost', '/zmena-hesla', '/api/'] as $private) {
            echo 'Disallow: ' . url($private) . "\n";
        }
        echo "\nSitemap: " . absolute_url('/sitemap.xml') . "\n";
    }

    public static function sitemap(): void
    {
        header('Content-Type: application/xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach (Pages::all() as $path => $page) {
            $template = DAYBOARD_ROOT . '/templates/' . $page['template'] . '.php';
            echo "  <url>\n";
            echo '    <loc>' . e(absolute_url($path)) . "</loc>\n";
            echo '    <lastmod>' . date('Y-m-d', (int) filemtime($template)) . "</lastmod>\n";
            echo "  </url>\n";
        }
        echo "</urlset>\n";
    }
}
