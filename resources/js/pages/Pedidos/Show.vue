<template>
  <Head :title="`Pedido ${pedido.codigo}`" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-6 p-4">
      <!-- Hero Header con gradiente -->
      <div
        class="relative overflow-hidden rounded-2xl border bg-gradient-to-br from-blue-500 via-purple-500 to-pink-500 p-8 text-white shadow-xl"
      >
        <!-- Pattern decorativo -->
        <div class="absolute inset-0 opacity-10">
          <div
            class="absolute h-full w-full bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxnIGZpbGw9IiNmZmYiIGZpbGwtb3BhY2l0eT0iMSI+PHBhdGggZD0iTTM2IDE0YzAtNi42MjctNS4zNzMtMTItMTItMTJzLTEyIDUuMzczLTEyIDEyIDUuMzczIDEyIDEyIDEyIDEyLTUuMzczIDEyLTEyem0wIDM2YzAtNi42MjctNS4zNzMtMTItMTItMTJzLTEyIDUuMzczLTEyIDEyIDUuMzczIDEyIDEyIDEyIDEyLTUuMzczIDEyLTEyem0zNi0zNmMwLTYuNjI3LTUuMzczLTEyLTEyLTEycy0xMiA1LjM3My0xMiAxMiA1LjM3MyAxMiAxMiAxMiAxMi01LjM3MyAxMi0xMnptMCAzNmMwLTYuNjI3LTUuMzczLTEyLTEyLTEycy0xMiA1LjM3My0xMiAxMiA1LjM3MyAxMiAxMiAxMiAxMi01LjM3MyAxMi0xMnoiLz48L2c+PC9nPjwvc3ZnPg==')] opacity-20"
          />
        </div>

        <div class="relative flex items-center justify-between">
          <div class="space-y-2">
            <div class="flex items-center gap-3">
              <h1 class="text-4xl font-bold tracking-tight">
                {{ pedido.codigo }}
              </h1>
              <Badge
                :variant="getEstadoVariant(pedido.estado_actual?.id)"
                class="text-sm font-semibold"
              >
                {{ pedido.estado_actual?.nombre || 'Sin estado' }}
              </Badge>
            </div>
            <p class="text-lg text-white/90">
              {{ pedido.cliente.nombre }} {{ pedido.cliente.apellido }}
            </p>
            <p class="text-sm text-white/70">DNI: {{ pedido.cliente.dni }}</p>
          </div>

          <div class="flex gap-2">
            <Button
              variant="outline"
              class="border-white/40 bg-white/90 text-gray-900 hover:bg-white dark:bg-white"
              @click="handleEdit"
            >
              <Pencil class="mr-2 h-4 w-4" />
              Editar
            </Button>

            <Button
              variant="outline"
              class="border-white/40 bg-white/90 text-gray-900 hover:bg-white dark:bg-white"
              @click="handlePrint"
            >
              <Printer class="mr-2 h-4 w-4" />
              Imprimir
            </Button>
          </div>
        </div>
      </div>

      <!-- Contenedor principal -->
      <div class="mx-auto w-full max-w-[1600px] space-y-6">
        <!-- Fila 1: Info del pedido + Estados -->
        <div class="grid gap-6 lg:grid-cols-3">
          <!-- Info del pedido (2/3) -->
          <div class="lg:col-span-2">
            <PedidoDetalles :pedido="pedido" />
          </div>

          <!-- Estados (1/3) -->
          <div class="space-y-6">
            <PedidoEstadosTimeline :estados="estados" :pedido-id="pedido.id" />

            <PedidoAccionesEstado
              :pedido="pedido"
              :siguiente-estado="siguienteEstado"
            />
          </div>
        </div>

        <!-- Fila 2: Productos -->
        <PedidoProductosTable
          v-if="
            pedido.productos_seleccionados &&
            pedido.productos_seleccionados.length > 0
          "
          :productos="pedido.productos_seleccionados"
        />
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import PedidoAccionesEstado from '@/components/pedidos/PedidoAccionesEstado.vue';
import PedidoDetalles from '@/components/pedidos/PedidoDetalles.vue';
import PedidoEstadosTimeline from '@/components/pedidos/PedidoEstadosTimeline.vue';
import PedidoProductosTable from '@/components/pedidos/PedidoProductosTable.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import pedidoRoutes from '@/routes/pedido';
import type { BreadcrumbItem } from '@/types';
import { Cliente } from '@/types/cliente.interface';
import { Estado } from '@/types/estado.interface';
import type { Pedido } from '@/types/pedido.interface';
import { Head, router } from '@inertiajs/vue3';
import { Pencil, Printer } from 'lucide-vue-next';

interface Props {
  pedido: Pedido & {
    cliente: Cliente;
    estado_actual: Estado;
  };
  estados: Array<any>;
  siguienteEstado: any;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Pedidos',
    href: pedidoRoutes.index().url,
  },
  {
    title: props.pedido.codigo,
    href: pedidoRoutes.show({ id: props.pedido.id }).url,
  },
];

const getEstadoVariant = (estadoId: number | undefined) => {
  if (!estadoId) return 'secondary';
  const variants: Record<number, any> = {
    1: 'secondary',
    2: 'secondary',
    3: 'default',
    4: 'default',
    5: 'default',
  };
  return variants[estadoId] || 'secondary';
};

const handleEdit = () => {
  router.visit(pedidoRoutes.edit({ id: props.pedido.id }).url);
};

const handlePrint = () => {
  window.print();
};
</script>
