// types/movimiento-stock.interface.ts
import type { Pedido } from './pedido.interface';
import type { Producto } from './producto.interface';
import type { Proveedor } from './proveedor.interface';

export interface MovimientoStock {
  id: number;
  producto_id: number;
  pedido_id: number | null;
  proveedor_id: number | null;
  tipo_movimiento: 'entrada' | 'salida';
  cantidad: number;
  fecha: string;
  precio?: number;
  created_at: string;
  updated_at: string;
  deleted_at?: string;

  // Relaciones
  producto?: Producto;
  pedido?: Pedido;
  proveedor?: Proveedor;
}
