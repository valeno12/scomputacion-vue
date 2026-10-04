<?php

namespace App\Services\Comercio;

use Illuminate\Validation\ValidationException;

final class Dinero
{
    public static function centavos(mixed $value, string $field = 'importe'): int
    {
        $value = (string) $value;
        if (! preg_match('/^\d{1,9}(?:\.\d{1,2})?$/D', $value)) {
            throw ValidationException::withMessages([$field => 'Ingresá un importe positivo o cero, con hasta dos decimales.']);
        }
        [$whole, $fraction] = array_pad(explode('.', $value), 2, '');

        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }

    /** Largest remainder allocation in integer cents, including losses. */
    public static function repartir(int $ganancia, array $reparto): array
    {
        $basisPoints = array_map(fn ($r) => self::centavos($r['porcentaje'], 'reparto'), $reparto);
        if (empty($basisPoints) || array_sum($basisPoints) !== 10000 || max($basisPoints) > 10000) {
            throw ValidationException::withMessages(['reparto' => 'Los porcentajes del reparto deben sumar exactamente 100 %.']);
        }
        $amounts = $remainders = [];
        foreach ($basisPoints as $i => $percentage) {
            $weightedRemainder = (abs($ganancia) % 10000) * $percentage;
            $amounts[$i] = intdiv(abs($ganancia), 10000) * $percentage + intdiv($weightedRemainder, 10000);
            $remainders[$i] = $weightedRemainder % 10000;
        }
        arsort($remainders, SORT_NUMERIC);
        $remaining = abs($ganancia) - array_sum($amounts);
        foreach (array_keys($remainders) as $i) {
            if ($remaining-- <= 0) {
                break;
            }
            $amounts[$i]++;
        }

        return array_map(fn ($i) => [
            ...$reparto[$i],
            'ganancia_centavos' => $amounts[$i] * ($ganancia < 0 ? -1 : 1),
        ], array_keys($reparto));
    }
}
