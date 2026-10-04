export function precioProducto(
  costo: number | string,
  porcentaje: number | string,
): number {
  const cents = Math.round(Number(costo) * 100);
  const points = Math.round(Number(porcentaje) * 100);
  if (
    !Number.isSafeInteger(cents) ||
    !Number.isSafeInteger(points) ||
    cents < 0 ||
    points < 0
  )
    return 0;
  return Number(
    BigInt(cents) + (BigInt(cents) * BigInt(points) + 5000n) / 10000n,
  );
}

export function porcentajeInicial(
  porcentaje: number | null,
  costoCentavos: number | null,
  precioCentavos: number,
): number | '' {
  if (porcentaje !== null) return porcentaje;
  if (!costoCentavos || precioCentavos < costoCentavos) return '';
  return (
    Math.round(((precioCentavos - costoCentavos) / costoCentavos) * 10000) / 100
  );
}

export interface IngresoResumenItem {
  nombre: string;
  marca?: string | null;
  cantidad: number | string;
  costo: number | string;
  porcentaje_ganancia: number | string;
}
