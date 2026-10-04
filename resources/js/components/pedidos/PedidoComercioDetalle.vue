<script setup lang="ts">
import AgregarProductosDialog from '@/components/comercio/AgregarProductosDialog.vue';
import CobroDialog from '@/components/comercio/CobroDialog.vue';
import OperacionDetalle from '@/components/comercio/OperacionDetalle.vue';
import VentaProductosTable from '@/components/comercio/VentaProductosTable.vue';
import { Button } from '@/components/ui/button';
import { usePrivacyMode } from '@/composables/usePrivacyMode';
import {
  moneda as formatMoney,
  type OpcionesComercio,
  type Operacion,
} from '@/types/comercio';
import type { Estado } from '@/types/estado.interface';
import type { PedidoEstado } from '@/types/pedido-estado.interface';
import type { Pedido } from '@/types/pedido.interface';
import { formatDate } from '@/utils/formatter';
import { Link, usePage } from '@inertiajs/vue3';
import {
  ArrowUpRight,
  FileText,
  Pencil,
  Plus,
  ShoppingBag,
  Wrench,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import PedidoEstadosTimeline from './PedidoEstadosTimeline.vue';
const props = defineProps<{
  pedido: Pedido;
  estados: (PedidoEstado & { estado: Estado })[];
  opciones: OpcionesComercio;
}>();
const historial = ref<HTMLElement | null>(null);
defineExpose({
  abrirHistorial: () =>
    historial.value?.scrollIntoView({ behavior: 'smooth', block: 'start' }),
});
const privacy = usePrivacyMode();
const moneda = (value: number) =>
  privacy.value ? '••••••' : formatMoney(value);
const activas = computed(() =>
  (props.pedido.operaciones || []).filter((op) => op.estado !== 'anulada'),
);
const reparacion = computed(() =>
  activas.value.find((op) => op.tipo === 'reparacion'),
);
const repuestos = computed(
  () =>
    reparacion.value?.items.filter((item) => item.tipo === 'repuesto') || [],
);
const ventas = computed(() =>
  activas.value.filter((op) => op.tipo === 'venta' && op.items.length),
);
const total = computed(() =>
  activas.value.reduce((sum, op) => sum + op.total_centavos, 0),
);
const cobrado = computed(() =>
  activas.value
    .filter((op) => op.fecha_cobro)
    .reduce((sum, op) => sum + op.total_centavos, 0),
);
const totalRepuestos = computed(() =>
  repuestos.value.reduce(
    (sum, row) => sum + row.precio_unitario_centavos * row.cantidad,
    0,
  ),
);
const totalVentas = computed(() =>
  ventas.value.reduce((sum, op) => sum + op.total_centavos, 0),
);
const manoObra = computed(
  () => (reparacion.value?.total_centavos || 0) - totalRepuestos.value,
);
const agregar = ref(usePage().url.includes('agregar_productos=1'));
const ventaACobrar = ref<Operacion | null>(null);
const cobrar = ref(false);
function abrirCobro(venta: Operacion) {
  ventaACobrar.value = venta;
  cobrar.value = true;
}
const fecha = (value: string) => formatDate(value, { includeTime: false });
</script>
<template>
  <div
    class="pedido-comercio grid min-w-0 items-start gap-6 xl:grid-cols-[minmax(0,1fr)_300px]"
  >
    <div class="min-w-0 space-y-6">
      <section
        class="surface overflow-hidden rounded-xl border"
        aria-labelledby="informacion-pedido"
      >
        <div
          class="flex items-center justify-between gap-3 border-b bg-blue-500/10 px-5 py-4"
        >
          <h2
            id="informacion-pedido"
            class="flex items-center gap-2 font-semibold"
          >
            <FileText class="size-5 text-blue-500" />Información del pedido
          </h2>
          <Button variant="ghost" size="sm" as-child
            ><Link :href="`/Pedido/${pedido.id}/edit?step=2`"
              ><Pencil class="size-4" />Editar presupuesto</Link
            ></Button
          >
        </div>
        <div class="grid gap-6 p-5 lg:grid-cols-2">
          <dl
            class="grid content-start gap-4 text-sm sm:grid-cols-2 lg:grid-cols-1"
          >
            <div>
              <dt class="secondary">Cliente</dt>
              <dd class="mt-1 font-medium">
                {{ pedido.cliente?.nombre }} {{ pedido.cliente?.apellido }}
              </dd>
            </div>
            <div>
              <dt class="secondary">Equipo</dt>
              <dd class="mt-1 font-medium">{{ pedido.equipo }}</dd>
            </div>
            <div>
              <dt class="secondary">Estado de ingreso</dt>
              <dd class="mt-1 whitespace-pre-line">
                {{ pedido.estado_ingreso }}
              </dd>
            </div>
            <div>
              <dt class="secondary">Fecha de ingreso</dt>
              <dd class="mt-1">
                {{ fecha(pedido.fecha_ingreso || pedido.created_at) }}
              </dd>
            </div>
            <div>
              <dt class="secondary">Cargador</dt>
              <dd class="mt-1">
                {{
                  Boolean(pedido.cargador) &&
                  !['no', '0'].includes(String(pedido.cargador).toLowerCase())
                    ? 'Incluido'
                    : 'No'
                }}
              </dd>
            </div>
          </dl>
          <div class="space-y-4">
            <div class="rounded-lg bg-violet-500/10 p-4 text-sm">
              <p class="secondary mb-2">Trabajo a realizar</p>
              <p class="leading-relaxed whitespace-pre-line">
                {{ pedido.trabajo_realizar || 'Pendiente de diagnóstico' }}
              </p>
            </div>
            <div
              class="rounded-lg border border-blue-500/30 bg-blue-500/5 p-4"
              data-testid="resumen-cobro"
            >
              <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-3">
                  <dt class="secondary">Mano de obra</dt>
                  <dd class="font-medium tabular-nums">
                    {{ moneda(manoObra) }}
                  </dd>
                </div>
                <div class="flex justify-between gap-3">
                  <dt class="secondary">Repuestos</dt>
                  <dd class="font-medium tabular-nums">
                    {{ moneda(totalRepuestos) }}
                  </dd>
                </div>
                <div class="flex justify-between gap-3">
                  <dt class="secondary">Ventas de productos</dt>
                  <dd class="font-medium tabular-nums">
                    {{ moneda(totalVentas) }}
                  </dd>
                </div>
                <div
                  class="flex justify-between gap-3 border-t border-blue-500/30 pt-3 font-semibold"
                >
                  <dt>Total del pedido</dt>
                  <dd class="text-lg tabular-nums">{{ moneda(total) }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                  <dt class="secondary">Ya cobrado</dt>
                  <dd
                    class="text-emerald-600 tabular-nums dark:text-emerald-300"
                  >
                    {{ moneda(cobrado) }}
                  </dd>
                </div>
                <div
                  class="flex justify-between gap-3 border-t border-blue-500/30 pt-3 font-semibold"
                >
                  <dt>Pendiente de cobrar</dt>
                  <dd class="text-lg tabular-nums">
                    {{ moneda(total - cobrado) }}
                  </dd>
                </div>
              </dl>
            </div>
            <p
              v-if="reparacion?.items.length"
              class="rounded-lg bg-emerald-500/10 p-3 text-sm"
            >
              Ganancia de la reparación:
              <strong class="text-emerald-700 dark:text-emerald-300">{{
                moneda(reparacion.ganancia_centavos)
              }}</strong>
              <span class="secondary mt-1 block text-xs"
                >Mano de obra + ganancia de repuestos.
                {{
                  reparacion.fecha_cobro
                    ? 'Cobrada el ' + fecha(reparacion.fecha_cobro) + '.'
                    : 'Se suma a rendimientos cuando se cobra.'
                }}</span
              >
            </p>
          </div>
        </div>
      </section>
      <section
        v-if="repuestos.length && reparacion"
        class="surface overflow-hidden rounded-xl border"
      >
        <div class="border-b bg-amber-500/10 px-5 py-4">
          <h2 class="flex items-center gap-2 font-semibold">
            <Wrench class="size-5 text-amber-500" />Repuestos de la reparación
          </h2>
          <p class="secondary mt-1 text-xs">
            Compras específicas para este equipo. La ganancia corresponde al
            titular.
          </p>
        </div>
        <OperacionDetalle
          :operacion="reparacion"
          :tipos="['repuesto']"
          solo-items
          ocultar-reparto
          costos-visibles
          ocultar-porcentajes
        />
      </section>
      <section class="surface overflow-hidden rounded-xl border">
        <div
          class="flex flex-wrap items-center justify-between gap-3 border-b bg-blue-500/5 px-5 py-4"
        >
          <div>
            <h2 class="flex items-center gap-2 font-semibold">
              <ShoppingBag class="size-5 text-blue-500" />Ventas de productos
            </h2>
            <p class="secondary mt-1 text-xs">
              Productos del inventario vendidos con este pedido.
            </p>
          </div>
          <Button
            v-if="Number(pedido.estadoActual_id) < 5"
            variant="outline"
            @click="agregar = true"
            ><Plus class="size-4" />Agregar productos</Button
          >
        </div>
        <div v-if="!ventas.length" class="secondary p-6 text-sm">
          Todavía no hay productos vendidos con este pedido.
        </div>
        <article
          v-for="venta in ventas"
          :key="venta.id"
          class="border-b last:border-b-0"
        >
          <div
            class="flex flex-wrap items-center justify-between gap-3 bg-muted/35 px-4 py-3 text-sm"
          >
            <Link
              :href="`/comercio/operaciones/${venta.id}?desde=pedido`"
              class="inline-flex items-center gap-1 font-medium text-blue-600 hover:underline dark:text-blue-300"
              >Venta V{{ venta.id }}<ArrowUpRight class="size-3.5"
            /></Link>
            <span
              v-if="venta.fecha_cobro"
              class="text-emerald-700 dark:text-emerald-300"
              >Cobrada el {{ fecha(venta.fecha_cobro) }} ·
              {{ venta.medio_pago || 'Sin medio registrado' }}</span
            >
            <span
              v-else
              class="rounded-full bg-amber-500/15 px-3 py-1 text-xs font-semibold text-amber-800 dark:text-amber-300"
              >Pendiente de cobro</span
            >
          </div>
          <VentaProductosTable :items="venta.items" />
          <div
            class="flex flex-wrap items-center justify-between gap-3 border-t px-4 py-3 text-sm"
          >
            <strong>Total venta {{ moneda(venta.total_centavos) }}</strong>
            <Button
              v-if="!venta.fecha_cobro"
              variant="outline"
              size="sm"
              @click="abrirCobro(venta)"
              >Cobrar esta venta por separado</Button
            >
            <Link
              v-else
              :href="`/comercio/operaciones/${venta.id}?desde=pedido`"
              class="text-blue-600 hover:underline dark:text-blue-300"
              >Ver detalle y reparto</Link
            >
          </div>
        </article>
        <p
          v-if="ventas.some((op) => !op.fecha_cobro)"
          class="secondary border-t px-5 py-3 text-xs"
        >
          Las ventas pendientes están incluidas en el saldo a cobrar del pedido.
        </p>
      </section>
    </div>
    <aside
      ref="historial"
      data-testid="historial-pedido"
      class="min-w-0 scroll-mt-24 xl:sticky xl:top-24"
    >
      <PedidoEstadosTimeline
        :estados="estados"
        :pedido-id="pedido.id"
        editable
        acciones-claras
      />
    </aside>
    <AgregarProductosDialog
      v-model:open="agregar"
      :pedido-id="pedido.id"
      :codigo="pedido.codigo"
      :saldo="total - cobrado"
      :opciones="opciones"
    />
    <CobroDialog
      v-if="ventaACobrar"
      v-model:open="cobrar"
      :endpoint="`/comercio/operaciones/${ventaACobrar.id}/cobrar`"
      :titulo="`Cobrar venta V${ventaACobrar.id}`"
      descripcion="Este importe se descontará del saldo pendiente del pedido."
      :total="ventaACobrar.total_centavos"
      :minimo="ventaACobrar.fecha"
    />
  </div>
</template>
<style scoped>
.surface {
  background: var(--card);
}
.dark .surface {
  background: #242427;
  border-color: #3f3f46;
}
.dark .secondary {
  color: #b9b9c2;
}
.secondary {
  color: var(--muted-foreground);
}
</style>
