// types/pedido.interface.ts
import type { Cliente } from './cliente.interface';
import type { Estado } from './estado.interface';
import type { PedidoEstado } from './pedido-estado.interface';

export interface Pedido {
  id: number;
  codigo: string;
  cliente_id: number;
  cargador: string | null;
  equipo: string | null;
  id_movimiento_stock: number | null;
  fecha_ingreso: string;
  estado_ingreso: string | null;
  trabajo_realizar: string | null;
  repuesto: string | null;
  presupuesto: number | null;
  precio_total: number | null;
  costo: number | null;
  fecha_pago: string | null;
  costo_mano_obra: number | null;
  estadoActual_id: number | null;
  created_at: string;
  updated_at: string;

  // ✅ Relaciones reutilizando tipos
  cliente?: Cliente;
  estado_actual?: Estado;
  estado_finalizado?: PedidoEstado;
  estado_entregado?: PedidoEstado;
}
