<script setup lang="ts">
import PedidoAccionesEstado from '@/components/pedidos/PedidoAccionesEstado.vue';
import PedidoComercioDetalle from '@/components/pedidos/PedidoComercioDetalle.vue';
import PedidoDetalles from '@/components/pedidos/PedidoDetalles.vue';
import PedidoEstadosTimeline from '@/components/pedidos/PedidoEstadosTimeline.vue';
import PedidoProductosTable from '@/components/pedidos/PedidoProductosTable.vue';
import { Button } from '@/components/ui/button';
import { usePedidoNavigation } from '@/composables/usePedidoNavigation';
import AppLayout from '@/layouts/AppLayout.vue';
import pedidoRoutes from '@/routes/pedido';
import type { Cliente } from '@/types/cliente.interface';
import type { OpcionesComercio } from '@/types/comercio';
import type { Estado } from '@/types/estado.interface';
import type { Pedido } from '@/types/pedido.interface';
import type { VentasParticipante } from '@/types/rendimientos.interfaces';
import { Head, router, usePage } from '@inertiajs/vue3';
import {
  ArrowLeft,
  Check,
  ClipboardList,
  History,
  Pencil,
  Printer,
} from 'lucide-vue-next';
import { ref } from 'vue';
const props = defineProps<{
  pedido: Pedido & { cliente: Cliente; estado_actual: Estado };
  estados: any[];
  siguienteEstado: Estado | null;
  ventasParticipantes?: VentasParticipante[];
  titularId?: number;
  opcionesComercio?: OpcionesComercio;
}>();
const page = usePage();
const { listadoUrl, volverAlListado } = usePedidoNavigation(
  props.pedido.id,
  () => props.pedido.estadoActual_id,
);
const detalle = ref<InstanceType<typeof PedidoComercioDetalle> | null>(null);
const breadcrumbs = [
  { title: 'Pedidos', href: listadoUrl() },
  {
    title: props.pedido.codigo,
    href: pedidoRoutes.show({ id: props.pedido.id }).url,
  },
];
const pasos = [
  'En revisión',
  'Pendiente aprobación',
  'En proceso',
  'Finalizado',
  'Entregado',
];
const editar = () =>
  router.visit(pedidoRoutes.edit({ id: props.pedido.id }).url);
