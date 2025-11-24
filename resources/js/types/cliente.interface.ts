import { Pedido } from './pedido.interface';

export interface Cliente {
  id: number;
  dni: string;
  nombre: string;
  apellido: string;
  direccion: string;
  telefono: string;
  mail: string;
  created_at?: string;
  updated_at?: string;
  deleted_at?: string | null;
  pedidos?: Pedido[];
}
