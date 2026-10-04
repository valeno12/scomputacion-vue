<script setup lang="ts">
import HeroSection from '@/components/rendimientos/HeroSection.vue';
import IngresosStockTable from '@/components/rendimientos/IngresosStockTable.vue';
import MovimientosResumen, {
  type MovimientoResumen,
} from '@/components/rendimientos/MovimientosResumen.vue';
import ProveedoresChart from '@/components/rendimientos/ProveedoresChart.vue';
import StatsCard from '@/components/rendimientos/StatsCard.vue';
import VentasParticipantes from '@/components/rendimientos/VentasParticipantes.vue';
import { usePrivacyMode } from '@/composables/usePrivacyMode';
import AppLayout from '@/layouts/AppLayout.vue';
import rendimientos from '@/routes/rendimientos';
import { moneda as formatMoney } from '@/types/comercio';
import type { RendimientosPageProps } from '@/types/rendimientos.interfaces';
import { Head } from '@inertiajs/vue3';
import {
  ArrowDownLeft,
  ArrowUpRight,
  PackageCheck,
  ShoppingBag,
  TrendingUp,
  Wrench,
} from 'lucide-vue-next';
import { computed } from 'vue';
const props = defineProps<RendimientosPageProps>();
const privacy = usePrivacyMode();
const moneda = (value: number) =>
  privacy.value ? '••••••' : formatMoney(value);
