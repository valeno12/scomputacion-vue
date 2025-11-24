<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Models\Estado;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // Obtener todos los pedidos (como antes)
        $pedidos = Pedido::all();
    
        $conteoPedidosPorEstado = [];
    
        // Contar pedidos por estado (exactamente como antes, pero guardando más info)
        foreach ($pedidos as $pedido) {
            $estadoActual = $pedido->estados()->orderBy('pedido_estado.created_at', 'desc')->first();
            if ($estadoActual) {
                if (!isset($conteoPedidosPorEstado[$estadoActual->id])) {
                    $conteoPedidosPorEstado[$estadoActual->id] = [
                        'count' => 1,
                        'nombre' => $estadoActual->nombre,
                        'color' => $estadoActual->color ?? 'primary'
                    ];
                } else {
                    $conteoPedidosPorEstado[$estadoActual->id]['count']++;
                }
            }
        }
        
        // Estadísticas del mes actual
        $mesActual = Carbon::now()->startOfMonth();
        $mesAnterior = Carbon::now()->subMonth()->startOfMonth();
        
        $pedidosEsteMes = Pedido::whereBetween('created_at', [
            $mesActual,
            Carbon::now()
        ])->count();
        
        $pedidosMesAnterior = Pedido::whereBetween('created_at', [
            $mesAnterior,
            $mesActual
        ])->count();

        // Calcular tendencia
        $tendencia = $pedidosMesAnterior > 0 
            ? round((($pedidosEsteMes - $pedidosMesAnterior) / $pedidosMesAnterior) * 100, 1)
            : 0;

        // Pedidos completados este mes - versión simplificada
        $pedidosCompletadosEsteMes = 0;
        $pedidosDelMes = Pedido::whereBetween('created_at', [
            $mesActual,
            Carbon::now()
        ])->get();
        
        foreach ($pedidosDelMes as $pedido) {
            $estadoActual = $pedido->estados()->orderBy('pedido_estado.created_at', 'desc')->first();
            // Ajustá el nombre del estado según tu BD: "Entregado", "Completado", "Finalizado", etc.
            if ($estadoActual && $estadoActual->nombre === 'Entregado') {
                $pedidosCompletadosEsteMes++;
            }
        }
        
        return Inertia::render('Dashboard', [
            'conteoPedidosPorEstado' => $conteoPedidosPorEstado,
            'estadisticasMes' => [
                'pedidosEsteMes' => $pedidosEsteMes,
                'pedidosCompletados' => $pedidosCompletadosEsteMes,
                'tendencia' => $tendencia,
                'mesNombre' => Carbon::now()->locale('es')->isoFormat('MMMM YYYY')
            ]
        ]);
    }
}