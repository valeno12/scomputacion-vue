<?php

namespace App\Services\Comercio;

use App\Models\ProductoNuevo;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class PreciosProductos
{
    public function aumentar(array $data): void
    {
        DB::transaction(function () use ($data) {
            $productos = ProductoNuevo::whereIn('id', array_column($data['items'], 'producto_id'))
                ->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            foreach ($data['items'] as $index => $item) {
                $producto = $productos->get($item['producto_id']);
                $campo = "items.$index.producto_id";
                if (! $producto?->activo) {
                    throw ValidationException::withMessages([$campo => 'Este producto ya no está disponible. Quitalo de la selección.']);
                }
                $actual = $producto->precio_venta_centavos;
                if ($actual !== (int) $item['precio_actual_centavos']) {
                    throw ValidationException::withMessages([$campo => 'El precio cambió desde que abriste la revisión. Actualizá la revisión antes de confirmar.']);
                }
                if ($actual <= 0) {
                    throw ValidationException::withMessages([$campo => 'Este producto no tiene un precio de venta para aumentar. Registrá su ingreso primero.']);
                }
                $importe = intdiv($actual, 100).'.'.str_pad((string) ($actual % 100), 2, '0', STR_PAD_LEFT);
                $nuevo = PrecioProducto::calcular($importe, $data['porcentaje'], $campo);
                if ($nuevo === $actual) {
                    throw ValidationException::withMessages([$campo => 'Este porcentaje no cambia el precio al redondear a centavos. Usá un aumento mayor.']);
                }
                // Only the current catalog price changes. Receipts and operations keep their snapshots.
                $producto->update(['precio_venta_centavos' => $nuevo]);
            }
        }, 3);
    }
}