const ingresos = computed(() =>
  props.gananciasPorMes.reduce((sum, r) => sum + r.total_ganancias, 0),
);
const gastos = computed(() =>
  props.gastosPorMes.reduce((sum, r) => sum + r.total_gastos, 0),
);
const actividades = computed(() =>
  props.comercio.categorias.map((c) => {
    const esPedido = c.tipo === 'reparacion';
    const costoLegacy =
      props.legacyResumen.cobros_centavos -
      props.legacyResumen.ganancia_centavos;
    return {
      ...c,
      cantidad: c.cantidad + (esPedido ? props.cobrosPorMesDetalles.length : 0),
      costo_centavos: c.costo_centavos + (esPedido ? costoLegacy : 0),
      ganancia_centavos:
        c.ganancia_centavos +
        (esPedido ? props.legacyResumen.ganancia_centavos : 0),
      total_centavos:
        c.total_centavos + (esPedido ? props.legacyResumen.cobros_centavos : 0),
      icono: c.tipo === 'venta' ? ShoppingBag : Wrench,
      nombre: c.tipo === 'venta' ? 'Ventas de productos' : 'Pedidos',
      color: c.tipo === 'venta' ? 'bg-blue-500' : 'bg-violet-500',
    };
  }),
);
const maximo = computed(() =>
  Math.max(1, ...actividades.value.map((c) => Math.abs(c.ganancia_centavos))),
);
const movimientosCobros = computed<MovimientoResumen[]>(() =>
  [
    ...props.comercio.operaciones.map((o) => ({
      id: `op-${o.id}`,
      nombre: o.descripcion,
      tipo: o.tipo === 'venta' ? 'Venta de productos' : 'Pedido',
      categoria: o.tipo === 'venta' ? 'ventas' : 'pedidos',
      fecha: o.fecha_cobro,
      orden: o.cobrado_en || o.fecha_cobro,
      pedidoId: o.pedido_id,
      pedidoCodigo: o.pedido_codigo,
      total: o.titular_centavos,
      url:
        o.tipo === 'venta'
          ? `/comercio/operaciones/${o.id}`
          : `/Pedido/${o.pedido_id}`,
      pedidoUrl: o.pedido_id ? `/Pedido/${o.pedido_id}` : null,
    })),
    ...props.cobrosPorMesDetalles.map((p) => ({
      id: `pedido-${p.id}`,
      nombre: `Pedido ${p.codigo} (legacy)`,
      tipo: 'Pedido',
      categoria: 'anteriores' as const,
      fecha: p.fecha_pago || '',
      orden: (p as unknown as { orden?: string }).orden || p.fecha_pago || '',
      total: Math.round(Number(p.presupuesto) * 100),
      url: `/Pedido/${p.id}`,
    })),
  ].sort((a, b) => {
    const temporal = b.orden.localeCompare(a.orden);
    if (temporal) return temporal;
    if (a.pedidoId && a.pedidoId === b.pedidoId)
      return a.categoria === 'pedidos' ? -1 : 1;
    return 0;
  }),
);
const movimientosCompras = computed<MovimientoResumen[]>(() =>
  [
    ...props.comercio.compras
      .filter((p) => p.comprador_id === props.comercio.titular.id)
      .map((p) => ({
        id: p.id,
        nombre: p.descripcion,
        tipo: p.tipo,
        categoria: 'productos' as const,
        fecha: p.fecha,
        orden: p.orden || p.fecha,
        total: p.total_centavos,
        url: p.url,
      })),
    ...props.gastosPorMesDetalles.map((p) => ({
      id: `anterior-${p.id}`,
      nombre: p.producto?.nombre || 'Compra anterior',
      tipo: 'Ingreso anterior',
      categoria: 'anteriores' as const,
      fecha: p.fecha,
      orden: p.created_at || p.fecha,
      total: Math.round(Number(p.precio || 0) * Number(p.cantidad) * 100),
    })),
  ].sort((a, b) => b.orden.localeCompare(a.orden)),
);
</script>
<template>
  <Head title="Rendimientos" /><AppLayout
    :breadcrumbs="[{ title: 'Rendimientos', href: rendimientos.index().url }]"
  >
    <div class="mx-auto w-full max-w-[1760px] min-w-0 space-y-6 p-4 md:p-6">
      <HeroSection
        :selected-year="selectedYear"
        :selected-month="selectedMonth"
        :ganancia-mes="gananciaMes"
        :titular="comercio.titular.nombre"
      />
      <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <StatsCard
          label="Entradas del mes"
          :value="ingresos"
          variant="success"
          :icon="ArrowDownLeft"
          description="Cobros y márgenes que te corresponden."
        />
        <StatsCard
          label="Salidas del mes"
          :value="gastos"
          variant="danger"
          :icon="ArrowUpRight"
          description="Compras de productos pagadas por vos."
        />
        <StatsCard
          label="Trabajos entregados"
          :value="pedidosEntregadosMes"
          variant="info"
          :icon="PackageCheck"
          :format-as-currency="false"
          description="Pedidos entregados durante este mes."
        />
        <StatsCard
          label="Ganancia promedio"
          :value="promedioGananciaPorPedido"
          variant="primary"
          :icon="TrendingUp"
          description="Por venta o pedido cobrado."
        />
      </div>
      <div class="grid min-w-0 items-stretch gap-5 xl:grid-cols-2">
        <MovimientosResumen tipo="cobros" :movimientos="movimientosCobros" />
        <MovimientosResumen tipo="compras" :movimientos="movimientosCompras" />
      </div>
      <div
        class="grid min-w-0 items-start gap-5 xl:grid-cols-[minmax(0,1.65fr)_minmax(300px,1fr)]"
      >
        <section
          class="min-w-0 overflow-hidden rounded-xl border bg-card shadow-sm"
        >
          <div
            class="flex flex-wrap items-center justify-between gap-3 border-b bg-gradient-to-r from-blue-50 to-indigo-50 px-5 py-4 dark:from-blue-950/30 dark:to-indigo-950/20"
          >
            <div class="flex items-center gap-3">
              <span
                class="flex size-10 items-center justify-center rounded-lg bg-blue-100 text-blue-600 dark:bg-blue-500/20 dark:text-blue-300"
                ><TrendingUp class="size-5"
              /></span>
              <div>
                <h2 class="font-semibold">Cómo se compone tu resultado</h2>
                <p class="text-xs text-muted-foreground">
                  Después de descontar el costo de lo vendido.
                </p>
              </div>
            </div>
            <strong
              class="text-2xl tracking-tight text-blue-700 tabular-nums dark:text-blue-300"
              >{{ moneda(Math.round(gananciaTitular * 100)) }}</strong
            >
          </div>
          <div class="overflow-x-auto">
            <table class="activity-table w-full min-w-[540px] text-sm">
              <thead
                class="bg-muted/20 text-left text-xs text-muted-foreground"
              >
                <tr>
                  <th class="px-5 py-3 font-medium">Actividad</th>
                  <th class="px-3 py-3 text-right font-medium">
                    Costo recuperado
                  </th>
                  <th class="px-5 py-3 text-right font-medium">Tu ganancia</th>
                </tr>
              </thead>
              <tbody class="divide-y">
                <tr v-for="c in actividades" :key="c.tipo">
                  <td class="px-5 py-4">
                    <div class="flex items-center gap-2.5">
                      <component
                        :is="c.icono"
                        class="size-4 shrink-0 text-muted-foreground"
                      />
                      <div class="min-w-0">
                        <p class="font-medium">{{ c.nombre }}</p>
                        <p class="mt-0.5 text-xs text-muted-foreground">
                          {{ c.cantidad }}
                          {{
                            c.cantidad === 1
                              ? 'operación cobrada'
                              : 'operaciones cobradas'
                          }}
                        </p>
                      </div>
                    </div>
                  </td>
                  <td
                    data-label="Costo recuperado"
                    class="px-3 py-4 text-right whitespace-nowrap text-muted-foreground tabular-nums"
                  >
                    {{ moneda(c.costo_centavos) }}
                  </td>
                  <td data-label="Tu ganancia" class="min-w-36 px-5 py-4">
                    <p
                      class="text-right font-semibold whitespace-nowrap tabular-nums"
                    >
                      {{ moneda(c.ganancia_centavos) }}
                    </p>
                    <div
                      class="mt-2 h-1.5 overflow-hidden rounded-full bg-muted"
                      aria-hidden="true"
                    >
                      <div
                        class="h-full rounded-full"
                        :class="c.color"
                        :style="{
                          width: `${privacy ? 0 : (Math.abs(c.ganancia_centavos) / maximo) * 100}%`,
                        }"
                      />
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <p
            class="border-t bg-muted/15 px-5 py-3 text-xs leading-relaxed text-muted-foreground"
          >
            La ganancia y la devolución del costo son cosas distintas. Los
            importes de otras personas se muestran aparte.
          </p>
        </section>
        <VentasParticipantes
          :participantes="comercio.ventas_por_participante"
          :titular-id="comercio.titular.id"
        />
      </div>
      <IngresosStockTable :ingresos="comercio.compras" />
      <ProveedoresChart :proveedores="proveedores" />
    </div>
  </AppLayout>
</template>

<style scoped>
@media (max-width: 639px) {
  .activity-table,
  .activity-table tbody {
    display: block;
    min-width: 0;
  }
  .activity-table thead {
    display: none;
  }
  .activity-table tr {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1rem;
    padding: 1rem 1.25rem;
  }
  .activity-table td {
    min-width: 0;
    padding: 0;
    text-align: left;
  }
  .activity-table td:first-child {
    grid-column: 1 / -1;
  }
  .activity-table td[data-label]::before {
    content: attr(data-label);
    display: block;
    margin-bottom: 0.375rem;
    font-size: 0.75rem;
    color: var(--muted-foreground);
  }
  .activity-table td p {
    text-align: left;
  }
}
</style>
