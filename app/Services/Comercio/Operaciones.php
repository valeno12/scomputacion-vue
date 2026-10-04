<?php

namespace App\Services\Comercio;

use App\Models\CompraRepuesto;
use App\Models\InventarioMovimiento;
use App\Models\LoteStock;
use App\Models\Operacion;
use App\Models\Participante;
use App\Models\Pedido;
use App\Models\Proveedor;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Operaciones
{
    public function __construct(private Repartos $repartos) {}

    public function guardarItems(Operacion $operacion, array $items, bool $stockAsignado = false): void
    {
        if ($operacion->estado !== 'borrador') {
            $this->error('La operación ya fue confirmada. Volvé a pendiente antes de modificarla.');
        }
        $items = app(AsignacionInventario::class)->expandir($operacion, $items, $stockAsignado);
        $purchases = array_filter(array_column($items, 'compra_id'));
        if (count($purchases) !== count(array_unique($purchases))) {
            $this->error('Una compra de repuesto no puede agregarse dos veces.');
        }
        $prepared = [];
        $originals = $operacion->items()->get()->keyBy('id');
        $used = [];
        foreach ($items as $i => $item) {
            $kind = $item['tipo'];
            $original = null;
            if (! empty($item['id'])) {
                $original = $originals->get($item['id']);
                if (! $original || isset($used[$original->id])) {
                    $this->error('Este renglón no pertenece al pedido o está repetido.', "items.$i.id");
                }
            } elseif (empty($item['producto_id'])) {
                // Existing clients may submit the original form without row IDs.
                $original = $originals->first(fn ($row) => ! isset($used[$row->id]) && $row->tipo === $kind && match ($kind) {
                    'stock' => $row->lote_id === (int) ($item['lote_id'] ?? 0),
                    'repuesto' => ! empty($item['compra_id']) ? $row->compra_id === (int) $item['compra_id'] : $row->descripcion === ($item['descripcion'] ?? ''),
                    default => true,
                });
            }
            if ($original) {
                $used[$original->id] = true;
            }
            $sameSource = $original && $original->tipo === $kind
                && ($kind !== 'stock' || $original->lote_id === (int) ($item['lote_id'] ?? 0));
            $quantity = (int) $item['cantidad'];
            $lot = null;
            $buyer = null;
            $cost = 0;
            $purchase = null;
            if ($kind === 'stock') {
                if (empty($item['lote_id'])) {
                    $this->error('Seleccioná un producto del inventario.', "items.$i.producto_id");
                }
                $lot = LoteStock::with('articulo')->findOrFail($item['lote_id']);
                if (! $sameSource && ! $lot->articulo->activo) {
                    $this->error('El artículo seleccionado está archivado.', "items.$i.lote_id");
                }
                $buyer = Participante::findOrFail($sameSource ? $original->comprador_id : $lot->comprador_id);
                $cost = $sameSource ? $original->costo_unitario_centavos : $lot->costo_unitario_centavos;
                $description = $sameSource ? $original->descripcion : trim($lot->articulo->nombre.' '.$lot->articulo->marca);
            } elseif ($kind === 'repuesto') {
                $purchaseId = ($sameSource ? $original->compra_id : null) ?? ($item['compra_id'] ?? null);
                // A recorded purchase keeps its original financier, even after archiving them.
                $buyer = $sameSource ? Participante::find($original->comprador_id) : $this->repartos->titular();
                if (! $buyer || empty(trim($item['descripcion'] ?? ''))) {
                    $this->error('Indicá la descripción del repuesto.', "items.$i.descripcion");
                }
                $cost = Dinero::centavos($item['costo'] ?? '', "items.$i.costo");
                $description = $item['descripcion'];
                $provider = filled($item['proveedor'] ?? null) ? trim($item['proveedor']) : null;
                $providerId = $sameSource ? $original->proveedor_id : null;
                if (! $purchaseId && $provider !== null) {
                    $providerId = (Proveedor::whereRaw('LOWER(nombre) = ?', [mb_strtolower($provider)])->first() ?? Proveedor::create(['nombre' => $provider]))->id;
                } elseif (! $purchaseId && array_key_exists('proveedor', $item) && $provider === null) {
                    $providerId = null;
                }
                if ($purchaseId) {
                    $purchase = CompraRepuesto::where('operacion_id', $operacion->id)->findOrFail($purchaseId);
                    if ($purchase->cantidad !== $quantity || $purchase->costo_unitario_centavos !== $cost || $purchase->comprador_id !== $buyer->id) {
                        $this->error('Una compra registrada conserva su cantidad, costo y comprador. Agregá otro repuesto para una compra nueva.', "items.$i.costo");
                    }
                } elseif (! empty($item['fecha_compra'])) {
                    $purchase = CompraRepuesto::create([
                        'operacion_id' => $operacion->id, 'descripcion' => $description,
                        'comprador_id' => $buyer->id, 'proveedor_id' => $providerId,
                        'cantidad' => $quantity, 'costo_unitario_centavos' => $cost, 'fecha' => $item['fecha_compra'],
                    ]);
                }
            } else {
                $description = 'Mano de obra';
                $quantity = 1;
            }
            $percentage = $kind === 'repuesto' ? ($item['porcentaje_ganancia'] ?? null) : null;
            if ($kind === 'repuesto' && $percentage === null && ! $sameSource) {
                $this->error('Indicá el porcentaje de ganancia del repuesto.', "items.$i.porcentaje_ganancia");
            }
            $unchangedPart = $sameSource && $kind === 'repuesto' && $cost === $original->costo_unitario_centavos
                && $percentage !== null && (float) $percentage === (float) ($original->porcentaje_ganancia ?? ($cost > 0 ? round(($original->precio_unitario_centavos / $cost - 1) * 100, 2) : 0));
            $groupOriginal = $kind === 'stock' && ! empty($item['grupo']) ? $originals->where('tipo', 'stock')->where('producto_id', $item['producto_id'] ?? null)->firstWhere('grupo', $item['grupo']) : null;
            if ($kind === 'stock') {
                // Existing rows retain their recorded price; new rows use the catalog.
                $price = $sameSource ? $original->precio_unitario_centavos : ($groupOriginal?->precio_unitario_centavos ?? $lot->articulo->precio_venta_centavos);
                if (isset($item['precio']) && Dinero::centavos($item['precio'], "items.$i.precio") !== $price) {
                    $this->error($sameSource || $groupOriginal
                        ? 'El producto conserva el precio guardado en el pedido. Recargá el pedido para continuar.'
                        : 'El precio del producto cambió. Quitalo y volvé a agregarlo para revisar el total antes de guardar.', "items.$i.precio");
                }
            } else {
                $price = $unchangedPart ? $original->precio_unitario_centavos : ($kind === 'repuesto' && $percentage !== null
                    ? PrecioProducto::calcular($item['costo'], $percentage, "items.$i.porcentaje_ganancia")
                    : Dinero::centavos($item['precio'] ?? '', "items.$i.precio"));
            }
            $split = $sameSource ? $original->reparto : ($groupOriginal?->reparto ?? $this->repartos->predeterminado($kind));
            $gain = ($price - $cost) * $quantity;
            $distribution = Dinero::repartir($gain, $split);
            foreach ($distribution as &$share) {
                $share['costo_centavos'] = 0;
            }
            unset($share);
            if ($buyer) {
                $index = array_search($buyer->id, array_column($distribution, 'participante_id'));
                if ($index === false) {
                    $recordedBuyer = $sameSource ? collect($original->distribucion)->firstWhere('participante_id', $buyer->id) : null;
                    $distribution[] = ['participante_id' => $buyer->id, 'nombre' => $recordedBuyer['nombre'] ?? $buyer->nombre, 'porcentaje' => 0, 'ganancia_centavos' => 0, 'costo_centavos' => $cost * $quantity];
                } else {
                    $distribution[$index]['costo_centavos'] = $cost * $quantity;
                }
            }
            $prepared[] = [
                'id' => $original?->id,
                'tipo' => $kind, 'descripcion' => $description,
                'compra_id' => $purchase?->id,
                'lote_id' => $lot?->id, 'comprador_id' => $buyer?->id,
                'proveedor_id' => $kind === 'repuesto' ? ($purchase ? $purchase->proveedor_id : $providerId) : ($sameSource ? $original->proveedor_id : $lot?->proveedor_id),
                'cantidad' => $quantity, 'costo_unitario_centavos' => $cost,
                'precio_unitario_centavos' => $price, 'reparto' => $split,
                'distribucion' => $distribution,
                'fecha_compra' => $purchase?->fecha,
            ];
            $last = array_key_last($prepared);
            if (! empty($item['grupo'])) {
                $prepared[$last]['grupo'] = $item['grupo'];
            }
            if (! empty($item['producto_id'])) {
                $prepared[$last]['producto_id'] = $item['producto_id'];
            }
            if ($percentage !== null) {
                $prepared[$last]['porcentaje_ganancia'] = $unchangedPart ? $original->porcentaje_ganancia : $percentage;
            }
            if ($kind === 'repuesto') {
                $prepared[$last]['proveedor_nombre'] = $purchase ? ($original?->proveedor_nombre ?? Proveedor::withTrashed()->find($purchase->proveedor_id)?->nombre) : $provider;
            }
        }
        $purchases = array_filter(array_column($prepared, 'compra_id'));
        if (count($purchases) !== count(array_unique($purchases))) {
            $this->error('Una compra de repuesto no puede agregarse dos veces.');
        }
        $ids = [];
        foreach ($prepared as $row) {
            $id = $row['id'];
            unset($row['id']);
            if ($id) {
                $originals[$id]->fill($row);
                if ($originals[$id]->isDirty()) {
                    $originals[$id]->save();
                }
                $ids[] = $id;
            } else {
                $ids[] = $operacion->items()->create($row)->id;
            }
        }
        $operacion->items()->whereNotIn('id', $ids)->delete();
        $cost = $total = 0;
        foreach ($prepared as $row) {
            $cost += $row['costo_unitario_centavos'] * $row['cantidad'];
            $total += $row['precio_unitario_centavos'] * $row['cantidad'];
        }
        // Keep JSON amounts within JavaScript's exact integer range.
        if (max($cost, $total) > 9_007_199_254_740_991) {
            $this->error('El importe total supera el máximo admitido.');
        }
        $operacion->update(['costo_centavos' => $cost, 'total_centavos' => $total, 'ganancia_centavos' => $total - $cost]);
        $operacion->unsetRelation('items');
    }

    /** The order and operation are locked by the controller's transaction. */
    public function editarPedido(Operacion $op, array $items): void
    {
        $paidSnapshot = $op->fecha_cobro ? $this->snapshotCobrado($op) : null;
        if ($op->estado !== 'confirmada') {
            $this->guardarItems($op, $items);
            if ($paidSnapshot !== null && $paidSnapshot !== $this->snapshotCobrado($op)) {
                $this->error('Una operación cobrada conserva sus importes.', 'items');
            }

            return;
        }
        $before = $this->cantidadesStock($op);
        // The draft state only exists inside the transaction, while editing the same operation.
        $op->estado = 'borrador';
        $this->guardarItems($op, $items, true);
        if ($paidSnapshot !== null && $paidSnapshot !== $this->snapshotCobrado($op)) {
            $this->error('Los importes de una operación cobrada no se modifican desde el presupuesto. Conservá sus renglones; podés editar los datos del pedido.', 'items');
        }
        $after = $this->cantidadesStock($op);
        $lots = LoteStock::whereIn('id', $before->keys()->merge($after->keys())->unique())->orderBy('id')->lockForUpdate()->get();
        foreach ($lots as $lot) {
            $difference = ($after[$lot->id] ?? 0) - ($before[$lot->id] ?? 0);
            if ($difference > $lot->cantidad_disponible) {
                $this->error('Stock insuficiente para '.$lot->articulo->nombre.'. Revisá las cantidades.');
            }
            if ($difference !== 0) {
                $lot->decrement('cantidad_disponible', $difference);
                InventarioMovimiento::create(['lote_id' => $lot->id, 'operacion_id' => $op->id, 'cantidad' => -$difference, 'motivo' => 'edicion_pedido']);
            }
        }
        $op->update(['estado' => 'confirmada']);
    }

    private function cantidadesStock(Operacion $op): Collection
    {
        return $op->items()->where('tipo', 'stock')->get()->groupBy('lote_id')->map(fn ($rows) => $rows->sum('cantidad'));
    }

    private function snapshotCobrado(Operacion $op): string
    {
        return $op->items()->orderBy('id')->get(['id', 'tipo', 'lote_id', 'cantidad', 'costo_unitario_centavos', 'precio_unitario_centavos', 'reparto', 'distribucion'])->toJson();
    }

    public function vender(array $data): Operacion
    {
        return DB::transaction(function () use ($data) {
            // A repeated form submission cannot sell the same stock twice.
            $existing = Operacion::where('clave', $data['clave'])->first();
            if ($existing) {
                return $existing;
            }
            $pedido = empty($data['pedido_id']) ? null : Pedido::lockForUpdate()->findOrFail($data['pedido_id']);
            if ($pedido && ($pedido->comercio_version !== 2 || $pedido->estadoActual_id === 5)) {
                $this->error('Solo se pueden agregar ventas a pedidos actuales que no estén entregados.', 'pedido_id');
            }
            foreach ($data['items'] as $item) {
                if ($item['tipo'] !== 'stock') {
                    $this->error('Las ventas se realizan con productos del inventario.');
                }
            }
            $op = Operacion::firstOrCreate(['clave' => $data['clave']], [
                'tipo' => 'venta', 'pedido_id' => $pedido?->id,
                'cliente_id' => $pedido?->cliente_id ?? ($data['cliente_id'] ?? null),
                'fecha' => $data['fecha'],
            ]);
            if (! $op->wasRecentlyCreated) {
                return $op;
            }
            $this->guardarItems($op, $data['items']);
            if (! $pedido || $pedido->estadoActual_id >= 3) {
                $this->confirmar($op);
            }
            // Adding products to an order never implies that the customer paid.
            if (! $pedido && ! empty($data['cobrar'])) {
                app(Cobros::class)->venta($op, [
                    'fecha_cobro' => $data['fecha_cobro'] ?? $data['fecha'],
                    'medio_pago' => $data['medio_pago'], 'total_esperado' => $op->total_centavos,
                ]);
            }

            return $op->refresh();
        }, 3);
    }

    public function confirmar(Operacion $op): void
    {
        if ($op->estado === 'confirmada') {
            return;
        }
        if ($op->estado !== 'borrador') {
            $this->error('No se puede confirmar una operación anulada.');
        }
        $items = $op->items()->where('tipo', 'stock')->get();
        $needed = $items->groupBy('lote_id')->map(fn ($rows) => $rows->sum('cantidad'));
        $lots = LoteStock::whereIn('id', $needed->keys())->orderBy('id')->lockForUpdate()->get();
        foreach ($lots as $lot) {
            if ($lot->cantidad_disponible < $needed[$lot->id]) {
                $this->error('Stock insuficiente para '.$lot->articulo->nombre.'. Revisá las cantidades.');
            }
            $lot->decrement('cantidad_disponible', $needed[$lot->id]);
            InventarioMovimiento::create(['lote_id' => $lot->id, 'operacion_id' => $op->id, 'cantidad' => -$needed[$lot->id], 'motivo' => $op->tipo]);
        }
        $op->update(['estado' => 'confirmada']);
    }

    public function devolver(Operacion $op, bool $anular = false): void
    {
        if ($op->estado === 'anulada') {
            return;
        }
        if ($op->fecha_cobro && ! $anular) {
            return; // Reopening repair work does not undo an actual payment or a completed sale.
        }
        if ($op->estado === 'confirmada') {
            $quantities = $op->items()->where('tipo', 'stock')->get()->groupBy('lote_id')->map(fn ($rows) => $rows->sum('cantidad'));
            foreach (LoteStock::whereIn('id', $quantities->keys())->orderBy('id')->lockForUpdate()->get() as $lot) {
                $lot->increment('cantidad_disponible', $quantities[$lot->id]);
                InventarioMovimiento::create(['lote_id' => $lot->id, 'operacion_id' => $op->id, 'cantidad' => $quantities[$lot->id], 'motivo' => $anular ? 'anulacion' : 'reapertura']);
            }
        }
        $op->update(['estado' => $anular ? 'anulada' : 'borrador', 'anulada_el' => $anular ? now() : null]);
    }

    public function error(string $message, string $field = 'items'): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
