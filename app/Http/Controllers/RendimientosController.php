<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pedido;
use App\Models\MovimientoStock;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class RendimientosController extends Controller
{
    public function index(Request $request)
    {
        // Obtener el mes y el año seleccionados del request o usar el mes y año actuales como valores predeterminados
        $selectedMonth = $request->input('selectedMonth', date('n'));
        $selectedYear = $request->input('selectedYear', date('Y'));
    
        // Calcular ganancias por mes
        $gananciasPorMes = Pedido::whereYear('fecha_pago', $selectedYear)
            ->whereMonth('fecha_pago', $selectedMonth)
            ->whereNotNull('fecha_pago')
            ->select(DB::raw('SUM(presupuesto) as total_ganancias'))
            ->groupBy(DB::raw('EXTRACT(MONTH FROM fecha_pago)'))
            ->get();
    
        // Calcular gastos por mes
        $gastosPorMes = MovimientoStock::where('tipo_movimiento', 'entrada')
            ->whereYear('fecha', $selectedYear)
            ->whereMonth('fecha', $selectedMonth)
            ->select(DB::raw('SUM(cantidad * precio) as total_gastos'))
            ->groupBy(DB::raw('EXTRACT(MONTH FROM fecha)'))
            ->get();
    
        // Obtener detalles de cobros por mes
        $cobrosPorMesDetalles = Pedido::whereYear('fecha_pago', $selectedYear)
            ->whereMonth('fecha_pago', $selectedMonth)
            ->whereNotNull('fecha_pago')
            ->get();
    
        // Obtener detalles de gastos por mes
        $gastosPorMesDetalles = MovimientoStock::where('tipo_movimiento', 'entrada')
            ->whereYear('fecha', $selectedYear)
            ->whereMonth('fecha', $selectedMonth)
            ->with('producto') // Eager load para la vista
            ->get();
        
        $proveedores = MovimientoStock::where('tipo_movimiento', 'entrada')
            ->with('proveedor')
            ->select('proveedor_id', DB::raw('count(*) as cantidad_pedidos'))
            ->groupBy('proveedor_id')
            ->get();
            
        // Organizar los detalles de cobros y gastos por mes para pasar a la vista
        $detalleCobrosGastos = [];
        foreach ($cobrosPorMesDetalles as $cobro) {
            $detalleCobrosGastos[$cobro->fecha_pago]['cobros'][] = $cobro;
        }
        foreach ($gastosPorMesDetalles as $gasto) {
            $detalleCobrosGastos[$gasto->fecha]['gastos'][] = $gasto;
        }
        
        // Calcular ganancia del mes como la diferencia entre gastos y cobros
        $gananciaMes = $gananciasPorMes->sum('total_ganancias') - $gastosPorMes->sum('total_gastos');
        
        $pedidosEntregadosMes = DB::table('pedido_estado')
            ->where('estado_id', 5)
            ->whereYear('created_at', $selectedYear)
            ->whereMonth('created_at', $selectedMonth)
            ->distinct('pedido_id')
            ->count('pedido_id');

        $promedioGananciaPorPedido = $pedidosEntregadosMes > 0 
            ? $gananciaMes / $pedidosEntregadosMes 
            : 0;

        return Inertia::render('Rendimientos/Index', [
            'gananciasPorMes' => $gananciasPorMes,
            'gastosPorMes' => $gastosPorMes,
            'selectedMonth' => (int) $selectedMonth,
            'selectedYear' => (int) $selectedYear,
            'cobrosPorMesDetalles' => $cobrosPorMesDetalles,
            'gastosPorMesDetalles' => $gastosPorMesDetalles,
            'gananciaMes' => $gananciaMes,
            'proveedores' => $proveedores,
            'pedidosEntregadosMes' => $pedidosEntregadosMes,
            'promedioGananciaPorPedido' => $promedioGananciaPorPedido,
        ]);
    }
}