import { Pedido } from './pedido.interface';

export interface Cliente {
  id: number;
  dni: string | null;
  nombre: string;
  apellido: string;
  direccion: string | null;
  telefono: string | null;
  mail: string | null;
  created_at?: string;
  updated_at?: string;
  deleted_at?: string | null;
  pedidos?: Pedido[];
}
