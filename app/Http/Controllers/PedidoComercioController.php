<?php

namespace App\Http\Controllers;

use App\Services\Comercio\FechaComercial;
use App\Http\Requests\Comercio\CobroRequest;
use App\Http\Requests\Comercio\PedidoRequest;
use App\Models\Pedido;
use App\Models\PedidoEstado;
use App\Services\Comercio\Cobros;
use App\Services\Comercio\Operaciones;
use App\Services\Comercio\Repartos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class PedidoComercioController extends Controller
{
    public function create(Repartos $repartos)
    {
        return Inertia::render('Pedidos/Create', [...$repartos->opciones(), ...ComercioController::opcionesItems()]);
    }

    public function store(PedidoRequest $request, Operaciones $service)
    {
        $pedido = DB::transaction(function () use ($request, $service) {
            $data = $request->validated();
            $pedido = Pedido::create([
                ...collect($data)->only(['cliente_id', 'equipo', 'estado_ingreso', 'cargador'])->all(),
                'comercio_version' => 2, 'fecha_ingreso' => now(), 'estadoActual_id' => 1,
            ]);
            $pedido->generarCodigo();
            PedidoEstado::create(['pedido_id' => $pedido->id, 'estado_id' => 1]);
            $this->presupuestar($pedido, $data, $service);
            if (filled($data['trabajo_realizar'] ?? null)) {
                $pedido->update(['estadoActual_id' => 2]);
                PedidoEstado::create(['pedido_id' => $pedido->id, 'estado_id' => 2]);
            }

            return $pedido;
        });

        return redirect()->route('pedido.show', $pedido->id);
    }

    public function edit($id, Repartos $repartos)
    {
        $pedido = Pedido::with(['cliente', 'operaciones.items.lote.articulo', 'operaciones.items.comprador'])->findOrFail($id);
        abort_unless($pedido->comercio_version === 2, 422);
        foreach ($pedido->operaciones as $op) {
            $rows = $op->items->where('tipo', 'stock');
            $lots = \App\Models\LoteStock::with('comprador')->whereIn('articulo_id', $rows->pluck('lote.articulo_id'))->get();
            foreach ($rows as $row) {
                $choices = $lots->where('articulo_id', $row->lote->articulo_id)->groupBy('comprador_id')->map(function ($group) use ($op, $rows) {
                    $quantity = $group->sum('cantidad_disponible');
                    if ($op->estado === 'confirmada') {
                        $quantity += $rows->whereIn('lote_id', $group->pluck('id'))->sum('cantidad');
                    }

                    return ['id' => $group->first()->comprador_id, 'nombre' => $group->first()->comprador->nombre, 'cantidad' => $quantity];
                })->filter(fn ($choice) => $choice['cantidad'] > 0 || $choice['id'] === $row->comprador_id)->values();
                $row->setAttribute('financiadores', $choices);
            }
        }

        return Inertia::render('Pedidos/Edit', [
            ...$repartos->opciones(), ...ComercioController::opcionesItems(),
            'pedido' => $pedido, 'clienteActual' => $pedido->cliente,
        ]);
    }

    public function update(PedidoRequest $request, $id, Operaciones $service)
    {
        DB::transaction(function () use ($request, $id, $service) {
            $pedido = Pedido::lockForUpdate()->findOrFail($id);
            $data = $request->validated();
            abort_unless($pedido->comercio_version === 2, 422);
            $pedido->update(collect($data)->only(['cliente_id', 'equipo', 'estado_ingreso', 'cargador'])->all());
            $this->presupuestar($pedido, $data, $service);
            if ($request->boolean('cambiar_estado') && $pedido->estadoActual_id === 1 && filled($data['trabajo_realizar'] ?? null)) {
                $pedido->update(['estadoActual_id' => 2]);
                PedidoEstado::create(['pedido_id' => $pedido->id, 'estado_id' => 2]);
            }
        }, 3);

        return redirect()->route('pedido.show', $id);
    }

    private function presupuestar(Pedido $pedido, array $data, Operaciones $service): void
    {
        $op = $pedido->operaciones()->where('tipo', 'reparacion')->lockForUpdate()->first();
        if (! $op) {
            $op = $pedido->operaciones()->create(['clave' => (string) Str::uuid(), 'tipo' => 'reparacion', 'cliente_id' => $pedido->cliente_id, 'fecha' => FechaComercial::hoy()]);
        }
        $items = array_values(array_filter($data['items'], fn ($item) => $item['tipo'] !== 'stock'));
        $products = array_values(array_filter($data['items'], fn ($item) => $item['tipo'] === 'stock'));
        if (isset($data['costo_mano_obra']) && (float) $data['costo_mano_obra'] > 0) {
            $items[] = ['tipo' => 'mano_obra', 'cantidad' => 1, 'precio' => $data['costo_mano_obra']];
        }
        $service->editarPedido($op, $items);
        $venta = $pedido->operaciones()->where('presupuesto_pedido_id', $pedido->id)->lockForUpdate()->first();
        if ($venta?->estado === 'anulada') {
            $venta->update(['presupuesto_pedido_id' => null]);
            $venta = null;
        }
        if ($products || $venta) {
            $venta ??= $pedido->operaciones()->create([
                'clave' => (string) Str::uuid(), 'tipo' => 'venta', 'presupuesto_pedido_id' => $pedido->id,
                'cliente_id' => $pedido->cliente_id, 'fecha' => FechaComercial::hoy(),
            ]);
            $service->editarPedido($venta, $products);
            if ($pedido->estadoActual_id >= 3) {
                $service->confirmar($venta);
            }
            $venta->update(['cliente_id' => $pedido->cliente_id]);
        }
        $cost = $op->costo_centavos + ($venta?->costo_centavos ?? 0);
        $total = $op->total_centavos + ($venta?->total_centavos ?? 0);
        // The historical pedido totals are DECIMAL(10, 2).
        if (max($cost, $total) > 9_999_999_999) {
            $service->error('El total del pedido no puede superar $99.999.999,99.', 'items');
        }
        $op->update(['cliente_id' => $pedido->cliente_id]);
        $pedido->update([
            'trabajo_realizar' => $data['trabajo_realizar'] ?? null,
            'costo_mano_obra' => $data['costo_mano_obra'] ?? 0,
            'costo' => $cost / 100, 'presupuesto' => $total / 100,
            'ganancia' => ($total - $cost) / 100,
        ]);
    }

    public function actualizarEstado(Request $request, $pedido_id, $estado_id, Operaciones $service, Cobros $cobros)
    {
        DB::transaction(function () use ($request, $pedido_id, $estado_id, $service, $cobros) {
            $pedido = Pedido::lockForUpdate()->findOrFail($pedido_id);
            $next = (int) $estado_id;
            if ($next === $pedido->estadoActual_id) {
                return;
            }
            if ($next !== $pedido->estadoActual_id + 1 || $next > 5 || $next < 2) {
                $service->error('Solo se puede avanzar al siguiente estado.', 'estado');
            }
            if ($next === 2 && ! filled($pedido->trabajo_realizar)) {
                $service->error('Completá el presupuesto antes de avanzar.', 'estado');
            }
            if ($pedido->comercio_version === 2) {
                $op = $pedido->operaciones()->where('tipo', 'reparacion')->lockForUpdate()->firstOrFail();
                if ($next === 3) {
                    foreach ($pedido->operaciones()->where('estado', '!=', 'anulada')->orderBy('id')->lockForUpdate()->get() as $operacion) {
                        $service->confirmar($operacion);
                    }
                }
                if ($next === 5) {
                    $data = $request->validate((new CobroRequest)->rules());
                    $cobros->pedido($pedido, $data);
                    $pedido->fecha_pago = $op->fresh()->fecha_cobro ?? $data['fecha_cobro'];
                }
            }
            $pedido->estadoActual_id = $next;
            $pedido->save();
            PedidoEstado::create(['pedido_id' => $pedido->id, 'estado_id' => $next]);
        }, 3);

        return redirect()->route('pedido.show', $pedido_id);
    }

    public function eliminarEstado($pedido_id, $estado_id, Operaciones $service)
    {
        DB::transaction(function () use ($pedido_id, $estado_id, $service) {
            $pedido = Pedido::lockForUpdate()->findOrFail($pedido_id);
            abort_unless($pedido->estadoActual_id > 1 && $pedido->comercio_version === 2, 422, 'No se puede revertir este estado.');
            $entry = PedidoEstado::where('pedido_id', $pedido_id)->orderByDesc('id')->firstOrFail();
            abort_unless($entry->id === (int) $estado_id, 422, 'Solo se puede revertir el último estado.');
            if ($pedido->estadoActual_id === 3) {
                foreach ($pedido->operaciones()->where('estado', '!=', 'anulada')->whereNull('fecha_cobro')->orderBy('id')->lockForUpdate()->get() as $operacion) {
                    $service->devolver($operacion);
                }
            }
            $entry->delete();
            $pedido->update(['estadoActual_id' => $pedido->estadoActual_id - 1]);
        }, 3);

        return redirect()->route('pedido.show', $pedido_id);
    }

    public function destroy($id)
    {
        DB::transaction(function () use ($id) {
            $pedido = Pedido::lockForUpdate()->findOrFail($id);
            abort_unless($pedido->comercio_version === 2, 422);
            // Match the original soft deletion: inventory and recorded sales stay intact.
            $pedido->delete();
        }, 3);

        return redirect()->route('pedido.index');
    }

    public function storeAprobacion(PedidoRequest $request, $id, Operaciones $service)
    {
        return $this->update($request, $id, $service);
    }
}
