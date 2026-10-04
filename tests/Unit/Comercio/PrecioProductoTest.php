<?php

use App\Services\Comercio\PrecioProducto;
use Illuminate\Validation\ValidationException;

uses(Tests\TestCase::class);

test('price adds the chosen percentage to cost and rounds to cents', function ($cost, $percent, $expected) {
    expect(PrecioProducto::calcular($cost, $percent))->toBe($expected);
})->with([
    ['9000', '40', 1260000], ['9000.25', '40', 1260035],
    ['0.05', '10', 6], ['100', '12.55', 11255], ['9000', '0', 900000], ['0', '40', 0],
]);

test('calculated prices cannot overflow the supported amounts', function () {
    PrecioProducto::calcular('999999999', '10000');
})->throws(ValidationException::class);
