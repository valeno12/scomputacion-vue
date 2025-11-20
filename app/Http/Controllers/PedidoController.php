<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Estado;
use App\Models\MovimientoStock;
use App\Models\Pedido;
use App\Models\PedidoEstado;
use App\Models\ProductoSeleccionado;
use App\Models\PedidoProductos;
use App\Models\Producto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
            $query->where(function($q) use ($search) {
                $q->where('codigo', 'ilike', "%{$search}%")
                  ->orWhere('equipo', 'ilike', "%{$search}%")
                  ->orWhereHas('cliente', function($q) use ($search) {
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

    public function create()
    {
        return Inertia::render('Pedidos/Create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'cliente_id' => 'required',
            'equipo' => 'required|string',
            'estado_ingreso' => 'required|string',
        ]);
        $cargador = $request->has('cargador') ? true : false;

        $cliente = Cliente::findOrFail($request->cliente_id);
        $nombre = $cliente->nombre;
        $apellido = $cliente->apellido;

        $fecha = now();
        $pedido = Pedido::create([
            'cliente_id' => $request->cliente_id,
            'estado_ingreso' => $request->estado_ingreso,
            'equipo' => $request->equipo,
            'cargador' => $cargador,
            'fecha_ingreso' => $fecha,
            'estadoActual_id' => 1,
        ]);

        $codigo = $this->generarCodigo($pedido->id, $nombre, $apellido);
        $pedido->codigo = $codigo;
        $pedido->save();
        PedidoEstado::create([
            'pedido_id' => $pedido->id,
            'estado_id' => 1,
        ]);

        return redirect()->route('pedido.index')->with('success', 'ok');
    }

    public function obtenerClientes(Request $request) : JsonResponse
    {
        $query = $request->input('q');
        $clientes = Cliente::where('nombre', 'ilike', '%' . $query . '%')
                            ->orWhere('apellido', 'ilike', '%' . $query . '%')
                            ->orWhere('dni', 'ilike', '%' . $query . '%')
                            ->get();

        return response()->json($clientes);
    }

    public function showI($id)
    {
        $pedido = Pedido::findOrFail($id);

        return view('pedido.show_initial', compact('pedido'));
    }

    public function showF($id)
    {
        $pedido = Pedido::findOrFail($id);

        return view('pedido.show_final', compact('pedido'));
    }

    public function editI($id)
    {
        $pedido = Pedido::findOrFail($id);
        $clientes = Cliente::all();
        return view('pedido.editI', compact('pedido', 'clientes'));
    }

    public function editF($id, )
    {
        $pedido = Pedido::findOrFail($id);
        $productosPedido = $pedido->productosSeleccionados;
        $clientes = Cliente::all();
        $productos = Producto::all();
        return view('pedido.editF', compact('pedido', 'clientes','productosPedido','productos'));
    }

    public function updateI($id, Request $request)
    {
        $validatedData = $request->validate([
            'cliente_id' => 'required|exists:cliente,id',
            'equipo' => 'required|string',
            'estado_ingreso' => 'required|string',
        ]);
        $validatedData['cargador'] = $request->has('cargador') ? true : false;

        Pedido::where('id', $id)->update($validatedData);

        return redirect()->route('pedido.index', $id)->with('success', 'ok');
    }

    public function updateF($id, Request $request)
    {
        $validatedData = $request->validate([
            'cliente_id' => 'required|exists:cliente,id',
            'equipo' => 'required|string',
            'estado_ingreso' => 'required|string',
            'trabajo_realizar' => 'required|string',
            'costo_mano_obra' => 'required|numeric',
        ]);
        $validatedData['cargador'] = $request->has('cargador') ? true : false;

        $pedido = Pedido::findOrFail($id);
        $estadoActual = $pedido->estados()->orderBy('pedido_estado.created_at', 'desc')->first();

        if($estadoActual->id >= 3){
            $this->revertirSalidaProductos($id);
        }

        $costo = 0;
        $ganancia = $request->costo_mano_obra;

        $pedido->productosSeleccionados()->delete();

        if($request->has('productos')){
            $productos= $request->productos;
            foreach ($productos as $producto) {
                $idProducto = $producto['id'];
                $produc = Producto::findOrFail($idProducto);
                $cantidad = $producto['cantidad'];
                $costo += $produc->precio * $cantidad;
                $ganancia += (($produc->precio * 1.3) - $produc->precio) * $cantidad;
                ProductoSeleccionado::create([
                    'pedido_id' => $pedido->id,
                    'producto_id' => $idProducto,
                    'cantidad' => $cantidad,
                    'precio' => $produc->precio,
                ]);
            }
            if($estadoActual->id >= 3){
                $this->pedidoAprobado($pedido->id);
            }
        }
        $precioTotal = $costo + $ganancia;
	$pedido->ganancia = $ganancia;
	$pedido->save();
        $pedido->update([
            'cliente_id' => $request->cliente_id,
            'equipo' => $request->equipo,
            'estado_ingreso' =>$request->estado_ingreso,
            'trabajo_realizar' => $request->trabajo_realizar,
            'costo_mano_obra' => $request->costo_mano_obra,
            'costo' => $costo,
            'presupuesto' => $precioTotal,
        ]);

        return redirect()->route('pedido.index', $id)->with('success', 'ok');
    }

    public function destroy($id)
    {
        $Pedido = Pedido::findOrFail($id);
        $Pedido->delete();
        return redirect()->route('pedido.index')->with('eliminar','ok');

    }


    private function generarCodigo($id, $nombres, $apellidos)
    {
        $prefijo= 'PE';
        $iniciales = '';
        $nombres_array = explode(' ', $nombres);
        foreach ($nombres_array as $nombre) {
            $iniciales .= strtoupper(substr($nombre, 0, 1));
        }

        $apellidos_array = explode(' ', $apellidos);
        foreach ($apellidos_array as $apellido) {
            $iniciales .= strtoupper(substr($apellido, 0, 1));
        }

        $numero_id_formateado = str_pad($id, 3, '0', STR_PAD_LEFT);

        $codigo = $prefijo . $numero_id_formateado . $iniciales;

        return $codigo;
    }

    public function verEstados($id)
    {
        $pedido = Pedido::findOrFail($id);
        $estados = PedidoEstado::where('pedido_id', $pedido->id)
        ->orderBy('created_at', 'asc')
        ->get();
        $estadoActual = $estados->last();
        $siguienteEstado = Estado::find($estadoActual->estado_id + 1);
        return view('pedido.verEstados', compact('pedido', 'estados', 'estadoActual', 'siguienteEstado'));
    }

        public function eliminarEstado($pedido_id, $estado_id)
        {
            $ultimoEstado = PedidoEstado::findOrFail($estado_id);
            $ultimoEstado->delete();

            $pedido = Pedido::findOrFail($pedido_id);
            $pedido->estadoActual_id = $pedido->estadoActual_id - 1;
            $pedido->save();

            return redirect()
                ->route('pedido.verestados', ['id' => $pedido_id])
                ->with('eliminar', 'ok');
        }


    public function actualizarEstado($pedido_id, $estado_id)
    {
        $pedido = Pedido::findOrFail($pedido_id);
        if($estado_id == 2){
            $productos = Producto::all();
            return view('pedido.cambiar_estado', compact('pedido', 'productos'));
        }
        if($estado_id == 3){
            $this->pedidoAprobado($pedido_id);
        }
        if($estado_id == 5){
            $pedido->fecha_pago = now();
            $pedido->save();
        }
        $pedido->estadoActual_id = $estado_id;
        $pedido->save();
        $estado = new PedidoEstado();
        $estado->pedido_id = $pedido->id;
        $estado->estado_id = $estado_id;
        $estado->save();

        if($estado_id == 4){
            return redirect()->route('pedido.finalizados')->with('success', 'ok');
        }else if($estado_id == 5){
            return redirect()->route('pedido.entregados')->with('success', 'ok');            
        }else{
            return redirect()->route('pedido.index')->with('success', 'ok');
        }
    }

    public function storeAprobacion(Request $request, $id)
    {
        $pedido = Pedido::findOrFail($id);

        $request->validate([
            'trabajo_realizar' => 'required|string',
            'costo_mano_obra' => 'required|numeric',

        ]);
        $costo = 0;
        $ganancia = $request->costo_mano_obra;
        if($request->has('productos')){
            $productos= $request->productos;
            foreach ($productos as $producto) {
                $idProducto = $producto['id'];
                $produc = Producto::findOrFail($idProducto);
                $cantidad = $producto['cantidad'];
                $costo += $produc->precio * $cantidad;
                $ganancia += (($produc->precio * 1.3) - $produc->precio) * $cantidad;
                ProductoSeleccionado::create([
                    'pedido_id' => $pedido->id,
                    'producto_id' => $idProducto,
                    'cantidad' => $cantidad,
                    'precio' => $produc->precio,
                ]);
            }
        }
        $precioTotal = $costo + $ganancia;
        $pedido->costo_mano_obra = $request->costo_mano_obra;
        $pedido->trabajo_realizar = $request->trabajo_realizar;
        $pedido->componente_problematico = $request->componente_problematico;
        $pedido->presupuesto = $precioTotal;
        $pedido->costo = $costo;
        $pedido->ganancia = $ganancia;
        $pedido->estadoActual_id = 2;
        $pedido->save();
        PedidoEstado::create([
            'estado_id' => 2,
            'pedido_id' => $pedido->id,
        ]);
        return redirect()->route('pedido.index')->with('success', 'ok');
    }

    private function pedidoAprobado($id)
    {
        $pedido = Pedido::findOrFail($id);
        $productosSeleccionados = $pedido->productosSeleccionados;
        foreach ($productosSeleccionados as $productoSeleccionado) {
            $producto = $productoSeleccionado->producto;
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
        $productosSeleccionados = $pedido->productosSeleccionados;
        foreach ($productosSeleccionados as $productoSeleccionado) {
            $producto = $productoSeleccionado->producto;
            $producto->cantidad_disponible += $productoSeleccionado->cantidad;
            $producto->save();

            // Eliminar el movimiento de stock asociado
            $movimiento = MovimientoStock::where('producto_id', $producto->id)
                ->where('pedido_id', $id)
                ->where('tipo_movimiento', 'salida')
                ->first();
            if ($movimiento) {
                $movimiento->delete();
            }
        }

        return redirect()->back()->with('success', 'Revertida la salida de productos correctamente.');
    }

}
