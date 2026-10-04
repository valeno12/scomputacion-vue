<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { usePrivacyMode } from '@/composables/usePrivacyMode';
import {
  moneda as formatMoney,
  hoy,
  type ItemOperacion,
  type Operacion,
} from '@/types/comercio';
import { formatDate } from '@/utils/formatter';
import { router } from '@inertiajs/vue3';
import { ChevronDown, ClipboardList, Package, Wrench } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import RepartoResumen from './RepartoResumen.vue';
const props = defineProps<{
  operacion: Operacion;
  ocultarReparto?: boolean;
  tipos?: ItemOperacion['tipo'][];
  soloItems?: boolean;
  ocultarPorcentajes?: boolean;
  costosVisibles?: boolean;
}>();
const items = computed(() =>
  props.operacion.items.filter(
    (item) => !props.tipos || props.tipos.includes(item.tipo),
  ),
);
const privacy = usePrivacyMode();
const moneda = (value: number) =>
  privacy.value ? '••••••' : formatMoney(value);
const fechas = ref<Record<number, string>>({});
const processing = ref(false);
const costos = ref(props.costosVisibles ?? false);
function comprar(id: number) {
  processing.value = true;
  router.post(
    `/comercio/repuestos/${id}/comprar`,
    { fecha_compra: fechas.value[id] || hoy() },
    { preserveScroll: true, onFinish: () => (processing.value = false) },
  );
}
</script>
<template>
  <div
    v-if="!items.length"
    class="flex flex-col items-center rounded-xl border border-dashed bg-muted/15 px-6 py-8 text-center"
  >
    <span
      class="mb-3 flex size-12 items-center justify-center rounded-full bg-blue-100 text-blue-600 dark:bg-blue-500/15 dark:text-blue-300"
      ><ClipboardList class="size-5"
    /></span>
    <h3 class="font-semibold">Todavía no hay un presupuesto</h3>
    <p class="mt-1 max-w-sm text-sm text-muted-foreground">
      Cargá el trabajo, la mano de obra y los repuestos o productos que necesita
      este pedido.
    </p>
    <div v-if="$slots.emptyAction" class="mt-4">
      <slot name="emptyAction" />
    </div>
  </div>
  <section v-else class="space-y-4">
    <div
      :class="
        soloItems
          ? 'overflow-hidden'
          : 'overflow-hidden rounded-xl border bg-card'
      "
    >
      <div
        v-if="!soloItems"
        class="flex flex-wrap items-center justify-between gap-3 border-b px-4 py-4"
      >
        <div>
          <p class="text-xs font-medium text-muted-foreground">
            {{
              operacion.tipo === 'venta'
                ? 'Total de la venta'
                : 'Total del presupuesto'
            }}
          </p>
          <strong class="text-2xl tracking-tight tabular-nums">{{
            moneda(operacion.total_centavos)
          }}</strong>
        </div>
        <div class="flex flex-wrap items-center gap-x-6 gap-y-2 text-sm">
          <div>
            <p class="text-xs text-muted-foreground">Costo</p>
            <p class="mt-1 tabular-nums">
              {{ moneda(operacion.costo_centavos) }}
            </p>
          </div>
          <div>
            <p class="text-xs text-muted-foreground">Ganancia</p>
            <p
              class="mt-1 font-semibold text-emerald-700 tabular-nums dark:text-emerald-300"
            >
              {{ moneda(operacion.ganancia_centavos) }}
            </p>
          </div>
        </div>
      </div>
      <div class="overflow-x-auto">
        <table class="operation-table w-full min-w-[520px] table-fixed text-sm">
          <thead class="bg-muted/50 text-left text-sm text-foreground/80">
            <tr>
              <th
                class="px-4 py-3 font-medium"
                :class="
                  costosVisibles ? 'w-[30%]' : costos ? 'w-[40%]' : 'w-[52%]'
                "
              >
                Detalle
              </th>
              <th class="w-[10%] px-3 py-3 text-center font-medium">Cant.</th>
              <th v-if="costos" class="px-3 py-3 text-right font-medium">
                Costo unitario
              </th>
              <th
                v-if="costosVisibles"
                class="px-3 py-3 text-right font-medium"
              >
                Ganancia %
              </th>
              <th class="px-3 py-3 text-right font-medium">Precio unitario</th>
              <th class="px-4 py-3 text-right font-medium">Subtotal</th>
            </tr>
          </thead>
          <tbody class="divide-y">
            <tr v-for="item in items" :key="item.id" class="align-top">
              <td class="px-4 py-3.5">
                <p class="font-medium">{{ item.descripcion }}</p>
                <p
                  class="mt-1 flex items-center gap-1.5 text-xs text-muted-foreground"
                >
                  <component
                    :is="item.tipo === 'stock' ? Package : Wrench"
                    class="size-3"
                  />{{
                    item.tipo === 'stock'
                      ? 'Producto del inventario'
                      : item.tipo === 'repuesto'
                        ? 'Repuesto'
                        : 'Mano de obra'
                  }}
                </p>
                <p
                  v-if="costos && item.tipo === 'stock' && !ocultarPorcentajes"
                  class="mt-2 text-xs text-muted-foreground"
                >
                  Ganancia:
                  {{
                    item.reparto
                      .map((r) => `${r.nombre} ${r.porcentaje}%`)
                      .join(' · ')
                  }}
                </p>
                <div v-if="item.tipo === 'repuesto'" class="mt-2">
                  <p
                    v-if="item.fecha_compra"
                    class="text-sm text-emerald-700 dark:text-emerald-300"
                  >
                    Comprado el
                    {{ formatDate(item.fecha_compra, { includeTime: false })
                    }}<span v-if="item.proveedor_nombre">
                      · {{ item.proveedor_nombre }}</span
                    >
                  </p>
                  <details
                    v-else-if="operacion.estado !== 'anulada'"
                    class="group/compra"
                  >
                    <summary
                      class="inline-flex cursor-pointer list-none items-center gap-1 rounded-md py-1 text-sm font-medium text-blue-700 dark:text-blue-300"
                    >
                      Registrar compra
                      <ChevronDown
                        class="size-3 group-open/compra:rotate-180"
                      />
                    </summary>
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                      <Input
                        v-model="fechas[item.id]"
                        type="date"
                        :max="hoy()"
                        :aria-label="`Fecha de compra de ${item.descripcion}`"
                        class="h-8 max-w-40"
                      /><Button
                        type="button"
                        variant="outline"
                        size="sm"
                        :disabled="processing"
                        @click="comprar(item.id)"
                        >Confirmar compra</Button
                      ><small class="text-muted-foreground"
                        >Sin fecha, se usa hoy.</small
                      >
                    </div>
                  </details>
                </div>
              </td>
              <td
                data-label="Cantidad"
                class="px-3 py-3.5 text-center tabular-nums"
              >
                {{ item.cantidad }}
              </td>
              <td
                v-if="costos"
                data-label="Costo unitario"
                class="px-3 py-3.5 text-right whitespace-nowrap tabular-nums"
              >
                {{ moneda(item.costo_unitario_centavos) }}
              </td>
              <td
                v-if="costosVisibles"
                data-label="Ganancia %"
                class="px-3 py-3.5 text-right tabular-nums"
              >
                {{
                  item.porcentaje_ganancia ??
                  (item.costo_unitario_centavos
                    ? Math.round(
                        (item.precio_unitario_centavos /
                          item.costo_unitario_centavos -
                          1) *
                          10000,
                      ) / 100
                    : 0)
                }}%
              </td>
              <td
                data-label="Precio unitario"
                class="px-3 py-3.5 text-right whitespace-nowrap tabular-nums"
              >
                {{ moneda(item.precio_unitario_centavos) }}
              </td>
              <td
                data-label="Subtotal"
                class="px-4 py-3.5 text-right font-semibold whitespace-nowrap tabular-nums"
              >
                {{ moneda(item.precio_unitario_centavos * item.cantidad) }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-if="!costosVisibles" class="border-t bg-muted/15 px-4 py-2">
        <button
          type="button"
          class="inline-flex items-center gap-1.5 py-1 text-sm text-foreground/75 hover:text-foreground"
          :aria-expanded="costos"
          @click="costos = !costos"
        >
          <ChevronDown
            class="size-3.5 transition-transform"
            :class="{ 'rotate-180': costos }"
          />{{
            ocultarPorcentajes
              ? costos
                ? 'Ocultar costos'
                : 'Ver costos'
              : costos
                ? 'Ocultar costos y porcentajes por producto'
                : 'Ver costos y porcentajes por producto'
          }}
        </button>
      </div>
    </div>
    <RepartoResumen
      v-if="!ocultarReparto && !!operacion.fecha_cobro"
      :reparto="operacion.reparto_resumen"
      :previsto="!operacion.fecha_cobro"
      :anulado="operacion.estado === 'anulada'"
      :solo-titular="!operacion.items.some((i) => i.tipo === 'stock')"
    />
  </section>
</template>

<style scoped>
@media (max-width: 639px) {
  .operation-table,
  .operation-table tbody {
    display: block;
    min-width: 0;
  }
  .operation-table thead {
    display: none;
  }
  .operation-table tr {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.875rem;
    padding: 1rem;
  }
  .operation-table td {
    padding: 0;
    min-width: 0;
    text-align: left;
  }
  .operation-table td:first-child {
    grid-column: 1 / -1;
  }
  .operation-table td[data-label]::before {
    content: attr(data-label);
    display: block;
    margin-bottom: 0.25rem;
    font-size: 0.75rem;
    font-weight: 400;
    color: var(--muted-foreground);
  }
}
</style>
