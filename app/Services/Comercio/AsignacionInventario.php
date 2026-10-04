<?php

namespace App\Services\Comercio;

use App\Models\LoteStock;
use App\Models\Operacion;
use App\Models\ProductoNuevo;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class AsignacionInventario
{
    /** Expand product rows into cost snapshots, retaining the financier and previous allocations. */
    public function expandir(Operacion $op, array $items, bool $stockAsignado): array
    {
        $ids = collect($items)->where('tipo', 'stock')->pluck('producto_id')->filter()->unique();
        if ($ids->isEmpty()) {
            return $items;
        }
        $products = ProductoNuevo::whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
        $lots = LoteStock::with('comprador')->whereIn('articulo_id', $ids)->orderBy('fecha')->orderBy('id')->lockForUpdate()->get()->keyBy('id');
        $originals = $op->items()->where('tipo', 'stock')->get();
        $available = $lots->mapWithKeys(fn ($lot) => [$lot->id => $lot->cantidad_disponible])->all();
        if ($stockAsignado) {
            foreach ($originals as $row) {
                if (isset($available[$row->lote_id])) {
                    $available[$row->lote_id] += $row->cantidad;
                }
            }
        }
        $prepared = [];
        $pending = [];
        foreach ($items as $index => $item) {
            if ($item['tipo'] !== 'stock' || empty($item['producto_id'])) {
                $prepared[$index] = [$item];
                if ($item['tipo'] === 'stock' && isset($available[$item['lote_id'] ?? 0])) {
                    $available[$item['lote_id']] -= $item['cantidad'];
                }

                continue;
            }
            if (! empty($item['id']) && ! $originals->contains('id', (int) $item['id'])) {
                $this->error($index, 'Este renglón no pertenece al pedido.', 'id');
            }
            $item['grupo'] ??= (string) Str::uuid();
            $previous = $originals->where('grupo', $item['grupo']);
            if ($previous->isEmpty() && ! empty($item['id'])) {
                $previous = $originals->where('id', (int) $item['id']);
            }
            $previous = $previous->filter(fn ($row) => ($row->producto_id ?? $lots->get($row->lote_id)?->articulo_id) === (int) $item['producto_id']);
            $product = $products->get($item['producto_id']);
            if (! $product || (! $product->activo && $previous->isEmpty())) {
                $this->error($index, 'El producto ya no está disponible.');
            }
            $buyer = ! empty($item['comprador_id']) ? (int) $item['comprador_id'] : $previous->first()?->comprador_id;
            $choices = $lots->where('articulo_id', $product->id)->filter(fn ($lot) => ($available[$lot->id] ?? 0) > 0)->pluck('comprador_id')->unique();
            if (! $buyer && $choices->count() === 1) {
                $buyer = $choices->first();
            }
            if (! $buyer) {
                $this->error($index, 'Este producto tiene stock financiado por distintas personas. Elegí quién recupera el costo.', 'comprador_id');
            }
            $previous = $previous->where('comprador_id', $buyer);
            $remaining = (int) $item['cantidad'];
            $prepared[$index] = [];
            foreach ($previous as $old) {
                if ($remaining === 0) {
                    break;
                }
                $take = min($remaining, $old->cantidad);
                $prepared[$index][] = [...$item, 'id' => $old->id, 'lote_id' => $old->lote_id, 'cantidad' => $take, 'comprador_id' => $buyer];
                $available[$old->lote_id] = ($available[$old->lote_id] ?? 0) - $take;
                $remaining -= $take;
            }
            $pending[$index] = ['item' => $item, 'buyer' => $buyer, 'remaining' => $remaining];
        }
        // Existing assigned units are retained before allocating extra units to any row.
        foreach ($pending as $index => $request) {
            ['item' => $item, 'buyer' => $buyer, 'remaining' => $remaining] = $request;
            foreach ($lots->where('articulo_id', $item['producto_id'])->where('comprador_id', $buyer) as $lot) {
                if ($remaining === 0) {
                    break;
                }
                $take = min($remaining, max(0, $available[$lot->id] ?? 0));
                if ($take === 0) {
                    continue;
                }
                $prepared[$index][] = [...$item, 'id' => null, 'lote_id' => $lot->id, 'cantidad' => $take, 'comprador_id' => $buyer];
                $available[$lot->id] -= $take;
                $remaining -= $take;
            }
            if ($remaining > 0) {
                $name = $lots->firstWhere('comprador_id', $buyer)?->comprador?->nombre ?? 'la persona elegida';
                $this->error($index, 'No alcanza el stock de '.$name.'. Reducí la cantidad o agregá otra fila del producto con stock de otra persona.');
            }
        }
        ksort($prepared);

        return array_merge(...array_values($prepared));
    }

    private function error(int $index, string $message, string $field = 'cantidad'): never
    {
        throw ValidationException::withMessages(["items.$index.$field" => $message]);
    }
}
