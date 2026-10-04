<?php

namespace App\Http\Controllers;

use App\Services\Comercio\FechaComercial;
use App\Models\LoteStock;
use App\Models\OperacionItem;
use App\Models\Participante;
use App\Models\ProductoNuevo;
use App\Services\Comercio\PreciosProductos;
use App\Services\Comercio\StockProductos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ProductoNuevoController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => 'nullable|string|max:255', 'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|between:1,100',
            'sort_by' => 'nullable|in:id,nombre,marca,costo_unitario_centavos,precio_venta_centavos,cantidad_disponible',
            'sort_order' => 'nullable|in:asc,desc',
        ]);
        $filters = array_filter($filters, fn ($value) => $value !== null) + ['sort_by' => 'cantidad_disponible', 'sort_order' => 'desc', 'per_page' => 10];
        $query = ProductoNuevo::where('activo', true)->select('productos_nuevo.*')
            ->withExists('lotes as tiene_ingresos')
            ->selectSub(LoteStock::selectRaw('COALESCE(SUM(cantidad_disponible), 0)')->whereColumn('articulo_id', 'productos_nuevo.id'), 'cantidad_disponible')
            ->selectSub(LoteStock::select('costo_unitario_centavos')->whereColumn('articulo_id', 'productos_nuevo.id')->orderByDesc('fecha')->orderByDesc('id')->limit(1), 'costo_unitario_centavos');
        if ($search = $filters['search'] ?? null) {
            $query->where(fn ($q) => $q->whereRaw('LOWER(nombre) LIKE ?', ['%'.mb_strtolower($search).'%'])
                ->orWhereRaw('LOWER(marca) LIKE ?', ['%'.mb_strtolower($search).'%']));
        }

        return Inertia::render('Productos/Index', [
            'data' => $query->orderBy($filters['sort_by'], $filters['sort_order'])->orderBy('id')->paginate($filters['per_page'])->withQueryString(),
            'filters' => $filters,
        ]);
    }

    public function create()
    {
        return Inertia::render('Productos/Create');
    }

    public function show(ProductoNuevo $producto)
    {
        return Inertia::render('Productos/Show', [
            'producto' => $producto->load('registrador:id,name'),
            'stock' => (int) $producto->lotes()->sum('cantidad_disponible'),
            'ingresos' => $producto->lotes()->with(['comprador:id,nombre', 'proveedor:id,nombre', 'registrador:id,name'])->orderByDesc('fecha')->orderByDesc('id')->paginate(20, ['*'], 'ingresos_page')->withQueryString(),
            'ventas' => OperacionItem::whereHas('lote', fn ($q) => $q->where('articulo_id', $producto->id))
                ->with(['operacion.items', 'operacion.cliente:id,nombre,apellido', 'operacion.pedido:id,codigo,deleted_at', 'operacion.registrador:id,name', 'comprador:id,nombre'])
                ->latest('id')->paginate(20, ['*'], 'ventas_page')->withQueryString(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->productoRules());
        $producto = DB::transaction(function () use ($data) {
            $nombre = trim($data['nombre']);
            $marca = trim($data['marca']);
            $this->validarDuplicado($nombre, $marca);

            return ProductoNuevo::create(['nombre' => $nombre, 'marca' => $marca, 'precio_venta_centavos' => 0]);
        }, 3);

        if ($request->expectsJson()) {
            return response()->json(['producto' => $producto->fresh()->loadExists('lotes as tiene_ingresos')], 201);
        }

        return redirect()->route('productos_nuevo.index')->with('success', 'Producto creado.');
    }

    public function edit(ProductoNuevo $producto)
    {
        abort_unless($producto->activo, 404);

        return Inertia::render('Productos/Edit', ['producto' => $producto]);
    }

    public function update(Request $request, ProductoNuevo $producto)
    {
        abort_unless($producto->activo, 404);
        $data = $request->validate($this->productoRules());
        $this->validarDuplicado(trim($data['nombre']), trim($data['marca']), $producto->id);
        $producto->update(['nombre' => trim($data['nombre']), 'marca' => trim($data['marca'])]);

        return redirect()->route('productos_nuevo.index')->with('success', 'Producto actualizado.');
    }

    public function destroy(ProductoNuevo $producto)
    {
        $producto->update(['activo' => false]);

        return redirect()->route('productos_nuevo.index')->with('success', 'Producto eliminado.');
    }

    public function ingreso(ProductoNuevo $producto)
    {
        abort_unless($producto->activo, 404);

        return Inertia::render('Productos/Ingreso', [
            'producto' => $producto, 'participantes' => $this->participantes(),
            'ultimoCosto' => $this->ultimoCosto($producto),
        ]);
    }

    public function guardarIngreso(Request $request, ProductoNuevo $producto, StockProductos $stock)
    {
        $data = $request->validate($this->ingresoRules() + $this->precioRules());
        $stock->ingresar($producto, $data);

        return redirect()->route('productos_nuevo.index')->with('success', 'Ingreso de stock registrado.');
    }

    public function nuevoIngreso(Request $request)
    {
        return Inertia::render('Productos/IngresoMultiple', [
            'participantes' => $this->participantes(),
            'productosIniciales' => $this->seleccionarProductos($request),
        ]);
    }

    public function buscar(Request $request)
    {
        $data = $request->validate(['q' => 'nullable|string|max:200', 'inventario' => 'sometimes|boolean']);
        if (trim($data['q'] ?? '') === '') {
            return response()->json(['data' => [], 'has_more' => false]);
        }
        $query = ProductoNuevo::where('activo', true)->select('productos_nuevo.*')
            ->selectSub(LoteStock::selectRaw('COALESCE(SUM(cantidad_disponible), 0)')->whereColumn('articulo_id', 'productos_nuevo.id'), 'cantidad_disponible')
            ->selectSub(LoteStock::select('costo_unitario_centavos')->whereColumn('articulo_id', 'productos_nuevo.id')->orderByDesc('fecha')->orderByDesc('id')->limit(1), 'costo_unitario_centavos');
        if ($search = trim($data['q'] ?? '')) {
            foreach (preg_split('/\s+/', mb_strtolower($search)) as $term) {
                $query->where(fn ($q) => $q->whereRaw('LOWER(nombre) LIKE ?', ['%'.$term.'%'])->orWhereRaw('LOWER(marca) LIKE ?', ['%'.$term.'%']));
            }
        }
        if ($request->boolean('inventario')) {
            $query->whereHas('lotes', fn ($q) => $q->where('cantidad_disponible', '>', 0))
                ->with(['lotes' => fn ($q) => $q->where('cantidad_disponible', '>', 0)->orderBy('fecha')->orderBy('id'), 'lotes.comprador']);
        }
        $results = $query->orderBy('nombre')->orderBy('id')->limit(21)->get();
        if ($request->boolean('inventario')) {
            $results->each(function ($product) {
                $product->setAttribute('financiadores', $product->lotes->groupBy('comprador_id')->map(fn ($rows) => [
                    'id' => $rows->first()->comprador_id, 'nombre' => $rows->first()->comprador->nombre,
                    'cantidad' => $rows->sum('cantidad_disponible'),
                ])->values());
            });
        }

        return response()->json(['data' => $results->take(20)->values(), 'has_more' => $results->count() > 20]);
    }

    public function seleccion(Request $request)
    {
        return response()->json(['data' => $this->seleccionarProductos($request)]);
    }

    private function seleccionarProductos(Request $request)
    {
        $data = $request->validate([
            'productos' => 'sometimes|array|min:1|max:100',
            'productos.*' => ['required', 'integer', 'distinct', Rule::exists('productos_nuevo', 'id')->where('activo', true)],
        ], ['productos.*.exists' => 'Un producto seleccionado ya no está disponible. Actualizá la selección.']);
        $ids = $data['productos'] ?? [];
        $productos = ProductoNuevo::where('activo', true)->whereIn('id', $ids)->select('productos_nuevo.*')
            ->selectSub(LoteStock::select('costo_unitario_centavos')->whereColumn('articulo_id', 'productos_nuevo.id')->orderByDesc('fecha')->orderByDesc('id')->limit(1), 'costo_unitario_centavos')
            ->get()->keyBy('id');
        if ($productos->count() !== count($ids)) {
            throw ValidationException::withMessages(['productos' => 'Un producto seleccionado ya no está disponible. Actualizá la selección.']);
        }

        return collect($ids)->map(fn ($id) => $productos->get($id))->filter()->values();
    }

    public function aumentarPrecios(Request $request, PreciosProductos $precios)
    {
        $data = $request->validate([
            'porcentaje' => 'required|numeric|gt:0|max:10000|decimal:0,2',
            'items' => 'required|array|min:1|max:100',
            'items.*.producto_id' => 'required|integer|distinct',
            'items.*.precio_actual_centavos' => 'required|integer|min:0|max:99999999900',
        ]);
        $precios->aumentar($data);

        return back()->with('success', 'Precios de venta actualizados.');
    }

    public function guardarIngresoMultiple(Request $request, StockProductos $stock)
    {
        $rules = $this->datosIngresoRules() + ['clave' => 'required|uuid', 'items' => 'required|array|min:1|max:100'];
        $rules['items.*.producto_id'] = ['required', 'integer', 'distinct', Rule::exists('productos_nuevo', 'id')->where('activo', true)];
        $rules['items.*.cantidad'] = 'required|integer|between:1,100000';
        foreach ($this->precioRules() as $field => $rule) {
            $rules["items.*.$field"] = $rule;
        }
        $stock->ingresarVarios($request->validate($rules));

        return redirect()->route('productos_nuevo.index')->with('success', 'Ingreso registrado.');
    }

    private function ultimoCosto(ProductoNuevo $producto): ?int
    {
        return $producto->lotes()->orderByDesc('fecha')->orderByDesc('id')->value('costo_unitario_centavos') ?? $producto->costo_referencia_centavos;
    }

    private function precioRules(): array
    {
        return ['costo' => 'required|numeric|min:0|max:999999999|decimal:0,2', 'porcentaje_ganancia' => 'required|numeric|between:0,10000|decimal:0,2'];
    }

    private function validarDuplicado(string $nombre, string $marca, ?int $ignorarId = null): void
    {
        if (ProductoNuevo::where('activo', true)->when($ignorarId, fn ($q) => $q->where('id', '!=', $ignorarId))
            ->whereRaw('LOWER(nombre) = ?', [mb_strtolower($nombre)])
            ->whereRaw('LOWER(COALESCE(marca, ?)) = ?', ['', mb_strtolower($marca)])->exists()) {
            throw ValidationException::withMessages(['nombre' => 'Ese producto ya existe. Usá Ingresar stock para registrar otra compra.']);
        }
    }

    private function productoRules(): array
    {
        return ['nombre' => 'required|string|max:200', 'marca' => 'required|string|max:100'];
    }

    private function ingresoRules(): array
    {
        return [
            'clave' => 'required|uuid', 'cantidad' => 'required|integer|between:1,100000',
        ] + $this->precioRules() + $this->datosIngresoRules();
    }

    private function datosIngresoRules(): array
    {
        return [
            'comprador_id' => ['required', Rule::exists('participantes', 'id')->where('activo', true)],
            'proveedor' => 'required|string|max:255',
            'tipo' => ['required', Rule::in(['compra', 'inicial'])],
            'fecha' => 'required|date_format:Y-m-d|before_or_equal:'.FechaComercial::hoy(),
        ];
    }

    private function participantes()
    {
        return Participante::where('activo', true)->orderBy('nombre')->get(['id', 'nombre', 'activo']);
    }
}
