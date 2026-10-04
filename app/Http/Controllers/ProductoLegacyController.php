<?php

namespace App\Http\Controllers;

use App\Models\MovimientoStock;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ProductoLegacyController extends Controller
{
    public function index(Request $request)
    {
        $query = Producto::query()
            ->withSum([
                'productosSeleccionados as cantidad_pendientes' => function ($sub) {
                    $sub->whereHas('pedido', function ($qPedido) {
                        $qPedido->where('estadoActual_id', 2);
                    });
                },
            ], 'cantidad');

        $filters = $request->validate([
            'search' => 'nullable|string|max:255',
            'sort_by' => 'nullable|in:id,nombre,marca,precio,cantidad_disponible',
            'sort_order' => 'nullable|in:asc,desc',
            'per_page' => 'nullable|integer|between:1,100',
            'page' => 'nullable|integer|min:1',
        ]);
        if ($search = $filters['search'] ?? null) {
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(nombre) LIKE ?', ['%'.mb_strtolower($search).'%'])
                    ->orWhereRaw('LOWER(marca) LIKE ?', ['%'.mb_strtolower($search).'%']);
            });
        }

        // Ordenamiento
        $sortBy = $filters['sort_by'] ?? 'cantidad_disponible';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        // Paginación
        $perPage = $filters['per_page'] ?? 10;
        $productos = $query->paginate($perPage)->withQueryString();

        return Inertia::render('ProductosLegacy/Index', [
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
        return redirect()->route('productos_nuevo.index');
    }

    public function store(Request $request)
    {
        abort(422, 'Este catálogo contiene únicamente productos anteriores. Los productos nuevos se cargan en Productos.');
    }

    public function edit($id)
    {
        return Inertia::render('ProductosLegacy/Edit', [
            'producto' => Producto::findOrFail($id),
        ]);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'marca' => 'required|string|max:255',
            'precio' => 'required|numeric|min:0|max:99999999.99|decimal:0,2',
            'cantidad_disponible' => 'sometimes|required|integer',
        ]);
        Producto::findOrFail($id)->update($data);

        return redirect()->route('productos_legacy.index')->with('success', 'Producto anterior actualizado.');
    }

    public function destroy($id)
    {
        DB::transaction(function () use ($id) {
            Producto::findOrFail($id)->delete();
            MovimientoStock::where('producto_id', $id)->delete();
        });

        return redirect()->route('productos_legacy.index')->with('success', 'Producto anterior eliminado.');
    }

    public function search(Request $request)
    {
        $query = $request->get('q', '');

        $productos = Producto::query()
            ->where('cantidad_disponible', '>', 0)
            ->where(function ($q) use ($query) {
                $q->whereRaw('LOWER(nombre) LIKE ?', ['%'.mb_strtolower($query).'%'])
                    ->orWhereRaw('LOWER(marca) LIKE ?', ['%'.mb_strtolower($query).'%']);
            })
            ->orderBy('nombre')
            ->limit(50)
            ->get();

        return response()->json($productos);
    }

    public function buscarNombre(Request $request)
    {
        $query = $request->get('q', '');

        $nombres = Producto::query()
            ->whereRaw('LOWER(nombre) LIKE ?', ['%'.mb_strtolower($query).'%'])
            ->distinct()
            ->orderBy('nombre')
            ->limit(50)
            ->pluck('nombre');

        return response()->json($nombres);
    }

    public function buscarMarca(Request $request)
    {
        $query = $request->get('q', '');

        $marcas = Producto::query()
            ->whereRaw('LOWER(marca) LIKE ?', ['%'.mb_strtolower($query).'%'])
            ->distinct()
            ->orderBy('marca')
            ->limit(50)
            ->pluck('marca');

        return response()->json($marcas);
    }
}
