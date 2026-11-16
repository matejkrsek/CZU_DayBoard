<?php

declare(strict_types=1);

namespace DayBoard\Controllers;

use DayBoard\Core\Auth;
use DayBoard\Models\Household;
use DayBoard\Models\Layouts;
use DayBoard\Support\Czech;
use DayBoard\Widgets\WidgetRegistry;
use DayBoard\Widgets\WidgetRenderer;

/** Nástěnka – mřížka widgetů přihlášeného uživatele. */
final class DashboardController extends Controller
{
    public static function show(): void
    {
        $user = self::user('dashboard.view');
        $registry = WidgetRegistry::available();
        $allowed = Household::allowedWidgets($user);
        $layout = Layouts::forUser($user);
        $canEdit = Auth::can('layout.edit', $user);

        // Widgety s externími daty se načtou AJAXem až po vykreslení stránky;
        // ostatní (nákupní seznam, mapa, rádio) jsou rovnou v HTML.
        $bodies = [];
        foreach ($layout as $item) {
            if (!$registry[$item['key']]['async']) {
                $bodies[$item['key']] = WidgetRenderer::body($item['key'], $user);
            }
        }

        // Nabídka pro režim úprav: co lze přidat a co správce nepovolil.
        $catalog = [];
        foreach (WidgetRegistry::all() as $key => $widget) {
            $available = ($widget['available'] ?? true) === true;
            $catalog[] = [
                'key' => $key,
                'title' => $widget['title'],
                'scope' => WidgetRegistry::scopeLabel($widget['scope']),
                'w' => $widget['w'], 'h' => $widget['h'],
                'minW' => $widget['minW'] ?? 1, 'minH' => $widget['minH'] ?? 1,
                'status' => !$available ? 'extension' : (in_array($key, $allowed, true) ? 'allowed' : 'disabled'),
            ];
        }

        $now = new \DateTimeImmutable();
        self::page('app/dashboard', $user, [
            'title' => 'Nástěnka',
            'active' => 'dashboard',
            'greeting' => Czech::greeting($now) . ', ' . Czech::vocative((string) $user['display_name']),
            'today' => Czech::longDate($now),
            'layout' => $layout,
            'registry' => $registry,
            'bodies' => $bodies,
            'canEdit' => $canEdit,
            'editorConfig' => [
                'cols' => WidgetRegistry::COLS,
                'maxH' => WidgetRegistry::MAX_H,
                'confirmRemove' => (int) $user['confirm_remove'] === 1,
                'catalog' => $catalog,
                'defaultLayout' => Layouts::pack($allowed),
            ],
        ]);
    }
}
