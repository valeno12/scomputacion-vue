<?php

namespace App\Http\Controllers;

use App\Models\Proveedor;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProveedorController extends Controller
{
    public function index(Request $request)
    {
        $query = Proveedor::query();
        if ($search = $request->input('search')) {
            $query->where('nombre', 'ilike', "%{$search}%");
        }

        // Ordenamiento
        $sortBy = $request->input('sort_by', 'nombre');
        $sortOrder = $request->input('sort_order', 'asc');

        // Validar columnas permitidas para ordenar
        $allowedSorts = ['id', 'nombre'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        }

        // Paginación
        $perPage = $request->input('per_page', 10);
        $proveedores = $query->paginate($perPage)->withQueryString();

        return Inertia::render('Proveedores/Index', [
            'data' => $proveedores,
            'filters' => [
                'search' => $request->input('search', ''),
                'sort_by' => $sortBy,
                'sort_order' => $sortOrder,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function destroy($id)
    {
        $proveedor = Proveedor::findOrFail($id);
        $proveedor->delete();

        return redirect()->route('proveedor.index')->with('eliminar', 'ok');
    }

    public function create()
    {
        return Inertia::render('Proveedores/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
        ]);
        $proveedor = Proveedor::create($validated);
        if ($request->expectsJson()) {
            return response()->json(['proveedor' => $proveedor], 201);
        }

        return redirect()->route('proveedor.index');
    }

    public function edit($id)
    {
        $proveedor = Proveedor::findOrFail($id);

        return Inertia::render('Proveedores/Edit', [
            'proveedor' => $proveedor,
        ]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
        ]);
        $proveedor = Proveedor::findOrFail($id);
        $proveedor->update($validated);

        return redirect()->route('proveedor.index');
    }

    public function search(Request $request)
    {
        $request->validate(['q' => 'nullable|string|max:200']);
        $query = trim((string) $request->input('q', ''));
        if ($query === '') {
            return response()->json([]);
        }

        $proveedores = Proveedor::query()
            ->whereRaw('LOWER(nombre) LIKE ?', ['%'.mb_strtolower($query).'%'])
            ->orderBy('nombre')
            ->limit(50)
            ->get();

        return response()->json($proveedores);
    }
}
