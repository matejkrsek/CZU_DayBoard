<?php

declare(strict_types=1);

namespace DayBoard\Core;

/**
 * Malý router: mapuje metodu a cestu na obsluhu.
 * Vzor může obsahovat parametry ve složených závorkách, např. /navody/{slug}.
 */
final class Router
{
    /** @var list<array{method:string,regex:string,handler:callable}> */
    private array $routes = [];

    public function get(string $pattern, callable $handler): void
    {
        $this->add('GET', $pattern, $handler);
    }

    public function post(string $pattern, callable $handler): void
    {
        $this->add('POST', $pattern, $handler);
    }

    private function add(string $method, string $pattern, callable $handler): void
    {
        $regex = preg_replace('~\{([a-z_]+)\}~', '(?P<$1>[^/]+)', $pattern);
        $this->routes[] = ['method' => $method, 'regex' => '~^' . $regex . '$~u', 'handler' => $handler];
    }

    /**
     * Najde a spustí obsluhu. Vrací false, pokud cestě nic neodpovídá (404),
     * a vyhodí MethodNotAllowed, pokud cesta existuje jen pro jinou metodu.
     */
    public function dispatch(string $method, string $path): bool
    {
        $method = $method === 'HEAD' ? 'GET' : $method;
        $pathMatched = false;

        foreach ($this->routes as $route) {
            if (preg_match($route['regex'], $path, $matches) !== 1) {
                continue;
            }
            $pathMatched = true;
            if ($route['method'] !== $method) {
                continue;
            }
            // Pojmenované části cesty se předají obsluze jako pojmenované argumenty.
            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
            ($route['handler'])(...$params);
            return true;
        }

        if ($pathMatched) {
            throw new HttpError(405, 'Tato adresa nepodporuje metodu ' . $method . '.');
        }
        return false;
    }
}
