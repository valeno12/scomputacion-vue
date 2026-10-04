<?php

namespace App\Services\Comercio;

final class FechaComercial
{
    public static function hoy(): string
    {
        return now()->setTimezone(config('comercio.zona_horaria'))->toDateString();
    }
}
