<?php

namespace App\Http\Controllers;

use App\Models\InventarioMovimiento;
use App\Models\LoteStock;
use App\Models\MovimientoStock;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Services\Comercio\Dinero;
use App\Services\Comercio\MovimientosStockListado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class MovimientoStockController extends Controller
{
    public function index(Request $request, MovimientosStockListado $listado)
    {
        $filters = $request->validate([
            'tipo' => 'nullable|in:entradas,salidas', 'search' => 'nullable|string|max:255',
            'sort_by' => 'nullable|in:fecha,cantidad,precio', 'sort_order' => 'nullable|in:asc,desc',
            'per_page' => 'nullable|integer|between:1,100', 'page' => 'nullable|integer|min:1',
        ]);
        $filters = array_filter($filters, fn ($value) => $value !== null) + ['tipo' => 'entradas', 'sort_by' => 'fecha', 'sort_order' => 'desc', 'per_page' => 10];

        return Inertia::render('MovimientosStock/Index', ['data' => $listado->paginar($filters), 'filters' => $filters]);
    }

    public function edit($id)
    {
        $movimiento = MovimientoStock::with(['producto', 'proveedor'])
            ->where('tipo_movimiento', 'entrada')
            ->findOrFail($id);

        $productos = Producto::orderBy('nombre')->get();
        $proveedores = Proveedor::orderBy('nombre')->get();

        return Inertia::render('MovimientosStock/Edit', [
            'movimiento' => $movimiento,
            'productos' => $productos,
            'proveedores' => $proveedores,
        ]);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'precio' => 'required|numeric|min:0',
            'cantidad' => 'required|integer|min:1',
        ]);
        MovimientoStock::where('tipo_movimiento', 'entrada')->findOrFail($id)->update($data);

        return redirect()->route('movimientos-stock.index', ['tipo' => 'entradas'])
            ->with('success', 'Entrada de stock actualizada correctamente');
    }

    public function editIngreso(LoteStock $lote)
    {
        $lote->load('articulo');

        return Inertia::render('MovimientosStock/Edit', [
            'movimiento' => [
                'id' => $lote->id, 'producto' => $lote->articulo, 'fecha' => $lote->fecha,
                'cantidad' => $lote->cantidad_inicial, 'precio' => $lote->costo_unitario_centavos / 100,
            ],
            'updateUrl' => route('movimientos-stock.ingresos.update', $lote),
        ]);
    }

    public function updateIngreso(Request $request, LoteStock $lote)
    {
        $data = $request->validate(['precio' => 'required|numeric|min:0|max:999999999|decimal:0,2', 'cantidad' => 'required|integer|between:1,100000']);
        DB::transaction(function () use ($lote, $data) {
            $locked = LoteStock::lockForUpdate()->findOrFail($lote->id);
            $consumido = $locked->cantidad_inicial - $locked->cantidad_disponible;
            if ($data['cantidad'] < $consumido) {
                throw ValidationException::withMessages(['cantidad' => "Ya se utilizaron $consumido unidades de este ingreso."]);
            }
            $locked->update([
                'cantidad_inicial' => $data['cantidad'], 'cantidad_disponible' => $data['cantidad'] - $consumido,
                'costo_unitario_centavos' => Dinero::centavos($data['precio'], 'precio'),
            ]);
            InventarioMovimiento::where('lote_id', $locked->id)->whereIn('motivo', ['compra', 'inicial'])->update(['cantidad' => $data['cantidad']]);
        }, 3);

        return redirect()->route('movimientos-stock.index', ['tipo' => 'entradas'])->with('success', 'Entrada de stock actualizada.');
    }
}
