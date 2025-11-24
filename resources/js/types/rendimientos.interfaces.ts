// types/rendimientos.interface.ts
import type { MovimientoStock } from './movimiento-stock.interface';
import type { Pedido } from './pedido.interface';
import type { Proveedor } from './proveedor.interface';

export interface GananciasPorMes {
  total_ganancias: number;
}

export interface GastosPorMes {
  total_gastos: number;
}

export interface ProveedorStats {
  proveedor_id: number;
  cantidad_pedidos: number;
  proveedor: Proveedor;
}

export interface RendimientosPageProps {
  gananciasPorMes: GananciasPorMes[];
  gastosPorMes: GastosPorMes[];
  selectedMonth: number;
  selectedYear: number;
  cobrosPorMesDetalles: Pedido[];
  gastosPorMesDetalles: MovimientoStock[];
  gananciaMes: number;
  proveedores: ProveedorStats[];
  pedidosEntregadosMes: number;
  promedioGananciaPorPedido: number;
}
