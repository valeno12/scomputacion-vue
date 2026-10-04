<?php

namespace App\Services\Comercio;

use App\Models\LoteStock;
use App\Models\Operacion;
use App\Models\Participante;

class ResumenComercial
{
    public function mes(int $year, int $month): array
    {
        $owner = app(Repartos::class)->titular();
        $operations = Operacion::with(['items', 'pedido'])->where('estado', 'confirmada')
            ->where(fn ($query) => $query->where('tipo', 'venta')->orWhereHas('pedido', fn ($pedido) => $pedido->whereNull('pedido.deleted_at')))
            ->whereYear('fecha_cobro', $year)->whereMonth('fecha_cobro', $month)->orderByDesc('fecha_cobro')->orderByDesc('cobrado_en')->orderByDesc('id')->get();
        $lots = LoteStock::with(['articulo', 'comprador', 'proveedor'])->where('tipo', 'compra')->whereYear('fecha', $year)->whereMonth('fecha', $month)->orderByDesc('fecha')->orderByDesc('created_at')->orderByDesc('id')->get();
        $shares = [];
        $categories = collect(['venta' => 'Ventas de productos', 'reparacion' => 'Pedidos'])->map(fn ($label, $key) => ['tipo' => $key, 'nombre' => $label, 'cantidad' => 0, 'costo_centavos' => 0, 'ganancia_centavos' => 0, 'total_centavos' => 0])->all();
        $rows = [];
        foreach ($operations as $op) {
            foreach ($op->items->groupBy(fn ($item) => $item->tipo === 'stock' ? 'venta' : 'reparacion') as $kind => $items) {
                $ownerTotal = $ownerGain = $otherTotal = $gross = $gain = 0;
                foreach ($items as $item) {
                    $gross += $item->cantidad * $item->precio_unitario_centavos;
                    $gain += $item->cantidad * ($item->precio_unitario_centavos - $item->costo_unitario_centavos);
                    foreach ($item->distribucion as $share) {
                        $id = $share['participante_id'];
                        // Parts contribute their margin only. Their purchase is not an inventory expense.
                        $cost = $kind === 'venta' ? $share['costo_centavos'] : 0;
                        $profit = $share['ganancia_centavos'];
                        $total = $cost + $profit;
                        $shares[$id] ??= ['participante_id' => $id, 'nombre' => $share['nombre'], 'costo_centavos' => 0, 'ganancia_centavos' => 0, 'total_centavos' => 0];
                        $shares[$id]['costo_centavos'] += $cost;
                        $shares[$id]['ganancia_centavos'] += $profit;
                        $shares[$id]['total_centavos'] += $total;
                        if ($id == $owner->id) {
                            $ownerTotal += $total;
                            $ownerGain += $profit;
                            $categories[$kind]['costo_centavos'] += $cost;
                            $categories[$kind]['ganancia_centavos'] += $profit;
                            $categories[$kind]['total_centavos'] += $total;
                        } else {
                            $otherTotal += $total;
                        }
                    }
                }
                $categories[$kind]['cantidad']++;
                $rows[] = [...$op->only(['id', 'pedido_id', 'fecha_cobro', 'cobrado_en']), 'pedido_codigo' => $op->pedido?->codigo, 'tipo' => $kind, 'total_centavos' => $gross,
                    'ganancia_centavos' => $gain, 'descripcion' => $kind === 'venta' ? 'Venta #'.$op->id : 'Pedido '.($op->pedido?->codigo ?? '#'.$op->id),
                    'titular_centavos' => $ownerTotal, 'ganancia_titular_centavos' => $ownerGain, 'otros_centavos' => $otherTotal];
            }
        }
        $purchases = $lots->map(fn ($l) => ['id' => 'stock-'.$l->id, 'proveedor_id' => $l->proveedor_id, 'url' => '/productos/'.$l->articulo_id,
            'descripcion' => $l->articulo->nombre, 'fecha' => $l->fecha, 'total_centavos' => $l->cantidad_inicial * $l->costo_unitario_centavos,
            'tipo' => 'Productos', 'comprador_id' => $l->comprador_id, 'comprador' => $l->comprador->nombre,
            'proveedor' => $l->proveedor?->nombre ?? 'Sin proveedor', 'cantidad' => $l->cantidad_inicial,
            'orden' => $l->created_at?->toISOString()])->values();

        return [
            'titular' => $owner->only(['id', 'nombre']),
            'cobros_centavos' => $shares[$owner->id]['total_centavos'] ?? 0,
            'gastos_centavos' => $purchases->where('comprador_id', $owner->id)->sum('total_centavos'),
            'ganancia_centavos' => $shares[$owner->id]['ganancia_centavos'] ?? 0,
            'costo_recuperado_centavos' => $shares[$owner->id]['costo_centavos'] ?? 0,
            'cobros_totales_centavos' => $operations->sum('total_centavos'),
            'categorias' => array_values($categories),
            'distribucion' => array_values($shares),
            'ventas_por_participante' => $this->ventasPorParticipante($operations),
            'operaciones' => collect($rows),
            'compras' => $purchases,
        ];
    }

    public function ventasPorParticipante($operations): array
    {
        $rows = Participante::orderBy('nombre')->get()->mapWithKeys(fn ($person) => [$person->id => [
            'participante_id' => $person->id, 'nombre' => $person->nombre, 'activo' => $person->activo,
            'cantidad' => 0, 'costo_centavos' => 0, 'ganancia_centavos' => 0, 'total_centavos' => 0,
        ]])->all();
        foreach ($operations as $operation) {
            if ($operation->estado !== 'confirmada' || ! $operation->fecha_cobro) {
                continue;
            }
            $seen = [];
            foreach ($operation->items->where('tipo', 'stock') as $item) {
                foreach ($item->distribucion as $share) {
                    $id = $share['participante_id'];
                    $rows[$id]['costo_centavos'] += $share['costo_centavos'];
                    $rows[$id]['ganancia_centavos'] += $share['ganancia_centavos'];
                    $rows[$id]['total_centavos'] += $share['costo_centavos'] + $share['ganancia_centavos'];
                    $seen[$id] = true;
                }
            }
            foreach (array_keys($seen) as $id) {
                $rows[$id]['cantidad']++;
            }
        }

        return array_values($rows);
    }
}
