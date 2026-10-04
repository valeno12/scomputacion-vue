<?php

namespace App\Http\Controllers;

use App\Models\Estado;
use App\Models\MovimientoStock;
use App\Models\Pedido;
use App\Models\PedidoEstado;
use App\Models\Producto;
use App\Models\ProductoSeleccionado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PedidoController extends Controller
{
    public function index(Request $request)
    {
        $query = Pedido::with(['cliente', 'estadoActual']);

        // Filtro por tab/estado
        $estadoFilter = $request->get('estado', 'activos');

        switch ($estadoFilter) {
            case 'finalizados':
                $query->where('estadoActual_id', 4)
                    ->with('estadoFinalizado');
                break;
            case 'entregados':
                $query->where('estadoActual_id', 5)
                    ->with('estadoEntregado');
                break;
            case 'activos':
            default:
                $query->whereIn('estadoActual_id', [1, 2, 3]);
                break;
        }

        // Búsqueda
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('codigo', 'ilike', "%{$search}%")
                    ->orWhere('equipo', 'ilike', "%{$search}%")
                    ->orWhereHas('cliente', function ($q) use ($search) {
                        $q->where('nombre', 'ilike', "%{$search}%")
                            ->orWhere('apellido', 'ilike', "%{$search}%")
                            ->orWhere('dni', 'ilike', "%{$search}%");
                    });
            });
        }

        // Ordenamiento
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginación
        $perPage = $request->get('per_page', 10);
        $pedidos = $query->paginate($perPage);

        return Inertia::render('Pedidos/Index', [
            'data' => $pedidos,
            'filters' => [
                'search' => $request->search,
                'estado' => $estadoFilter,
                'page' => $request->page,
                'per_page' => $perPage,
                'sort_by' => $sortBy,
                'sort_order' => $sortOrder,
            ],
        ]);
    }

    public function show($id)
    {
        $pedido = Pedido::with([
            'cliente',
            'estadoActual',
            'productosSeleccionados.producto', 'operaciones.items',
        ])->findOrFail($id);

        // Obtener historial de estados
        $estados = PedidoEstado::where('pedido_id', $id)
            ->with('estado')
            ->orderBy('id', 'asc')
            ->get();

        $estadoActual = $estados->last();
        $siguienteEstado = null;
        if ($pedido->estadoActual_id < 5) {
            $siguienteEstado = Estado::find($pedido->estadoActual_id + 1);
        }

        return Inertia::render('Pedidos/Show', [
            'pedido' => $pedido,
            'estados' => $estados,
            'siguienteEstado' => $siguienteEstado,
            ...($pedido->comercio_version === 2 ? [
                'opcionesComercio' => [...app(\App\Services\Comercio\Repartos::class)->opciones(), ...ComercioController::opcionesItems()],
                'ventasParticipantes' => app(\App\Services\Comercio\ResumenComercial::class)->ventasPorParticipante($pedido->operaciones),
                'titularId' => app(\App\Services\Comercio\Repartos::class)->titular()->id,
            ] : []),
        ]);
    }

    public function edit($id)
    {
        if (Pedido::findOrFail($id)->comercio_version === 2) {
            return app()->call([app(PedidoComercioController::class), 'edit'], ['id' => $id]);
        }

        $pedido = Pedido::with(['cliente', 'productosSeleccionados.producto'])->findOrFail($id);

        return Inertia::render('Pedidos/EditLegacy', [
            'pedido' => $pedido,
            'clienteActual' => $pedido->cliente,
        ]);
    }

    public function update(Request $request, $id)
    {
        if (Pedido::findOrFail($id)->comercio_version === 2) {
            return app()->call([app(PedidoComercioController::class), 'update'], ['id' => $id]);
        }

        $validated = $request->validate([
            'cliente_id' => 'required|exists:cliente,id',
            'equipo' => 'required|string|max:255',
            'estado_ingreso' => 'required|string',
            'cargador' => 'sometimes|boolean',
            'trabajo_realizar' => 'sometimes|nullable|string',
            'costo_mano_obra' => 'sometimes|nullable|numeric|min:0|max:99999999.99',
            'productos' => 'sometimes|nullable|array',
            'productos.*.id' => 'required|integer|exists:producto,id',
            'productos.*.cantidad' => 'required|integer|min:1|max:100000',
            'items' => 'prohibited',
            'cambiar_estado' => 'sometimes|boolean',
        ]);

        DB::transaction(function () use ($validated, $id) {
            $pedido = Pedido::lockForUpdate()->findOrFail($id);
            $anteriores = $pedido->productosSeleccionados()->orderBy('id')->get();
            $productos = collect($validated['productos'] ?? [])->map(fn ($row) => [
                'id' => (int) $row['id'], 'cantidad' => (int) $row['cantidad'],
            ])->all();
            $seleccionAnterior = $anteriores->map(fn ($item) => ['id' => $item->producto_id, 'cantidad' => $item->cantidad])->all();
            $cambiaProductos = array_key_exists('productos', $validated) && $productos != $seleccionAnterior;
            $manoObra = array_key_exists('costo_mano_obra', $validated) ? $validated['costo_mano_obra'] : $pedido->costo_mano_obra;
            $diferenciaManoObra = (float) $manoObra - (float) $pedido->costo_mano_obra;
            $tieneStockDescontado = $pedido->estadoActual_id >= 3;

            if ($cambiaProductos && $tieneStockDescontado) {
                $this->revertirSalidaProductos($id);
            }

            if ($cambiaProductos) {
                // Preserve recorded prices; only newly added legacy products use the old 30% rule.
                $porProducto = $anteriores->groupBy('producto_id');
                $pedido->productosSeleccionados()->delete();
                foreach ($productos as $row) {
                    $anterior = $porProducto->get($row['id'])?->shift();
                    $producto = Producto::withTrashed()->findOrFail($row['id']);
                    abort_if(! $anterior && $producto->trashed(), 422, 'El producto anterior ya fue eliminado.');
                    $precio = $anterior?->precio ?? $producto->precio;
                    $item = new ProductoSeleccionado([
                        'pedido_id' => $pedido->id, 'producto_id' => $producto->id,
                        'cantidad' => $row['cantidad'], 'precio' => $precio,
                    ]);
                    $item->precio_venta = $anterior?->precio_venta ?? round((float) $precio * 1.3, 4);
                    $item->save();
                }
                if ($tieneStockDescontado) {
                    $this->pedidoAprobado($id);
                }
            }

            if ($cambiaProductos || $diferenciaManoObra != 0) {
                $actuales = $cambiaProductos ? $pedido->productosSeleccionados()->get() : $anteriores;
                $costo = fn ($items) => round($items->sum(fn ($item) => (float) $item->precio * $item->cantidad), 2);
                $venta = fn ($items) => round($items->sum(fn ($item) => (float) ($item->precio_venta ?? $item->precio * 1.3) * $item->cantidad), 2);
                $diferenciaCosto = $costo($actuales) - $costo($anteriores);
                $diferenciaVenta = $venta($actuales) - $venta($anteriores) + $diferenciaManoObra;
                // Keep historic totals when editing unrelated data, including previous adjustments.
                $pedido->costo = round((float) $pedido->costo + $diferenciaCosto, 2);
                $pedido->presupuesto = round((float) $pedido->presupuesto + $diferenciaVenta, 2);
                $pedido->ganancia = round((float) $pedido->ganancia + $diferenciaVenta - $diferenciaCosto, 2);
                if (max(abs($pedido->costo), abs($pedido->presupuesto), abs($pedido->ganancia)) > 99_999_999.99) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['productos' => 'El total del pedido no puede superar $99.999.999,99.']);
                }
            }

            $pedido->fill(collect($validated)->only(['cliente_id', 'equipo', 'estado_ingreso', 'cargador', 'trabajo_realizar', 'costo_mano_obra'])->all());
            if (($validated['cambiar_estado'] ?? false) && $pedido->estadoActual_id === 1 && filled($pedido->trabajo_realizar)) {
                $pedido->estadoActual_id = 2;
                PedidoEstado::create(['pedido_id' => $pedido->id, 'estado_id' => 2]);
            }
            $pedido->save();
        }, 3);

        return redirect()->route('pedido.show', $id)->with('success', 'Pedido actualizado exitosamente');
    }

    public function destroy($id)
    {
        $pedido = Pedido::findOrFail($id);
        if ($pedido->comercio_version === 2) {
            return app()->call([app(PedidoComercioController::class), 'destroy'], ['id' => $id]);
        }

        // Original behavior: soft deletion does not represent a stock return.
        $pedido->delete();

        return redirect()->route('pedido.index')->with('eliminar', 'ok');
    }

    public function eliminarEstado($pedido_id, $estado_id)
    {
        if (Pedido::findOrFail($pedido_id)->comercio_version === 2) {
            return app()->call([app(PedidoComercioController::class), 'eliminarEstado'], compact('pedido_id', 'estado_id'));
        }

        DB::transaction(function () use ($pedido_id, $estado_id) {
            $pedido = Pedido::lockForUpdate()->findOrFail($pedido_id);
            $ultimoEstado = PedidoEstado::where('pedido_id', $pedido_id)->orderByDesc('id')->firstOrFail();
            abort_unless($pedido->estadoActual_id > 1 && $ultimoEstado->id === (int) $estado_id, 422, 'Solo se puede revertir el último estado del pedido.');
            if ($pedido->estadoActual_id === 3) {
                $this->revertirSalidaProductos($pedido_id);
            }
            if ($pedido->estadoActual_id === 5) {
                $pedido->fecha_pago = null;
            }
            $ultimoEstado->delete();
            $pedido->estadoActual_id -= 1;
            $pedido->save();
        }, 3);

        return redirect()->route('pedido.show', $pedido_id)->with('success', 'Estado eliminado exitosamente');
    }

    public function actualizarEstado($pedido_id, $estado_id)
    {
        if (Pedido::findOrFail($pedido_id)->comercio_version === 2) {
            return app()->call([app(PedidoComercioController::class), 'actualizarEstado'], compact('pedido_id', 'estado_id'));
        }

        DB::transaction(function () use ($pedido_id, $estado_id) {
            $pedido = Pedido::lockForUpdate()->findOrFail($pedido_id);
            $next = (int) $estado_id;
            if ($next === $pedido->estadoActual_id) {
                return;
            }
            abort_unless($next === $pedido->estadoActual_id + 1 && $next <= 5, 422, 'Solo se puede avanzar al siguiente estado.');
            if ($next === 3) {
                $this->pedidoAprobado($pedido_id);
            }
            if ($next === 5) {
                $pedido->fecha_pago = now();
            }
            $pedido->estadoActual_id = $next;
            $pedido->save();
            PedidoEstado::create(['pedido_id' => $pedido_id, 'estado_id' => $next]);
        }, 3);

        return redirect()->route('pedido.show', $pedido_id)->with('success', 'Estado actualizado exitosamente');
    }

    public function storeAprobacion(Request $request, $id)
    {
        $pedido = Pedido::findOrFail($id);
        if ($pedido->comercio_version === 2) {
            return app()->call([app(PedidoComercioController::class), 'storeAprobacion'], ['id' => $id]);
        }
        $request->validate(['trabajo_realizar' => 'required|string', 'costo_mano_obra' => 'required|numeric|min:0']);
        $request->merge([
            'cliente_id' => $pedido->cliente_id, 'equipo' => $pedido->equipo,
            'estado_ingreso' => $pedido->estado_ingreso, 'cambiar_estado' => true,
        ]);

        return $this->update($request, $id);
    }

    private function pedidoAprobado($id)
    {
        $pedido = Pedido::findOrFail($id);
        $productosSeleccionados = $pedido->productosSeleccionados()->orderBy('producto_id')->get();
        foreach ($productosSeleccionados as $productoSeleccionado) {
            $producto = Producto::withTrashed()->lockForUpdate()->findOrFail($productoSeleccionado->producto_id);
            $producto->cantidad_disponible -= $productoSeleccionado->cantidad;
            $producto->save();
            MovimientoStock::create([
                'producto_id' => $producto->id,
                'pedido_id' => $id,
                'tipo_movimiento' => 'salida',
                'cantidad' => $productoSeleccionado->cantidad,
                'fecha' => now(),
            ]);
        }

    }

    private function revertirSalidaProductos($id)
    {
        $pedido = Pedido::findOrFail($id);
        $productosSeleccionados = $pedido->productosSeleccionados()->orderBy('producto_id')->get();
        foreach ($productosSeleccionados as $productoSeleccionado) {
            $producto = Producto::withTrashed()->lockForUpdate()->findOrFail($productoSeleccionado->producto_id);
            $producto->cantidad_disponible += $productoSeleccionado->cantidad;
            $producto->save();

            // Eliminar el movimiento de stock asociado
            MovimientoStock::where('producto_id', $producto->id)
                ->where('pedido_id', $id)
                ->where('tipo_movimiento', 'salida')
                ->delete();
        }

    }

    public function showI($id)
    {
        return redirect()->route('pedido.show', $id);
    }

    public function editI($id)
    {
        return redirect()->route('pedido.edit', $id);
    }

    public function editF($id)
    {
        return redirect()->route('pedido.edit', $id);
    }
}
