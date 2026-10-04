// types/rendimientos.interface.ts
import type { MovimientoStock } from './movimiento-stock.interface';
import type { Pedido } from './pedido.interface';

export interface GananciasPorMes {
  total_ganancias: number;
}

export interface GastosPorMes {
  total_gastos: number;
}

export interface ProveedorStats {
  proveedor_id: number | null;
  cantidad_pedidos: number;
  proveedor: { nombre: string };
}

export interface VentasParticipante {
  participante_id: number;
  nombre: string;
  activo: boolean;
  cantidad: number;
  costo_centavos: number;
  ganancia_centavos: number;
  total_centavos: number;
}

export interface RendimientosPageProps {
  gananciaTitular: number;
  legacyResumen: {
    cobros_centavos: number;
    ganancia_centavos: number;
    gastos_centavos: number;
  };
  comercio: {
    titular: { id: number; nombre: string };
    costo_recuperado_centavos: number;
    cobros_centavos: number;
    gastos_centavos: number;
    cobros_totales_centavos: number;
    categorias: {
      tipo: string;
      nombre: string;
      cantidad: number;
      costo_centavos: number;
      ganancia_centavos: number;
      total_centavos: number;
    }[];
    ganancia_centavos: number;
    ventas_por_participante: VentasParticipante[];
    distribucion: import('./comercio').ResumenParticipante[];
    operaciones: {
      id: number;
      tipo: string;
      fecha_cobro: string;
      cobrado_en?: string | null;
      pedido_id?: number | null;
      pedido_codigo?: string | null;
      descripcion: string;
      titular_centavos: number;
      otros_centavos: number;
      total_centavos: number;
    }[];
    compras: {
      id: string;
      descripcion: string;
      url: string;
      proveedor_id: number | null;
      comprador_id: number;
      comprador: string;
      proveedor: string;
      cantidad: number;
      orden?: string | null;
      tipo: string;
      fecha: string;
      total_centavos: number;
    }[];
  };
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
