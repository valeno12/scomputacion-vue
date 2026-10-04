<?php

namespace App\Services\Comercio;

use Illuminate\Support\Facades\DB;

final class MovimientosStockListado
{
    public function paginar(array $filters)
    {
        $entradas = ($filters['tipo'] ?? 'entradas') === 'entradas';
        $legacy = DB::table('movimiento_stock as m')
            ->leftJoin('producto as p', 'p.id', '=', 'm.producto_id')
            ->leftJoin('proveedor as pv', 'pv.id', '=', 'm.proveedor_id')
            ->leftJoin('pedido as pe', 'pe.id', '=', 'm.pedido_id')
            ->whereNull('m.deleted_at')->where('m.tipo_movimiento', $entradas ? 'entrada' : 'salida')
            ->selectRaw("'legacy' as origen, m.id as referencia_id, m.id as editable_id, 'Producto' as clase, m.tipo_movimiento as motivo,
                p.id as producto_id, p.nombre as producto_nombre, p.marca as producto_marca, m.cantidad, m.precio, DATE(m.fecha) as fecha,
                pv.id as proveedor_id, pv.nombre as proveedor_nombre, pe.id as pedido_id, pe.codigo as pedido_codigo, NULL as operacion_id");
        $actual = DB::table('inventario_movimientos as m')
            ->join('lotes_stock as l', 'l.id', '=', 'm.lote_id')
            ->join('productos_nuevo as p', 'p.id', '=', 'l.articulo_id')
            ->leftJoin('proveedor as pv', 'pv.id', '=', 'l.proveedor_id')
            ->leftJoin('operaciones as o', 'o.id', '=', 'm.operacion_id')
            ->leftJoin('pedido as pe', 'pe.id', '=', 'o.pedido_id')
            ->where('m.cantidad', $entradas ? '>' : '<', 0)
            ->selectRaw("'producto' as origen, m.id as referencia_id, CASE WHEN m.motivo IN ('compra', 'inicial') THEN l.id ELSE NULL END as editable_id,
                'Producto' as clase, m.motivo, p.id as producto_id, p.nombre as producto_nombre, p.marca as producto_marca,
                ABS(m.cantidad) as cantidad, l.costo_unitario_centavos / 100.0 as precio,
                CASE WHEN m.motivo IN ('compra', 'inicial') THEN l.fecha ELSE DATE(m.created_at) END as fecha,
                pv.id as proveedor_id, pv.nombre as proveedor_nombre, pe.id as pedido_id, pe.codigo as pedido_codigo, o.id as operacion_id");
        if ($entradas) {
            $repuestos = DB::table('compras_repuestos as r')
                ->join('operaciones as o', 'o.id', '=', 'r.operacion_id')
                ->leftJoin('pedido as pe', 'pe.id', '=', 'o.pedido_id')
                ->leftJoin('proveedor as pv', 'pv.id', '=', 'r.proveedor_id')
                ->selectRaw("'repuesto_compra' as origen, r.id as referencia_id, NULL as editable_id, 'Repuesto' as clase, 'compra' as motivo,
                    NULL as producto_id, r.descripcion as producto_nombre, NULL as producto_marca, r.cantidad, r.costo_unitario_centavos / 100.0 as precio, r.fecha,
                    pv.id as proveedor_id, pv.nombre as proveedor_nombre, pe.id as pedido_id, pe.codigo as pedido_codigo, o.id as operacion_id");
        } else {
            $repuestos = DB::table('operacion_items as r')
                ->join('operaciones as o', 'o.id', '=', 'r.operacion_id')
                ->leftJoin('pedido as pe', 'pe.id', '=', 'o.pedido_id')
                ->leftJoin('proveedor as pv', 'pv.id', '=', 'r.proveedor_id')
                ->where('r.tipo', 'repuesto')->where('o.estado', 'confirmada')
                ->selectRaw("'repuesto_uso' as origen, r.id as referencia_id, NULL as editable_id, 'Repuesto' as clase, 'reparacion' as motivo,
                    NULL as producto_id, r.descripcion as producto_nombre, NULL as producto_marca, r.cantidad, r.costo_unitario_centavos / 100.0 as precio, o.fecha,
                    pv.id as proveedor_id, pv.nombre as proveedor_nombre, pe.id as pedido_id, pe.codigo as pedido_codigo, o.id as operacion_id");
        }
        $query = DB::query()->fromSub($legacy->unionAll($actual)->unionAll($repuestos), 'movimientos');
        if ($search = $filters['search'] ?? null) {
            $query->where(function ($q) use ($search) {
                foreach (['producto_nombre', 'producto_marca', 'proveedor_nombre', 'pedido_codigo', 'clase'] as $column) {
                    $q->orWhereRaw("LOWER($column) LIKE ?", ['%'.mb_strtolower($search).'%']);
                }
            });
        }

        return $query->orderBy($filters['sort_by'], $filters['sort_order'])->orderBy('origen')->orderByDesc('referencia_id')
            ->paginate($filters['per_page'])->withQueryString()->through(function ($row) use ($entradas) {
                return [
                    'id' => $row->origen.':'.$row->referencia_id,
                    'producto_id' => $row->producto_id, 'pedido_id' => $row->pedido_id, 'proveedor_id' => $row->proveedor_id,
                    'tipo_movimiento' => $entradas ? 'entrada' : 'salida', 'cantidad' => (int) $row->cantidad,
                    'precio' => (float) $row->precio, 'fecha' => $row->fecha, 'clase' => $row->clase, 'motivo' => $row->motivo,
                    'producto' => ['id' => $row->producto_id, 'nombre' => $row->producto_nombre, 'marca' => $row->producto_marca],
                    'proveedor' => $row->proveedor_id ? ['id' => $row->proveedor_id, 'nombre' => $row->proveedor_nombre] : null,
                    'pedido' => $row->pedido_id ? ['id' => $row->pedido_id, 'codigo' => $row->pedido_codigo] : null,
                    'operacion_id' => $row->operacion_id,
                    'edit_url' => $entradas && $row->editable_id ? ($row->origen === 'legacy'
                        ? route('movimientos-stock.edit', $row->editable_id)
                        : route('movimientos-stock.ingresos.edit', $row->editable_id)) : null,
                ];
            });
    }
}
