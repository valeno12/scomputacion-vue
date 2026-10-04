<?php

namespace App\Http\Controllers;

use App\Services\Comercio\FechaComercial;
use App\Http\Requests\Comercio\OperacionRequest;
use App\Models\Articulo;
use App\Models\CompraRepuesto;
use App\Models\ConfiguracionComercial;
use App\Models\Operacion;
use App\Models\OperacionItem;
use App\Models\Participante;
use App\Models\Pedido;
use App\Models\Proveedor;
use App\Services\Comercio\Dinero;
use App\Services\Comercio\Operaciones;
use App\Services\Comercio\Repartos;
use App\Services\Comercio\StockProductos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ComercioController extends Controller
{
    public function configuracion(Repartos $repartos)
    {
        return Inertia::render('settings/Repartos', [
            ...$repartos->opciones(),
            'todosParticipantes' => Participante::orderBy('nombre')->get(),
        ]);
    }

    public function participante(Request $request, ?Participante $participante = null)
    {
        $data = $request->validate(['nombre' => 'required|string|max:100', 'activo' => 'required|boolean']);
        if ($participante?->id === ConfiguracionComercial::find(1)?->titular_id && ! $data['activo']) {
            throw \Illuminate\Validation\ValidationException::withMessages(['activo' => 'El participante principal es fijo y no puede desactivarse.']);
        }
        if ($participante?->exists) {
            $participante->update($data);
        } else {
            Participante::create($data);
        }

        return back();
    }

    public function guardarConfiguracion(Request $request, Repartos $repartos)
    {
        $rules = [];
        foreach (['reparto_mercaderia'] as $field) {
            $rules[$field] = 'required|array|min:1|max:100';
            $rules["$field.*.participante_id"] = 'required|integer';
            $rules["$field.*.porcentaje"] = 'required|numeric|between:0,100|decimal:0,2';
        }
        $data = $request->validate($rules);
        foreach ($data as $field => $rows) {
            $data[$field] = $repartos->snapshot($rows, $field);
        }
        $owner = $repartos->titular();
        $ownerSplit = $repartos->snapshot([['participante_id' => $owner->id, 'porcentaje' => 100]]);
        $data['reparto_repuestos'] = $ownerSplit;
        $data['reparto_mano_obra'] = $ownerSplit;
        $others = array_values(array_unique(array_filter(array_column($data['reparto_mercaderia'], 'participante_id'), fn ($id) => $id !== $owner->id)));
        $data['hermana_id'] = count($others) === 1 ? $others[0] : null;
        ConfiguracionComercial::updateOrCreate(['id' => 1], $data);

        return back();
    }

    public function inventario()
    {
        return redirect()->route('productos_nuevo.index');
    }

    public function articulo(Request $request, ?Articulo $articulo = null)
    {
        $data = $request->validate(['nombre' => 'required|string|max:200', 'marca' => 'nullable|string|max:100', 'precio' => 'required|numeric|min:0|max:999999999|decimal:0,2', 'activo' => 'required|boolean']);
        $data['precio_venta_centavos'] = Dinero::centavos($data['precio'], 'precio');
        unset($data['precio']);
        if ($articulo?->exists) {
            $articulo->update($data);
        } else {
            Articulo::create($data);
        }

        return back();
    }

    public function ingreso(Request $request, Articulo $articulo, StockProductos $stock)
    {
        abort_unless($articulo->activo, 422, 'El artículo está archivado.');
        $data = $request->validate([
            'comprador_id' => ['required', Rule::exists('participantes', 'id')->where('activo', true)],
            'proveedor_id' => ['nullable', Rule::exists('proveedor', 'id')->whereNull('deleted_at')],
            'cantidad' => 'required|integer|min:1|max:100000',
            'costo' => 'required|numeric|min:0|max:999999999|decimal:0,2',
            'tipo' => ['required', Rule::in(['compra', 'inicial'])],
            'fecha' => 'required|date_format:Y-m-d|before_or_equal:'.FechaComercial::hoy(),
        ]);
        $stock->ingresar($articulo, $data);

        return back();
    }

    public static function opcionesItems(): array
    {
        return [
            'lotes' => [],
            'proveedores' => Proveedor::orderBy('nombre')->get(['id', 'nombre']),
        ];
    }

    public function ventas(Request $request)
    {
        $filters = $request->validate([
            'search' => 'nullable|string|max:200', 'estado' => 'nullable|in:borrador,confirmada,anulada',
            'cobro' => 'nullable|in:pendiente,cobrada,anulada', 'origen' => 'nullable|in:directa,pedido',
            'desde' => 'nullable|date_format:Y-m-d', 'hasta' => 'nullable|date_format:Y-m-d',
            'sort_by' => 'nullable|in:id,fecha,total_centavos,ganancia_centavos', 'sort_order' => 'nullable|in:asc,desc', 'per_page' => 'nullable|integer|between:1,100',
        ]);
        $filters = array_filter($filters, fn ($v) => $v !== null) + ['sort_by' => 'id', 'sort_order' => 'desc', 'per_page' => 10];
        $query = Operacion::with(['cliente', 'pedido', 'items', 'registrador:id,name'])->where('tipo', 'venta')->whereHas('items');
        if ($text = $filters['search'] ?? null) {
            $like = '%'.mb_strtolower($text).'%';
            $query->where(fn ($q) => $q->whereHas('cliente', fn ($c) => $c->whereRaw('LOWER(nombre) LIKE ?', [$like])->orWhereRaw('LOWER(apellido) LIKE ?', [$like]))
                ->orWhereHas('pedido', fn ($p) => $p->whereRaw('LOWER(codigo) LIKE ?', [$like]))
                ->orWhereHas('items', fn ($i) => $i->whereRaw('LOWER(descripcion) LIKE ?', [$like])));
        }
        if ($filters['estado'] ?? null) {
            $query->where('estado', $filters['estado']);
        }
        if (($filters['cobro'] ?? null) === 'anulada') {
            $query->where('estado', 'anulada');
        } elseif ($filters['cobro'] ?? null) {
            $query->where('estado', '!=', 'anulada');
            ($filters['cobro'] === 'cobrada') ? $query->whereNotNull('fecha_cobro') : $query->whereNull('fecha_cobro');
        }
        if ($filters['origen'] ?? null) {
            ($filters['origen'] === 'pedido') ? $query->whereNotNull('pedido_id') : $query->whereNull('pedido_id');
        }
        if ($filters['desde'] ?? null) {
            $query->whereDate('fecha', '>=', $filters['desde']);
        }
        if ($filters['hasta'] ?? null) {
            $query->whereDate('fecha', '<=', $filters['hasta']);
        }

        return Inertia::render('Comercio/Ventas', ['data' => $query->orderBy($filters['sort_by'], $filters['sort_order'])->paginate($filters['per_page'])->withQueryString(), 'filters' => $filters]);
    }

    public function crearVenta(Request $request, Repartos $repartos)
    {
        $pedido = $request->filled('pedido_id') ? Pedido::with('cliente')->findOrFail($request->integer('pedido_id')) : null;
        abort_if($pedido && ($pedido->comercio_version !== 2 || $pedido->estadoActual_id === 5), 422, 'El pedido no admite nuevas ventas.');

        if ($pedido) {
            return redirect()->route('pedido.show', ['id' => $pedido->id, 'agregar_productos' => 1]);
        }

        return Inertia::render('Comercio/VentaForm', [
            ...$repartos->opciones(), ...self::opcionesItems(),
            'pedido' => $pedido,
            'clientes' => [],
        ]);
    }

    public function vender(OperacionRequest $request, Operaciones $operaciones)
    {
        $op = $operaciones->vender($request->validated());

        return $op->pedido_id ? redirect()->route('pedido.show', $op->pedido_id) : redirect()->route('comercio.operacion', $op);
    }

    public function cobrar(\App\Http\Requests\Comercio\CobroRequest $request, Operacion $operacion, \App\Services\Comercio\Cobros $cobros)
    {
        $cobros->venta($operacion, $request->validated());

        return back();
    }

    public function operacion(Operacion $operacion)
    {
        if ($operacion->tipo === 'reparacion' && $operacion->pedido_id) {
            return redirect()->route('pedido.show', $operacion->pedido_id);
        }

        return Inertia::render('Comercio/Operacion', ['operacion' => $operacion->load(['items', 'pedido', 'cliente', 'registrador:id,name'])]);
    }

    public function anular(Operacion $operacion, Operaciones $operaciones)
    {
        abort_unless($operacion->tipo === 'venta', 422);
        DB::transaction(function () use ($operacion, $operaciones) {
            if ($operacion->pedido_id) {
                Pedido::withTrashed()->lockForUpdate()->findOrFail($operacion->pedido_id);
            }
            $op = Operacion::lockForUpdate()->findOrFail($operacion->id);
            $operaciones->devolver($op, true);
        }, 3);

        return back();
    }

    public function comprarRepuesto(Request $request, OperacionItem $item)
    {
        $data = $request->validate(['fecha_compra' => 'required|date_format:Y-m-d|before_or_equal:'.FechaComercial::hoy()]);
        DB::transaction(function () use ($item, $data) {
            $op = Operacion::lockForUpdate()->findOrFail($item->operacion_id);
            abort_if($op->estado === 'anulada', 422);
            $locked = OperacionItem::lockForUpdate()->findOrFail($item->id);
            abort_unless($locked->tipo === 'repuesto', 422);
            if (! $locked->fecha_compra) {
                $purchase = CompraRepuesto::create([
                    'operacion_id' => $op->id, 'descripcion' => $locked->descripcion,
                    'comprador_id' => $locked->comprador_id, 'proveedor_id' => $locked->proveedor_id,
                    'cantidad' => $locked->cantidad, 'costo_unitario_centavos' => $locked->costo_unitario_centavos,
                    'fecha' => $data['fecha_compra'],
                ]);
                $locked->update([...$data, 'compra_id' => $purchase->id]);
            }
        });

        return back();
    }

    public function movimientos()
    {
        return redirect()->route('movimientos-stock.index');
    }
}
