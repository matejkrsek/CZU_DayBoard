<?php

declare(strict_types=1);

namespace DayBoard\Core;

/** Chyba při volání externí služby (síť, časový limit, HTTP 4xx/5xx). */
final class HttpException extends \RuntimeException
{
    public function __construct(string $message, public readonly int $status = 0, public readonly string $body = '')
    {
        parent::__construct($message, $status);
    }
}
