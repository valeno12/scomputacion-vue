import { usePage } from '@inertiajs/vue3';
import type { AppPageProps } from '@/types';

export interface Participante {
  id: number;
  nombre: string;
  activo: boolean;
}
export interface Reparto {
  participante_id: number | '';
  porcentaje: number | string;
  nombre?: string;
}
export interface Articulo {
  id: number;
  nombre: string;
  marca: string | null;
  precio_venta_centavos: number;
  activo: boolean;
  lotes: Lote[];
}
export interface Lote {
  id: number;
  articulo_id: number;
  comprador_id: number;
  articulo: Articulo;
  comprador: Participante;
  cantidad_disponible: number;
  cantidad_inicial: number;
  costo_unitario_centavos: number;
  tipo: string;
  fecha: string;
}
export interface ItemForm {
  importe_guardado?: {
    costo: number;
    porcentaje: number;
    precio_centavos: number;
  };
  grupo?: string;
  producto_id?: number;
  financiadores?: { id: number; nombre: string; cantidad: number }[];
  proveedor?: string;
  porcentaje_ganancia?: number | string;
  id?: number;
  tipo: 'stock' | 'repuesto';
  descripcion: string;
  lote_id: number | '';
  comprador_id: number | '';
  proveedor_id: number | '';
  cantidad: number;
  costo: number | string;
  precio: number | string;
  fecha_compra: string;
  compra_id?: number | null;
  reparto: Reparto[];
}
export interface OpcionesComercio {
  titular?: Participante | null;
  hermanaId?: number | null;
  participantes: Participante[];
  repartoMercaderia: Reparto[];
  repartoRepuestos: Reparto[];
  repartoManoObra: Reparto[];
  lotes: Lote[];
  proveedores: { id: number; nombre: string }[];
}
export interface Distribucion {
  participante_id: number;
  nombre: string;
  porcentaje: number;
  ganancia_centavos: number;
  costo_centavos: number;
}
export interface ItemOperacion {
  financiadores?: { id: number; nombre: string; cantidad: number }[];
  grupo?: string | null;
  producto_id?: number | null;
  porcentaje_ganancia?: number | null;
  proveedor_nombre?: string | null;
  comprador?: Participante;
  lote?: Lote;
  id: number;
  tipo: 'stock' | 'repuesto' | 'mano_obra';
  descripcion: string;
  lote_id: number | null;
  comprador_id: number | null;
  proveedor_id: number | null;
  cantidad: number;
  costo_unitario_centavos: number;
  precio_unitario_centavos: number;
  fecha_compra: string | null;
  compra_id: number | null;
  reparto: Reparto[];
  distribucion: Distribucion[];
}
export interface Operacion {
  es_presupuesto: boolean;
  anulada_el?: string | null;
  reparto_resumen: ResumenParticipante[];
  registrado_por?: number | null;
  registrador?: { id: number; name: string } | null;
  id: number;
  tipo: string;
  estado: string;
  pedido_id: number | null;
  cliente_id: number | null;
  fecha: string;
  fecha_cobro: string | null;
  cobrado_en?: string | null;
  medio_pago: string | null;
  costo_centavos: number;
  total_centavos: number;
  ganancia_centavos: number;
  items: ItemOperacion[];
  pedido?: { id: number; codigo: string; deleted_at?: string | null };
  cliente?: { nombre: string; apellido: string };
}

export const quienRecuperaCosto = (item: ItemOperacion): string =>
  item.distribucion.find((row) => row.participante_id === item.comprador_id)
    ?.nombre ??
  item.comprador?.nombre ??
  'Sin registro';
export interface ResumenParticipante {
  participante_id: number;
  nombre: string;
  rol: 'titular' | 'hermana' | 'otro';
  costo_centavos: number;
  ganancia_centavos: number;
  total_centavos: number;
}
export interface Paginacion<T> {
  data: T[];
  links: { url: string | null; label: string; active: boolean }[];
  total: number;
}
export const moneda = (centavos: number) =>
  new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' }).format(
    centavos / 100,
  );
export const hoy = () =>
  new Intl.DateTimeFormat('en-CA', {
    timeZone: usePage<AppPageProps>().props.zonaHorariaComercial,
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
  }).format(new Date());
export const copiarReparto = (rows: Reparto[]) =>
  rows.map((r) => ({
    participante_id: r.participante_id,
    porcentaje: r.porcentaje,
    ...(r.nombre ? { nombre: r.nombre } : {}),
  }));
export const itemFormulario = (i: ItemOperacion): ItemForm => ({
  importe_guardado: {
    costo: i.costo_unitario_centavos / 100,
    porcentaje:
      i.porcentaje_ganancia ??
      (i.costo_unitario_centavos
        ? Math.round(
            (i.precio_unitario_centavos / i.costo_unitario_centavos - 1) *
              10000,
          ) / 100
        : 0),
    precio_centavos: i.precio_unitario_centavos,
  },
  grupo: i.grupo || nuevaClave(),
  producto_id: i.producto_id ?? i.lote?.articulo_id,
  financiadores:
    i.financiadores ??
    (i.comprador
      ? [
          {
            id: i.comprador.id,
            nombre: i.comprador.nombre,
            cantidad: (i.lote?.cantidad_disponible ?? 0) + i.cantidad,
          },
        ]
      : []),
  proveedor: i.proveedor_nombre ?? '',
  porcentaje_ganancia:
    i.porcentaje_ganancia ??
    (i.costo_unitario_centavos
      ? Math.round(
          (i.precio_unitario_centavos / i.costo_unitario_centavos - 1) * 10000,
        ) / 100
      : 0),
  id: i.id,
  tipo: i.tipo as ItemForm['tipo'],
  descripcion: i.descripcion,
  lote_id: i.lote_id ?? '',
  comprador_id: i.comprador_id ?? '',
  proveedor_id: i.proveedor_id ?? '',
  cantidad: i.cantidad,
  costo: i.costo_unitario_centavos / 100,
  precio: i.precio_unitario_centavos / 100,
  fecha_compra: i.fecha_compra ?? '',
  compra_id: i.compra_id,
  reparto: copiarReparto(i.reparto),
});

export function itemsFormulario(rows: ItemOperacion[]): ItemForm[] {
  const groups = new Map<string, ItemForm>();
  for (const row of rows.filter((r) => r.tipo !== 'mano_obra')) {
    const key =
      row.tipo === 'stock' ? row.grupo || `row-${row.id}` : `row-${row.id}`;
    const previous = groups.get(key);
    if (previous) previous.cantidad += row.cantidad;
    else groups.set(key, itemFormulario(row));
  }
  return [...groups.values()];
}

export function nuevaClave(): string {
  const bytes = crypto.getRandomValues(new Uint8Array(16));
  bytes[6] = (bytes[6] & 0x0f) | 0x40;
  bytes[8] = (bytes[8] & 0x3f) | 0x80;
  const hex = Array.from(bytes, (b) => b.toString(16).padStart(2, '0')).join(
    '',
  );
  return `${hex.slice(0, 8)}-${hex.slice(8, 12)}-${hex.slice(12, 16)}-${hex.slice(16, 20)}-${hex.slice(20)}`;
}
