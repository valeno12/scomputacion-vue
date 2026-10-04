import type { Producto } from './producto.interface';

export interface ProductoSeleccionado {
  id: number;
  pedido_id: number;
  producto_id: number;
  cantidad: number;
  precio: number;
  precio_venta?: number | null;
  created_at: string;
  updated_at: string;

  // Relación
  producto: Producto;
}
