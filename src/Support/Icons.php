<?php

declare(strict_types=1);

namespace DayBoard\Support;

/**
 * Sada jednoduchých čárových ikon vkládaných přímo do HTML jako SVG
 * (žádný další požadavek na server, barva se řídí barvou textu).
 * Ikony jsou dekorativní – význam vždy nese text vedle nich.
 */
final class Icons
{
    private const CLOUD = '<path d="M7 18a4 4 0 0 1-.5-7.97A6 6 0 0 1 18 9.5a4.25 4.25 0 0 1-.5 8.5H7z"/>';
    private const CLOUD_HIGH = '<path d="M7 15a4 4 0 0 1-.5-7.97A6 6 0 0 1 18 6.5a4.25 4.25 0 0 1-.5 8.5H7z"/>';

    private const PATHS = [
        // počasí
        'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
        'moon' => '<path d="M20 14.5A8 8 0 0 1 9.5 4a8 8 0 1 0 10.5 10.5z"/>',
        'cloud' => self::CLOUD,
        'cloud-sun' => '<path d="M8 2.5V4M3.4 4.9l1 1M1.5 9.5H3M12.6 4.9l-1 1"/><path d="M5.2 10.4a3.2 3.2 0 0 1 5.5-2.9"/><path d="M9 20.5a3.5 3.5 0 0 1-.4-6.98 5 5 0 0 1 9.6-.92 3.75 3.75 0 0 1-.4 7.9H9z"/>',
        'cloud-moon' => '<path d="M11.5 3.2a4.2 4.2 0 0 0 4.6 5.3"/><path d="M11.5 3.2A4.3 4.3 0 0 0 7 8.6"/><path d="M9 20.5a3.5 3.5 0 0 1-.4-6.98 5 5 0 0 1 9.6-.92 3.75 3.75 0 0 1-.4 7.9H9z"/>',
        'rain' => self::CLOUD_HIGH . '<path d="M8 18.5l-1 2.5M12 18.5l-1 2.5M16 18.5l-1 2.5"/>',
        'drizzle' => self::CLOUD_HIGH . '<path d="M9 18.5l-.6 1.5M14.5 18.5l-.6 1.5"/>',
        'snow' => self::CLOUD_HIGH . '<path d="M8 19h.01M12 21h.01M16 19h.01M12 17.8h.01" stroke-width="2.4"/>',
        'storm' => self::CLOUD_HIGH . '<path d="M12.5 16.5l-2.2 3h3l-1.8 3"/>',
        'fog' => '<path d="M7 5h10M4 9h16M4 13h16M6 17h12"/>',
        'sunrise' => '<path d="M12 3v4M5.6 9.6l1.5 1.5M18.4 9.6l-1.5 1.5M3 17h18M6.5 21h11"/><path d="M7.5 17a4.5 4.5 0 0 1 9 0"/>',
        // widgety
        'calendar' => '<rect x="3.5" y="5" width="17" height="15.5" rx="2.5"/><path d="M3.5 10h17M8 3v4M16 3v4"/>',
        'car' => '<path d="M4 17v-4.5l2-5a2 2 0 0 1 1.9-1.3h8.2A2 2 0 0 1 18 7.5l2 5V17"/><path d="M4 12.5h16M4 17h16M6.5 17v2M17.5 17v2"/>',
        'tram' => '<rect x="5" y="4.5" width="14" height="12.5" rx="3"/><path d="M5 11h14M8.5 20.5L10 17M15.5 20.5L14 17M9 2.5h6"/><path d="M9 14h.01M15 14h.01" stroke-width="2.4"/>',
        'star' => '<path d="M12 3.5l2.5 5.3 5.8.8-4.2 4 1 5.8-5.1-2.8-5.1 2.8 1-5.8-4.2-4 5.8-.8L12 3.5z"/>',
        'news' => '<rect x="3.5" y="4.5" width="17" height="15" rx="2"/><path d="M7 9h6M7 12.5h10M7 16h10"/>',
        'cart' => '<path d="M3 4h2.5l2.2 10.5h9.8L19.5 7H6.8"/><circle cx="9" cy="19" r="1.4"/><circle cx="16.5" cy="19" r="1.4"/>',
        'pin' => '<path d="M12 21s6.5-5.6 6.5-11a6.5 6.5 0 0 0-13 0C5.5 15.4 12 21 12 21z"/><circle cx="12" cy="10" r="2.3"/>',
        'radio' => '<rect x="3.5" y="8" width="17" height="12" rx="2.5"/><path d="M7 8l9-4.5M15 12.5h2.5M15 15.5h2.5"/><circle cx="9" cy="14" r="2.5"/>',
        'photo' => '<rect x="3.5" y="5" width="17" height="14" rx="2"/><circle cx="9" cy="10" r="1.6"/><path d="M4 17l5-4.5 3.5 3 3-2.5 4.5 4"/>',
        // ovládání
        'play' => '<path d="M8 5.5v13l10.5-6.5L8 5.5z" fill="currentColor"/>',
        'pause' => '<path d="M8.5 5.5v13M15.5 5.5v13" stroke-width="2.6"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'x' => '<path d="M6 6l12 12M18 6L6 18"/>',
        'check' => '<path d="M5 12.5l4.5 4.5L19 7.5"/>',
        'arrow-right' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'arrow-left' => '<path d="M19 12H5M11 6l-6 6 6 6"/>',
        'search' => '<circle cx="11" cy="11" r="6.5"/><path d="M16 16l4.5 4.5"/>',
        'refresh' => '<path d="M20 11a8 8 0 0 0-14.3-4.3L4 8.5M4 4v4.5h4.5M4 13a8 8 0 0 0 14.3 4.3l1.7-1.8M20 20v-4.5h-4.5"/>',
        'volume' => '<path d="M4 10v4h3l4.5 3.5v-11L7 10H4z"/><path d="M15.5 9a4 4 0 0 1 0 6M18 6.5a7.5 7.5 0 0 1 0 11"/>',
        'grip' => '<path d="M9 6h.01M15 6h.01M9 12h.01M15 12h.01M9 18h.01M15 18h.01" stroke-width="2.6"/>',
        'edit' => '<path d="M4 20l1-4L16 5l3 3L8 19l-4 1zM14 7l3 3"/>',
        'logout' => '<path d="M14 4h4a1.5 1.5 0 0 1 1.5 1.5v13A1.5 1.5 0 0 1 18 20h-4M10 8l-4 4 4 4M6 12h9"/>',
        'external' => '<path d="M14 4h6v6M20 4l-9 9M18 13.5V19a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5.5"/>',
        // obecné
        'user' => '<circle cx="12" cy="8" r="3.5"/><path d="M5 20a7 7 0 0 1 14 0"/>',
        'users' => '<circle cx="9" cy="8.5" r="3"/><path d="M3 19.5a6 6 0 0 1 12 0M15.5 5.8a3 3 0 0 1 0 5.4M17.5 14.2a6 6 0 0 1 3.5 5.3"/>',
        'home' => '<path d="M4 11l8-7 8 7v8.5a1 1 0 0 1-1 1h-4.5V14h-5v6.5H5a1 1 0 0 1-1-1V11z"/>',
        'shield' => '<path d="M12 3l7.5 3v5.5c0 4.6-3.2 8-7.5 9.5-4.3-1.5-7.5-4.9-7.5-9.5V6L12 3z"/><path d="M9 12l2.2 2.2 4-4.2"/>',
        'lock' => '<rect x="5" y="10.5" width="14" height="10" rx="2"/><path d="M8 10.5V8a4 4 0 0 1 8 0v2.5"/>',
        'sliders' => '<path d="M4 7h10M18 7h2M4 17h2M10 17h10"/><circle cx="16" cy="7" r="2"/><circle cx="8" cy="17" r="2"/>',
        'clock' => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/>',
        'server' => '<rect x="4" y="4" width="16" height="7" rx="2"/><rect x="4" y="13" width="16" height="7" rx="2"/><path d="M8 7.5h.01M8 16.5h.01" stroke-width="2.4"/>',
        'grid' => '<rect x="4" y="4" width="7" height="7" rx="1.5"/><rect x="13" y="4" width="7" height="7" rx="1.5"/><rect x="4" y="13" width="7" height="7" rx="1.5"/><rect x="13" y="13" width="7" height="7" rx="1.5"/>',
        'info' => '<circle cx="12" cy="12" r="8.5"/><path d="M12 11v5M12 7.8h.01"/>',
        'alert' => '<path d="M12 4l9 15.5H3L12 4z"/><path d="M12 10v4.5M12 17h.01"/>',
    ];

    public static function svg(string $name, int $size = 20, string $class = ''): string
    {
        $paths = self::PATHS[$name] ?? self::PATHS['info'];
        return '<svg class="icon' . ($class !== '' ? ' ' . e($class) : '') . '" width="' . $size . '" height="' . $size
            . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"'
            . ' stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths . '</svg>';
    }
}
