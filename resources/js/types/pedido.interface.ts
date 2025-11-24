import type { Cliente } from './cliente.interface';
import type { Estado } from './estado.interface';
import type { PedidoEstado } from './pedido-estado.interface';
import type { ProductoSeleccionado } from './producto-seleccionado.interface'; // ✅ AGREGAR

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
  ganancia: number | null;
  costo: number | null;
  componente_problematico?: string | null;
  fecha_pago: string | null;
  costo_mano_obra: number | null;
  estadoActual_id: number | null;
  created_at: string;
  updated_at: string;

  // Relaciones
  cliente?: Cliente;
  estado_actual?: Estado;
  estado_finalizado?: PedidoEstado;
  estado_entregado?: PedidoEstado;
  productos_seleccionados?: ProductoSeleccionado[]; // ✅ AGREGAR
}
