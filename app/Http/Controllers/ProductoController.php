<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Proovedor;
use App\Models\ProductoSeleccionado;
use Illuminate\Http\Request;
use App\Models\MovimientoStock;
use App\Models\Proveedor;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;

class ProductoController extends Controller
{
    public function index(Request $request)
    {
        $query = Producto::query()
            ->withSum([
                'productosSeleccionados as cantidad_pendientes' => function ($sub) {
                    $sub->whereHas('pedido', function ($qPedido) {
                        $qPedido->where('estadoActual_id', 2);
                    });
                }
            ],'cantidad');
        
        // Búsqueda
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nombre', 'ilike', "%{$search}%")
                ->orWhere('marca', 'ilike', "%{$search}%");
            });
        }
        
        // Ordenamiento
        $sortBy = $request->get('sort_by', 'cantidad_disponible');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);
        
        // Paginación
        $perPage = $request->get('per_page', 10);
        $productos = $query->paginate($perPage);
        
        return Inertia::render('Productos/Index', [
            'data' => $productos,
            'filters' => [
                'search' => $request->search,
                'page' => $request->page,
                'per_page' => $perPage,
                'sort_by' => $sortBy,
                'sort_order' => $sortOrder,
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('Productos/Create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'marca' => 'required|string|max:255',
            'proveedor'=> 'required|string|max:255',
            'precio' => 'required|numeric|min:0',
            'cantidad_disponible' => 'required|integer|min:0',
        ]);
        $producto = Producto::where('nombre', $request->nombre)
                        ->where('marca', $request->marca)
                        ->first();

        if ($producto) {
            $producto->update([
                'cantidad_disponible' => $producto->cantidad_disponible + $request->cantidad_disponible,
                'precio' => $request->precio
            ]);
        } else {
        $producto = Producto::create($request->all());
        }

        $proveedor = Proveedor::where('nombre', $request->proveedor)->first();

        if(!$proveedor){
            $proveedor = Proveedor::create([
                'nombre' => $request->proveedor,
            ]);
        }

        MovimientoStock::create([
            'producto_id' => $producto->id,
            'tipo_movimiento' => 'entrada',
            'cantidad' => $request->cantidad_disponible,
            'proveedor_id' => $proveedor->id,
            'precio' => $request->precio,
            'fecha' => now(),
        ]);

        return redirect()->route('producto.index')->with('success', 'ok');
    }

    public function show(Producto $producto)
    {
        return view('producto.show', compact('producto'));
    }

    public function edit($id)
    {
        $producto = Producto::findOrFail($id);
        return Inertia::render('Productos/Edit',[
            'producto' => $producto,
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'marca' => 'required|string|max:255',
            'precio' => 'required|numeric|min:0',
        ]);
        $producto = Producto::findOrFail($id);
        $producto->update($request->all());

        return redirect()->route('producto.index')->with('success', 'ok');
    }

    public function destroy($id)
    {
        $producto=Producto::findOrFail($id);
        $producto->delete();
        $movimiento = MovimientoStock::where('producto_id', $id);
        $movimiento->delete();
        return redirect()->route('producto.index')->with('eliminado', 'ok');
    }

    public function search(Request $request)
    {   
        $query = $request->get('q', '');
        
        $productos = Producto::query()
            ->where('cantidad_disponible', '>', 0)
            ->where(function($q) use ($query) {
                $q->where('nombre', 'ilike', "%{$query}%")
                ->orWhere('marca', 'ilike', "%{$query}%");
            })
            ->orderBy('nombre')
            ->limit(50)
            ->get();
        
        return response()->json($productos);
    }

    public function buscarNombre(Request $request)
    {
        $query = $request->get('q','');

        $nombres = Producto::query()
            ->where('nombre', 'ilike', "%{$query}%")
            ->distinct()
            ->orderBy('nombre')
            ->limit(50)
            ->pluck('nombre');
        
        return response()->json($nombres);
    }
    
    public function buscarMarca(Request $request)
    {
        $query = $request->get('q','');

        $marcas = Producto::query()
            ->where('marca', 'ilike', "%{$query}%")
            ->distinct()
            ->orderBy('marca')
            ->limit(50)
            ->pluck('marca');
        
        return response()->json($marcas);
    }
}
