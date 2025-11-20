<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClienteController extends Controller
{
    public function index(Request $request)
    {
        $query = Cliente::query();

        // Búsqueda
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('dni', 'like', "%{$search}%")
                  ->orWhere('nombre', 'ilike', "%{$search}%")
                  ->orWhere('apellido', 'ilike', "%{$search}%")
                  ->orWhere('mail', 'ilike', "%{$search}%");
            });
        }

        // Ordenamiento
        $sortBy = $request->input('sort_by', 'apellido');
        $sortOrder = $request->input('sort_order', 'asc');
        
        // Validar columnas permitidas para ordenar
        $allowedSorts = ['dni', 'nombre', 'apellido', 'mail'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
            
            // Si ordena por apellido, secundariamente por nombre
            if ($sortBy === 'apellido') {
                $query->orderBy('nombre', $sortOrder);
            }
        }

        // Paginación
        $perPage = $request->input('per_page', 10);
        $clientes = $query->paginate($perPage)->withQueryString();

        return Inertia::render('Clientes/Index', [
            'data' => $clientes,
            'filters' => [
                'search' => $request->input('search', ''),
                'sort_by' => $sortBy,
                'sort_order' => $sortOrder,
                'per_page' => $perPage,
            ],
        ]);
    }


    public function create()
    {
        return Inertia::render('Clientes/Create');
    }

    public function search(Request $request)
    {
        $query = $request->get('q', '');
        
        // Separar por espacios
        $palabras = array_filter(explode(' ', $query));
        
        $clientes = Cliente::query();
        
        // Para cada palabra, buscar que esté en algún campo
        foreach ($palabras as $palabra) {
            $clientes->where(function($q) use ($palabra) {
                $q->where('nombre', 'ilike', "%{$palabra}%")
                ->orWhere('apellido', 'ilike', "%{$palabra}%")
                ->orWhere('dni', 'ilike', "%{$palabra}%");
            });
        }
        
        $clientes = $clientes
            ->orderBy('apellido')
            ->orderBy('nombre')
            ->limit(50)
            ->get();
        
        return response()->json($clientes);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'dni' => 'required|string|max:20|unique:cliente,dni',
            'nombre' => 'required|string|max:255',
            'apellido' => 'required|string|max:255',
            'direccion' => 'required|string|max:500',
            'telefono' => 'required|string|max:50',
            'mail' => 'required|email|max:255',
        ]);

        $cliente = Cliente::create($validated);

        if ($request->boolean('from_modal')) {
            return back()->with('cliente_creado_id', $cliente->id);
        }
            
        return redirect()->route('cliente.index');
    }

    public function show($id)
    {
        $cliente = Cliente::with(['pedidos' => function($query) {
            $query->with('estadoActual')
                ->orderBy('created_at', 'desc')
                ->limit(10);
        }])->findOrFail($id);
        
        return Inertia::render('Clientes/Show', [
            'cliente' => $cliente,
        ]);
    }

    public function edit($id)
    {
        $cliente = Cliente::findOrFail($id);
        
        return Inertia::render('Clientes/Edit', [
            'cliente' => $cliente,
        ]);
    }

    public function update(Request $request, $id)
    {
        $cliente = Cliente::findOrFail($id);
        
        $validated = $request->validate([
            'dni' => 'required|string|max:20|unique:cliente,dni,' . $cliente->id,
            'nombre' => 'required|string|max:255',
            'apellido' => 'required|string|max:255',
            'direccion' => 'required|string|max:500',
            'telefono' => 'required|string|max:50',
            'mail' => 'required|email|max:255',
        ]);

        $cliente->update($validated);

        return redirect()->route('cliente.index');
            // ->with('success', 'Cliente actualizado exitosamente');
    }

    public function destroy($id)
    {
        $cliente = Cliente::findOrFail($id);
        $cliente->delete();

        return redirect()->route('cliente.index')
            ->with('success', 'Cliente eliminado exitosamente');
    }
}