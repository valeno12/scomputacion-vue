// types/pedido-estado.interface.ts
import type { Estado } from './estado.interface';

export interface PedidoEstado {
  id: number;
  pedido_id: number;
  estado_id: number;
  created_at: string;
  updated_at: string;

  // Relación opcional con Estado
  estado?: Estado;
}
