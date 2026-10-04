<script setup lang="ts">
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { usePrivacyMode } from '@/composables/usePrivacyMode';
import { moneda as formatMoney } from '@/types/comercio';
import { formatDate } from '@/utils/formatter';
import { Link } from '@inertiajs/vue3';
import {
  ArrowDownLeft,
  ArrowUpRight,
  ArrowUpRight as ExternalLink,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
type CategoriaMovimiento = 'pedidos' | 'ventas' | 'productos' | 'anteriores';
export interface MovimientoResumen {
  id: string;
  nombre: string;
  tipo: string;
  fecha: string;
  total: number;
  url?: string;
  categoria: CategoriaMovimiento;
  orden: string;
  pedidoId?: number | null;
  pedidoCodigo?: string | null;
  pedidoUrl?: string | null;
}
const props = defineProps<{
  tipo: 'cobros' | 'compras';
  movimientos: MovimientoResumen[];
}>();
const privacy = usePrivacyMode();
const moneda = (value: number) =>
  privacy.value ? '••••••' : formatMoney(value);
const seleccionado = ref('');
const tabs = computed(() => {
  const options =
    props.tipo === 'cobros'
      ? [
          { value: 'todos', label: 'Todas' },
          { value: 'pedidos', label: 'Pedidos' },
          { value: 'ventas', label: 'Ventas' },
          { value: 'anteriores', label: 'Registros anteriores' },
        ]
      : [
          { value: 'productos', label: 'Productos' },
          { value: 'anteriores', label: 'Registros anteriores' },
        ];

  return options.filter(
    (tab) =>
      tab.value === 'todos' ||
      props.movimientos.some(
        (movimiento) => movimiento.categoria === tab.value,
      ),
  );
});
watch(
  tabs,
  (options) => {
    if (!options.some((tab) => tab.value === seleccionado.value))
      seleccionado.value = options[0]?.value ?? '';
  },
  { immediate: true },
);
const movimientosVisibles = computed(() =>
  seleccionado.value === 'todos'
    ? props.movimientos
    : props.movimientos.filter(
        (movimiento) => movimiento.categoria === seleccionado.value,
      ),
);
const total = computed(() =>
  movimientosVisibles.value.reduce((sum, m) => sum + m.total, 0),
);
const pedidosAgrupados = computed(() => {
  const cantidades = new Map<number, number>();
  movimientosVisibles.value.forEach((movimiento) => {
    if (movimiento.pedidoId)
      cantidades.set(
        movimiento.pedidoId,
        (cantidades.get(movimiento.pedidoId) || 0) + 1,
      );
  });
  return new Set(
    [...cantidades].filter(([, cantidad]) => cantidad > 1).map(([id]) => id),
  );
});
const movimientosOrdenados = computed(() => {
  const bloques = new Map<
    string,
    { orden: string; movimientos: MovimientoResumen[] }
  >();

  movimientosVisibles.value.forEach((movimiento) => {
    const clave = movimiento.pedidoId
      ? `pedido-${movimiento.pedidoId}`
      : movimiento.id;
    const bloque = bloques.get(clave) || {
      orden: movimiento.orden,
      movimientos: [],
    };

    bloque.movimientos.push(movimiento);
    if (movimiento.orden > bloque.orden) bloque.orden = movimiento.orden;
    bloques.set(clave, bloque);
  });

  return [...bloques.values()]
    .sort((a, b) => b.orden.localeCompare(a.orden))
    .flatMap((bloque) =>
      bloque.movimientos.sort((a, b) => {
        if (a.categoria !== b.categoria)
          return a.categoria === 'pedidos' ? -1 : 1;
        return a.orden.localeCompare(b.orden) || a.id.localeCompare(b.id);
      }),
    );
});
const iniciaGrupoPedido = (movimiento: MovimientoResumen, index: number) =>
  !!movimiento.pedidoId &&
  movimientosOrdenados.value[index - 1]?.pedidoId !== movimiento.pedidoId;
const esCobroPedido = (movimiento: MovimientoResumen) =>
  movimiento.categoria === 'pedidos';
</script>
<template>
  <section
    class="flex min-w-0 flex-col overflow-hidden rounded-xl border bg-card shadow-sm"
  >
    <div
      class="flex items-center justify-between gap-3 border-b px-5 py-4"
      :class="
        tipo === 'cobros'
          ? 'bg-gradient-to-r from-emerald-50 to-green-50 dark:from-emerald-950/30 dark:to-green-950/20'
          : 'bg-gradient-to-r from-rose-50 to-orange-50 dark:from-rose-950/30 dark:to-orange-950/20'
      "
    >
      <div class="flex items-center gap-3">
        <span
          class="flex size-10 shrink-0 items-center justify-center rounded-lg"
          :class="
            tipo === 'cobros'
              ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-300'
              : 'bg-rose-100 text-rose-600 dark:bg-rose-500/20 dark:text-rose-300'
          "
          ><component
            :is="tipo === 'cobros' ? ArrowDownLeft : ArrowUpRight"
            class="size-5"
        /></span>
        <div>
          <h2 class="font-semibold">
            {{ tipo === 'cobros' ? 'Entradas del mes' : 'Salidas del mes' }}
          </h2>
          <p class="text-xs text-muted-foreground">
            {{
              tipo === 'cobros'
                ? 'Cobros y márgenes que te corresponden.'
                : 'Compras de productos pagadas por vos.'
            }}
          </p>
        </div>
      </div>
      <span
        class="rounded-full bg-background/60 px-2.5 py-1 text-xs font-semibold"
        >{{ movimientosVisibles.length }}</span
      >
    </div>
    <Tabs
      v-if="tabs.length > 1"
      v-model="seleccionado"
      class="border-b px-5 pt-3"
    >
      <div class="overflow-x-auto">
        <TabsList
          class="h-auto min-w-full justify-start gap-1 bg-transparent p-0"
        >
          <TabsTrigger
            v-for="tab in tabs"
            :key="tab.value"
            :value="tab.value"
            class="flex-none px-3 py-2 data-[state=active]:bg-muted data-[state=active]:shadow-none"
            >{{ tab.label }}</TabsTrigger
          >
        </TabsList>
      </div>
    </Tabs>
    <div
      v-if="movimientosVisibles.length"
      class="max-h-[390px] flex-1 overflow-auto"
    >
      <table class="w-full text-sm">
        <thead
          class="sticky top-0 z-10 bg-muted text-left text-xs text-muted-foreground"
        >
          <tr>
            <th class="px-5 py-3 font-medium">Concepto</th>
            <th class="px-3 py-3 font-medium">Fecha</th>
            <th class="px-5 py-3 text-right font-medium">
              {{ tipo === 'cobros' ? 'Para vos' : 'Importe' }}
            </th>
          </tr>
        </thead>
        <tbody class="divide-y">
          <template v-for="(m, index) in movimientosOrdenados" :key="m.id">
            <tr
              v-if="tipo === 'cobros' && iniciaGrupoPedido(m, index)"
              class="bg-blue-50/70 dark:bg-blue-950/20"
            >
              <td
                colspan="3"
                class="px-5 py-2 text-xs font-semibold text-blue-700 dark:text-blue-300"
              >
                <Link
                  v-if="m.pedidoUrl"
                  :href="m.pedidoUrl"
                  class="hover:underline"
                >
                  Pedido {{ m.pedidoCodigo }}
                </Link>
                <template v-else> Pedido {{ m.pedidoCodigo }} </template>
              </td>
            </tr>
            <tr
              :class="[
                index % 2 ? 'bg-muted/20' : '',
                pedidosAgrupados.has(m.pedidoId || 0)
                  ? 'bg-blue-50/20 dark:bg-blue-950/10'
                  : '',
              ]"
            >
              <td
                class="border-l-2 px-5 py-3"
                :class="
                  m.pedidoId
                    ? 'border-blue-400/70 dark:border-blue-500/50'
                    : 'border-transparent'
                "
              >
                <Link
                  v-if="m.url"
                  :href="m.url"
                  class="inline-flex items-center gap-1 font-medium hover:text-blue-600 hover:underline dark:hover:text-blue-400"
                  >{{ esCobroPedido(m) ? 'Cobro del pedido' : m.nombre
                  }}<ExternalLink
                    class="size-3 shrink-0 text-muted-foreground" /></Link
                ><span v-else class="font-medium">{{
                  esCobroPedido(m) ? 'Cobro del pedido' : m.nombre
                }}</span
                ><span class="mt-1 block text-xs text-muted-foreground"
                  >{{
                    esCobroPedido(m)
                      ? 'Importe cobrado por el trabajo'
                      : m.tipo
                  }}<template
                    v-if="m.tipo === 'Venta de productos' && m.pedidoCodigo"
                  >
                    · Venta asociada a este pedido</template
                  ></span
                >
              </td>
              <td
                class="px-3 py-3 text-xs whitespace-nowrap text-muted-foreground"
              >
                {{ formatDate(m.fecha, { includeTime: false }) }}
              </td>
              <td
                class="px-5 py-3 text-right font-semibold whitespace-nowrap tabular-nums"
                :class="
                  tipo === 'cobros'
                    ? 'text-emerald-700 dark:text-emerald-300'
                    : ''
                "
              >
                {{ moneda(m.total) }}
              </td>
            </tr></template
          >
        </tbody>
      </table>
    </div>
    <div
      v-else
      class="flex flex-1 flex-col items-center justify-center gap-2 px-5 py-10 text-center"
    >
      <component
        :is="tipo === 'cobros' ? ArrowDownLeft : ArrowUpRight"
        class="size-9 text-muted-foreground/30"
      />
      <p class="text-sm font-medium">
        {{
          tipo === 'cobros'
            ? 'No hay entradas en esta vista'
            : 'No hay salidas en esta vista'
        }}
      </p>
      <p class="max-w-xs text-xs leading-relaxed text-muted-foreground">
        {{
          tipo === 'cobros'
            ? 'Las ventas se suman al cobrarlas y los pedidos, al entregarlos.'
            : 'Los repuestos de pedidos no se registran como salida independiente.'
        }}
      </p>
    </div>
    <div
      v-if="movimientosVisibles.length"
      class="flex items-center justify-between gap-3 border-t bg-muted/30 px-5 py-3"
    >
      <span class="text-sm font-medium">{{
        tipo === 'cobros' ? 'Total de entradas' : 'Total de salidas'
      }}</span
      ><strong class="text-lg tabular-nums">{{ moneda(total) }}</strong>
    </div>
  </section>
</template>
