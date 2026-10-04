<?php

namespace App\Services\Comercio;

use App\Models\InventarioMovimiento;
use App\Models\LoteStock;
use App\Models\ProductoNuevo;
use App\Models\Proveedor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class StockProductos
{
    public function ingresarVarios(array $data): void
    {
        DB::transaction(function () use ($data) {
            // La clave identifica todo el ingreso: un reintento no duplica ninguna fila.
            if (! DB::table('ingresos_stock')->insertOrIgnore(['clave' => $data['clave'], 'created_at' => now(), 'updated_at' => now()])) {
                return;
            }
            $ingresoId = DB::table('ingresos_stock')->where('clave', $data['clave'])->value('id');
            $productos = ProductoNuevo::whereIn('id', array_column($data['items'], 'producto_id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            foreach ($data['items'] as $index => $item) {
                $producto = $productos->get($item['producto_id']);
                if (! $producto?->activo) {
                    throw ValidationException::withMessages(["items.$index.producto_id" => 'Este producto ya no está disponible.']);
                }
                PrecioProducto::calcular($item['costo'], $item['porcentaje_ganancia'], "items.$index.porcentaje_ganancia");
                $this->ingresar($producto, array_merge($data, $item, ['clave' => (string) Str::uuid(), 'ingreso_stock_id' => $ingresoId]));
            }
        }, 3);
    }

    public function ingresar(ProductoNuevo $producto, array $data): LoteStock
    {
        return DB::transaction(function () use ($producto, $data) {
            $producto = ProductoNuevo::lockForUpdate()->findOrFail($producto->id);
            if (! $producto->activo) {
                throw ValidationException::withMessages(['cantidad' => 'El producto fue eliminado.']);
            }
            $clave = $data['clave'] ?? (string) Str::uuid();
            if ($existing = LoteStock::where('clave', $clave)->first()) {
                if ($existing->articulo_id !== $producto->id) {
                    throw ValidationException::withMessages(['cantidad' => 'El ingreso ya pertenece a otro producto.']);
                }

                return $existing;
            }
            $proveedorId = $data['proveedor_id'] ?? null;
            if (! empty($data['proveedor'])) {
                $nombre = trim($data['proveedor']);
                $proveedorId = (Proveedor::whereRaw('LOWER(nombre) = ?', [mb_strtolower($nombre)])->first()
                    ?? Proveedor::create(['nombre' => $nombre]))->id;
            }
            $lot = LoteStock::create([
                'clave' => $clave, 'articulo_id' => $producto->id,
                'comprador_id' => $data['comprador_id'], 'proveedor_id' => $proveedorId,
                'cantidad_inicial' => $data['cantidad'], 'cantidad_disponible' => $data['cantidad'],
                'costo_unitario_centavos' => Dinero::centavos($data['costo'], 'costo'),
                'tipo' => $data['tipo'], 'fecha' => $data['fecha'],
                'ingreso_stock_id' => $data['ingreso_stock_id'] ?? null,
                'porcentaje_ganancia' => $data['porcentaje_ganancia'] ?? null,
                'precio_venta_centavos' => isset($data['porcentaje_ganancia']) ? PrecioProducto::calcular($data['costo'], $data['porcentaje_ganancia']) : null,
            ]);
            InventarioMovimiento::create(['lote_id' => $lot->id, 'cantidad' => $lot->cantidad_inicial, 'motivo' => $lot->tipo]);
            if (isset($data['porcentaje_ganancia'])) {
                $producto->update([
                    'costo_referencia_centavos' => $lot->costo_unitario_centavos,
                    'porcentaje_ganancia' => $data['porcentaje_ganancia'],
                    'precio_venta_centavos' => $lot->precio_venta_centavos,
                ]);
            } elseif (isset($data['precio_venta'])) {
                $producto->update(['precio_venta_centavos' => Dinero::centavos($data['precio_venta'], 'precio_venta')]);
            }

            return $lot;
        }, 3);
    }
}
