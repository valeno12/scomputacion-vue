<?php

namespace App\Services\Comercio;

use Illuminate\Validation\ValidationException;

final class PrecioProducto
{
    public static function calcular(mixed $costo, mixed $porcentaje, string $campo = 'porcentaje_ganancia'): int
    {
        $centavos = Dinero::centavos($costo, 'costo');
        $puntos = Dinero::centavos($porcentaje, $campo);
        if ($puntos > 1000000) {
            throw ValidationException::withMessages([$campo => 'El porcentaje máximo es 10.000 %.']);
        }
        // Centavos enteros, con redondeo a dos decimales (mitades hacia arriba).
        $precio = $centavos + intdiv($centavos * $puntos + 5000, 10000);
        if ($precio > 99999999900) {
            throw ValidationException::withMessages([$campo => 'El precio calculado supera el importe máximo permitido.']);
        }

        return $precio;
    }
}
