<?php

declare(strict_types=1);

namespace DayBoard\Core;

/** Vykreslování PHP šablon ze složky templates/. */
final class View
{
    /** Vykreslí šablonu a vloží ji do layoutu (templates/layouts/{layout}.php). */
    public static function render(string $template, array $data = [], ?string $layout = null): string
    {
        $content = self::partial($template, $data);
        if ($layout === null) {
            return $content;
        }
        return self::partial('layouts/' . $layout, $data + ['content' => $content]);
    }

    /** Vykreslí samotnou šablonu; pole $data se v ní rozbalí do proměnných. */
    public static function partial(string $template, array $data = []): string
    {
        $file = DAYBOARD_ROOT . '/templates/' . $template . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException('Šablona nenalezena: ' . $template);
        }

        $render = static function (string $__file, array $__data): void {
            extract($__data, EXTR_SKIP);
            require $__file;
        };

        ob_start();
        try {
            $render($file, $data);
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        return (string) ob_get_clean();
    }

    public static function send(string $html, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: text/html; charset=utf-8');
        echo $html;
    }
}