const imprimir = () => window.print();
</script>
<template>
  <Head :title="`Pedido ${pedido.codigo}`" />
  <AppLayout
    :breadcrumbs="breadcrumbs"
    :sticky-actions="pedido.comercio_version === 2"
  >
    <div
      class="mx-auto flex w-full min-w-0 flex-1 flex-col gap-5 p-4 md:p-6"
      :class="
        pedido.comercio_version === 2 ? 'max-w-[1500px]' : 'max-w-[1760px]'
      "
    >
      <div
        v-if="Object.keys(page.props.errors).length"
        role="alert"
        class="rounded-xl border border-destructive/30 bg-destructive/5 p-4 text-sm text-destructive"
      >
        <p v-for="(error, key) in page.props.errors" :key="key">{{ error }}</p>
      </div>
      <div
        v-if="pedido.comercio_version === 2"
        class="sticky top-0 z-20 -mx-1 flex flex-wrap items-center justify-between gap-2 rounded-xl border bg-background/95 p-3 shadow-sm backdrop-blur-md print:hidden"
        data-testid="pedido-acciones"
      >
        <Button variant="ghost" size="sm" @click="volverAlListado"
          ><ArrowLeft class="size-4" />Volver a pedidos</Button
        >
        <div class="flex flex-wrap items-center gap-2">
          <Button variant="outline" size="sm" @click="editar"
            ><Pencil class="size-4" /><span class="sm:hidden">Editar</span
            ><span class="hidden sm:inline">Editar datos</span></Button
          >
          <Button
            variant="outline"
            size="icon"
            class="hidden sm:inline-flex"
            aria-label="Imprimir pedido"
            title="Imprimir pedido"
            @click="imprimir"
            ><Printer class="size-4"
          /></Button>
          <PedidoAccionesEstado
            :pedido="pedido"
            :siguiente-estado="siguienteEstado"
            etiqueta-explicita
          />
        </div>
      </div>
      <header
        v-if="pedido.comercio_version === 2"
        class="flex flex-wrap items-start justify-between gap-3 rounded-xl bg-gradient-to-r from-blue-600 to-violet-600 p-6 text-white"
      >
        <div>
          <div class="flex flex-wrap items-center gap-3">
            <h1 class="text-2xl font-bold">
              {{ pedido.codigo }}
            </h1>
            <span
              class="rounded-full bg-blue-100 px-3 py-1 text-sm font-semibold text-blue-800 dark:bg-blue-950 dark:text-blue-200"
              >{{ pedido.estado_actual?.nombre }}</span
            >
          </div>
          <p class="mt-2 text-sm text-white/90">
            {{ pedido.cliente.nombre }} {{ pedido.cliente.apellido }}
            <span class="mx-1">·</span> {{ pedido.equipo }}
          </p>
        </div>
        <div class="flex items-center gap-2">
          <Button
            class="sm:hidden"
            variant="ghost"
            size="icon"
            aria-label="Imprimir pedido"
            @click="imprimir"
            ><Printer class="size-4"
          /></Button>
          <Button
            variant="ghost"
            size="sm"
            @click="detalle?.abrirHistorial()"
            class="xl:hidden"
            ><History class="size-4" />Historial</Button
          >
        </div>
      </header>
      <header
        v-else
        class="sticky top-0 z-20 rounded-xl border bg-background/95 px-4 py-4 shadow-sm backdrop-blur-md md:px-5 print:static"
      >
        <div class="flex flex-wrap items-center justify-between gap-4">
          <div class="flex min-w-0 items-center gap-3">
            <span
              class="hidden size-12 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-violet-600 text-white shadow-sm sm:flex"
              ><ClipboardList class="size-6"
            /></span>
            <div class="min-w-0">
              <div class="flex flex-wrap items-center gap-2.5">
                <h1 class="text-2xl font-bold tracking-tight">
                  {{ pedido.codigo }}
                </h1>
                <span
                  class="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-800 dark:bg-blue-500/20 dark:text-blue-200"
                  >{{ pedido.estado_actual?.nombre || 'Sin estado' }}</span
                >
              </div>
              <p class="mt-1 text-sm text-muted-foreground">
                {{ pedido.cliente.nombre }} {{ pedido.cliente.apellido
                }}<span v-if="pedido.cliente.dni" class="ml-2 text-xs"
                  >· DNI {{ pedido.cliente.dni }}</span
                >
              </p>
            </div>
          </div>
          <div
            class="flex w-full flex-wrap items-center gap-2 sm:w-auto print:hidden"
          >
            <Button variant="outline" size="sm" @click="editar"
              ><Pencil class="size-4" /> Editar</Button
            ><Button variant="outline" size="sm" @click="imprimir"
              ><Printer class="size-4" /> Imprimir</Button
            >
            <div class="ml-auto border-l pl-4 sm:ml-2">
              <PedidoAccionesEstado
                :pedido="pedido"
                :siguiente-estado="siguienteEstado"
              />
            </div>
          </div>
        </div>
      </header>
      <ol
        v-if="pedido.comercio_version !== 2"
        class="grid grid-cols-5 gap-2 rounded-xl border bg-card px-3 py-4 md:px-5"
        aria-label="Progreso del pedido"
      >
        <li
          v-for="(paso, index) in pasos"
          :key="paso"
          class="flex min-w-0 flex-col items-center gap-2 text-center text-xs sm:flex-row sm:text-left"
          :aria-current="
            Number(pedido.estadoActual_id) === index + 1 ? 'step' : undefined
          "
        >
          <span
            class="flex size-7 shrink-0 items-center justify-center rounded-full text-xs font-semibold"
            :class="
              Number(pedido.estadoActual_id) >= index + 1
                ? 'bg-blue-600 text-white'
                : 'bg-muted text-muted-foreground'
            "
            ><Check
              v-if="Number(pedido.estadoActual_id) > index + 1"
              class="size-3.5"
            /><template v-else>{{ index + 1 }}</template></span
          ><span
            :class="
              Number(pedido.estadoActual_id) === index + 1
                ? 'font-semibold text-blue-700 dark:text-blue-300'
                : 'text-muted-foreground'
            "
            >{{ paso }}</span
          >
        </li>
      </ol>
      <PedidoComercioDetalle
        v-if="pedido.comercio_version === 2"
        ref="detalle"
        :pedido="pedido"
        :estados="estados"
        :opciones="opcionesComercio!"
      />
      <div
        v-else
        class="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_340px]"
      >
        <div class="min-w-0 space-y-5">
          <PedidoDetalles :pedido="pedido" /><PedidoProductosTable
            v-if="pedido.productos_seleccionados?.length"
            :productos="pedido.productos_seleccionados"
          />
        </div>
        <PedidoEstadosTimeline
          :estados="estados"
          :pedido-id="pedido.id"
          editable
        />
      </div>
    </div>
  </AppLayout>
</template>
