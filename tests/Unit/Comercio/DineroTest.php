<?php

use App\Services\Comercio\Dinero;
use Illuminate\Validation\ValidationException;

uses(Tests\TestCase::class);

test('money is represented as exact integer cents', function () {
    expect(Dinero::centavos('9000'))->toBe(900000)
        ->and(Dinero::centavos('0.01'))->toBe(1)
        ->and(Dinero::centavos('125.5'))->toBe(12550);
});

test('distribution applies only to profit and supports arbitrary participants', function () {
    $result = Dinero::repartir(100000, [['porcentaje' => '12.50'], ['porcentaje' => '37.50'], ['porcentaje' => '50']]);
    expect(array_column($result, 'ganancia_centavos'))->toBe([12500, 37500, 50000]);
});

test('rounding preserves every cent even for negative profits and ties', function (int $profit) {
    $result = Dinero::repartir($profit, [['porcentaje' => '33.33'], ['porcentaje' => '33.33'], ['porcentaje' => '33.34']]);
    expect(array_sum(array_column($result, 'ganancia_centavos')))->toBe($profit);
})->with([0, 1, 2, 100, -1, -101, 999999999999999]);

test('invalid percentages are rejected', function (array $rows) {
    expect(fn () => Dinero::repartir(100, $rows))->toThrow(ValidationException::class);
})->with([[[]], [[['porcentaje' => 99]]], [[['porcentaje' => 101]]], [[['porcentaje' => -20], ['porcentaje' => 120]]]]);
