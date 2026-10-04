import type { ItemForm } from '@/types/comercio';
import { precioProducto } from './precioProducto';

// The preview uses the same recorded values when a saved part is unchanged.
export function precioItemCentavos(item: ItemForm): number {
  if (item.tipo === 'stock') return Math.round(Number(item.precio) * 100) || 0;
  const saved = item.importe_guardado;
  if (
    saved &&
    saved.costo === Number(item.costo) &&
    saved.porcentaje === Number(item.porcentaje_ganancia)
  )
    return saved.precio_centavos;
  if (item.costo === '' || item.porcentaje_ganancia === '') return 0;
  return precioProducto(item.costo, item.porcentaje_ganancia ?? 0);
}
export function importesPedido(
  items: ItemForm[],
  manoObra: number | string | null = 0,
) {
  const productos = items
    .filter((i) => i.tipo === 'stock')
    .reduce((sum, i) => sum + precioItemCentavos(i) * Number(i.cantidad), 0);
  const repuestos = items
    .filter((i) => i.tipo === 'repuesto')
    .reduce((sum, i) => sum + precioItemCentavos(i) * Number(i.cantidad), 0);
  const servicio = Math.round(Number(manoObra) * 100) || 0;
  return {
    productos,
    repuestos,
    servicio,
    total: productos + repuestos + servicio,
  };
}
