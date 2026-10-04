<?php

namespace App\Http\Controllers;

use App\Models\MovimientoStock;
use App\Models\Pedido;
use App\Models\Proveedor;
use App\Services\Comercio\ResumenComercial;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RendimientosController extends Controller
{
    public function index(Request $request, ResumenComercial $resumen)
    {
        $request->validate(['selectedMonth' => 'sometimes|integer|between:1,12', 'selectedYear' => 'sometimes|integer|between:2000,2200']);
        $localNow = now()->setTimezone(config('comercio.zona_horaria'));
        $month = (int) $request->input('selectedMonth', $localNow->month);
        $year = (int) $request->input('selectedYear', $localNow->year);
        // New repairs are counted through their operation, never through both sources.
        $legacyIncome = Pedido::with('estadoEntregado')->where('comercio_version', 1)->whereYear('fecha_pago', $year)->whereMonth('fecha_pago', $month)->get();
        $legacyIncome->each(fn ($pedido) => $pedido->setAttribute('orden', ($pedido->estadoEntregado?->created_at ?? $pedido->updated_at ?? $pedido->created_at)?->toISOString()));
        $legacyExpenses = MovimientoStock::with('producto')->where('tipo_movimiento', 'entrada')->whereYear('fecha', $year)->whereMonth('fecha', $month)->get();
        $commerce = $resumen->mes($year, $month);
        $income = (float) $legacyIncome->sum('presupuesto') + $commerce['cobros_centavos'] / 100;
        $expenses = (float) $legacyExpenses->sum(fn ($m) => $m->cantidad * $m->precio) + $commerce['gastos_centavos'] / 100;
        $delivered = Pedido::whereHas('estadoEntregado', fn ($q) => $q->whereYear('created_at', $year)->whereMonth('created_at', $month))->count();
        $count = $legacyIncome->count() + $commerce['operaciones']->count();
        $profit = (float) $legacyIncome->sum('ganancia') + $commerce['ganancia_centavos'] / 100;

        $providerRows = $legacyExpenses->map(fn ($row) => ['proveedor_id' => $row->proveedor_id])
            ->concat($commerce['compras']->map(fn ($row) => ['proveedor_id' => $row['proveedor_id']]));
        $providers = Proveedor::withTrashed()->whereIn('id', $providerRows->pluck('proveedor_id')->filter())->get()->keyBy('id');
        $providerSummary = $providerRows->groupBy(fn ($row) => $row['proveedor_id'] ?? 'none')->map(fn ($rows) => [
            'proveedor_id' => $rows->first()['proveedor_id'], 'cantidad_pedidos' => $rows->count(),
            'proveedor' => ['nombre' => $providers->get($rows->first()['proveedor_id'])?->nombre ?? 'Sin proveedor'],
        ])->values();

        return Inertia::render('Rendimientos/Index', [
            'gananciasPorMes' => [['total_ganancias' => $income]],
            'gastosPorMes' => [['total_gastos' => $expenses]],
            'selectedMonth' => $month, 'selectedYear' => $year,
            'cobrosPorMesDetalles' => $legacyIncome,
            'gastosPorMesDetalles' => $legacyExpenses,
            'gananciaMes' => $income - $expenses,
            'proveedores' => $providerSummary,
            'pedidosEntregadosMes' => $delivered,
            'promedioGananciaPorPedido' => $count ? $profit / $count : 0,
            'comercio' => $commerce,
            'gananciaTitular' => $profit,
            'legacyResumen' => ['cobros_centavos' => (int) round($legacyIncome->sum('presupuesto') * 100), 'ganancia_centavos' => (int) round($legacyIncome->sum('ganancia') * 100), 'gastos_centavos' => (int) round($legacyExpenses->sum(fn ($m) => $m->cantidad * $m->precio) * 100)],
        ]);
    }
}
