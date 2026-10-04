export interface ProductoNuevo {
  id: number;
  nombre: string;
  marca: string | null;
  precio_venta_centavos: number;
  costo_referencia_centavos: number | null;
  porcentaje_ganancia: number | null;
  costo_unitario_centavos?: number | null;
  cantidad_disponible?: number;
  activo: boolean;
  tiene_ingresos?: boolean;
}
