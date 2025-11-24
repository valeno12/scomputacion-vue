// types/prodcuto.interface.ts
export interface Producto {
  id: number;
  nombre: string;
  marca: string;
  precio: number;
  cantidad_disponible: number;
  cantidad_pendientes?: number;
  created_at: string;
  updated_at: string;
  deleted_at?: string;
}
