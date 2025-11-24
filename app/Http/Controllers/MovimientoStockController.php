<?php

namespace App\Http\Controllers;

use App\Models\MovimientoStock;
use App\Models\Producto;
use App\Models\Proveedor;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MovimientoStockController extends Controller
{
    public function index(Request $request)
    {
        // Determinar el tipo de movimiento (entradas o salidas)
        $tipo = $request->get('tipo', 'entradas');
        $tipoMovimiento = $tipo === 'entradas' ? 'entrada' : 'salida';
        
        $query = MovimientoStock::query()
            ->with($tipoMovimiento === 'entrada' 
                ? ['producto', 'proveedor'] 
                : ['producto', 'pedido'])
            ->where('tipo_movimiento', $tipoMovimiento);
        
        // Búsqueda
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search, $tipoMovimiento) {
                $q->whereHas('producto', function($subQ) use ($search) {
                    $subQ->where('nombre', 'ilike', "%{$search}%")
                         ->orWhere('marca', 'ilike', "%{$search}%");
                });
                
                if ($tipoMovimiento === 'entrada') {
                    $q->orWhereHas('proveedor', function($subQ) use ($search) {
                        $subQ->where('nombre', 'ilike', "%{$search}%");
                    });
                } else {
                    $q->orWhereHas('pedido', function($subQ) use ($search) {
                        $subQ->where('codigo', 'ilike', "%{$search}%");
                    });
                }
            });
        }
        
        // Ordenamiento
        $sortBy = $request->get('sort_by', 'fecha');
        $sortOrder = $request->get('sort_order', 'desc');
        
        if (in_array($sortBy, ['fecha', 'cantidad', 'precio'])) {
            $query->orderBy($sortBy, $sortOrder);
        }
        
        // Paginación
        $perPage = $request->get('per_page', 10);
        $movimientos = $query->paginate($perPage)->appends($request->query());
        
        return Inertia::render('MovimientosStock/Index', [
            'data' => $movimientos,
            'filters' => [
                'search' => $request->search,
                'tipo' => $tipo,
                'page' => $request->page,
                'per_page' => $perPage,
                'sort_by' => $sortBy,
                'sort_order' => $sortOrder,
            ],
        ]);
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
        $request->validate([
            'precio' => 'required|numeric|min:0',
            'cantidad' => 'required|integer|min:1',
        ]);
        
        $movimiento = MovimientoStock::where('tipo_movimiento', 'entrada')
            ->findOrFail($id);
        
        $movimiento->precio = $request->precio;
        $movimiento->cantidad = $request->cantidad;
        $movimiento->save();
        
        return redirect()->route('movimientos-stock.index', ['tipo' => 'entradas'])
            ->with('success', 'Entrada de stock actualizada correctamente');
    }
}