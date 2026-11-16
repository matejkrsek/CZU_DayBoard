<?php

declare(strict_types=1);

namespace DayBoard\Core;

/** Chyba, která se má uživateli zobrazit jako stránka s daným HTTP stavem (403, 404, 419…). */
final class HttpError extends \RuntimeException
{
    public function __construct(public readonly int $status, string $message = '')
    {
        parent::__construct($message, $status);
    }
}
