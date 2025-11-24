<template>
  <Head :title="`${cliente.nombre} ${cliente.apellido}`" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-6 p-4">
      <!-- Contenedor principal con max-width -->
      <div class="mx-auto w-full max-w-6xl space-y-6">
        <!-- Hero Header con gradiente -->
        <div
          class="relative overflow-hidden rounded-2xl border bg-gradient-to-br from-cyan-500 via-blue-500 to-indigo-500 p-8 text-white shadow-xl"
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
                <div
                  class="flex h-12 w-12 items-center justify-center rounded-full bg-white/20 backdrop-blur-sm"
                >
                  <User class="h-6 w-6" />
                </div>
                <h1 class="text-4xl font-bold tracking-tight">
                  {{ cliente.nombre }} {{ cliente.apellido }}
                </h1>
              </div>
              <p v-if="cliente.created_at" class="text-lg text-white/90">
                Cliente desde {{ formatDate(cliente.created_at) }}
              </p>
            </div>

            <div class="flex gap-2">
              <Button
                variant="outline"
                class="border-white/40 bg-white/90 text-gray-900 hover:bg-white"
                @click="handleEdit"
              >
                <Pencil class="mr-2 h-4 w-4" />
                Editar
              </Button>
              <Button
                variant="destructive"
                class="bg-red-600 hover:bg-red-700"
                @click="confirmDelete"
              >
                <Trash2 class="mr-2 h-4 w-4" />
                Eliminar
              </Button>
            </div>
          </div>
        </div>

        <!-- Grid de 2 columnas -->
        <div class="grid gap-6 md:grid-cols-2">
          <!-- Información Personal -->
          <div
            class="overflow-hidden rounded-xl border bg-white shadow-sm dark:bg-gray-950"
          >
            <div
              class="border-b bg-gradient-to-r from-blue-50 to-cyan-50 px-6 py-4 dark:from-blue-950/30 dark:to-cyan-950/30"
            >
              <div class="flex items-center gap-3">
                <div
                  class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/50"
                >
                  <User class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                </div>
                <div>
                  <h3 class="font-semibold text-gray-900 dark:text-gray-100">
                    Información Personal
                  </h3>
                  <p class="text-xs text-gray-600 dark:text-gray-400">
                    Datos de contacto del cliente
                  </p>
                </div>
              </div>
            </div>

            <div class="p-6">
              <dl class="space-y-4">
                <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-900/50">
                  <dt
                    class="text-xs font-medium tracking-wider text-gray-600 uppercase dark:text-gray-400"
                  >
                    DNI
                  </dt>
                  <dd
                    class="mt-1 font-mono text-lg font-bold text-gray-900 dark:text-gray-100"
                  >
                    {{ cliente.dni }}
                  </dd>
                </div>
                <div>
                  <dt
                    class="text-sm font-medium text-gray-600 dark:text-gray-400"
                  >
                    Nombre completo
                  </dt>
                  <dd
                    class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-100"
                  >
                    {{ cliente.nombre }} {{ cliente.apellido }}
                  </dd>
                </div>
                <div>
                  <dt
                    class="text-sm font-medium text-gray-600 dark:text-gray-400"
                  >
                    Correo Electrónico
                  </dt>
                  <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                    {{ cliente.mail }}
                  </dd>
                </div>
                <div>
                  <dt
                    class="text-sm font-medium text-gray-600 dark:text-gray-400"
                  >
                    Teléfono
                  </dt>
                  <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                    {{ cliente.telefono }}
                  </dd>
                </div>
                <div>
                  <dt
                    class="text-sm font-medium text-gray-600 dark:text-gray-400"
                  >
                    Dirección
                  </dt>
                  <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                    {{ cliente.direccion }}
                  </dd>
                </div>
              </dl>
            </div>
          </div>

          <!-- Estadísticas -->
          <div
            class="overflow-hidden rounded-xl border bg-white shadow-sm dark:bg-gray-950"
          >
            <div
              class="border-b bg-gradient-to-r from-purple-50 to-pink-50 px-6 py-4 dark:from-purple-950/30 dark:to-pink-950/30"
            >
              <div class="flex items-center gap-3">
                <div
                  class="flex h-10 w-10 items-center justify-center rounded-lg bg-purple-100 dark:bg-purple-900/50"
                >
                  <BarChart3
                    class="h-5 w-5 text-purple-600 dark:text-purple-400"
                  />
                </div>
                <div>
                  <h3 class="font-semibold text-gray-900 dark:text-gray-100">
                    Estadísticas
                  </h3>
                  <p class="text-xs text-gray-600 dark:text-gray-400">
                    Resumen de actividad
                  </p>
                </div>
              </div>
            </div>

            <div class="p-6">
              <div class="grid grid-cols-2 gap-4">
                <div
                  class="rounded-xl border-2 border-blue-200 bg-gradient-to-br from-blue-50 to-cyan-50 p-4 dark:border-blue-800 dark:from-blue-950/30 dark:to-cyan-950/30"
                >
                  <div
                    class="text-3xl font-bold text-blue-600 dark:text-blue-400"
                  >
                    {{ cliente.pedidos?.length || 0 }}
                  </div>
                  <div
                    class="text-sm font-medium text-gray-700 dark:text-gray-300"
                  >
                    Pedidos totales
                  </div>
                </div>

                <div
                  class="rounded-xl border-2 border-orange-200 bg-gradient-to-br from-orange-50 to-yellow-50 p-4 dark:border-orange-800 dark:from-orange-950/30 dark:to-yellow-950/30"
                >
                  <div
                    class="text-3xl font-bold text-orange-600 dark:text-orange-400"
                  >
                    {{ pedidosActivos }}
                  </div>
                  <div
                    class="text-sm font-medium text-gray-700 dark:text-gray-300"
                  >
                    Pedidos activos
                  </div>
                </div>

                <div
                  class="rounded-xl border-2 border-green-200 bg-gradient-to-br from-green-50 to-emerald-50 p-4 dark:border-green-800 dark:from-green-950/30 dark:to-emerald-950/30"
                >
                  <div
                    class="text-3xl font-bold text-green-600 dark:text-green-400"
                  >
                    {{ pedidosFinalizados }}
                  </div>
                  <div
                    class="text-sm font-medium text-gray-700 dark:text-gray-300"
                  >
                    Pedidos Completos
                  </div>
                </div>

                <div
                  class="rounded-xl border-2 border-purple-200 bg-gradient-to-br from-purple-50 to-pink-50 p-4 dark:border-purple-800 dark:from-purple-950/30 dark:to-pink-950/30"
                >
                  <div
                    class="text-xl font-bold text-purple-600 dark:text-purple-400"
                  >
                    {{ totalGastado }}
                  </div>
                  <div
                    class="text-sm font-medium text-gray-700 dark:text-gray-300"
                  >
                    Total gastado
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Pedidos recientes -->
        <div
          class="overflow-hidden rounded-xl border bg-white shadow-sm dark:bg-gray-950"
        >
          <div
            class="flex items-center justify-between border-b bg-gradient-to-r from-green-50 to-emerald-50 px-6 py-4 dark:from-green-950/30 dark:to-emerald-950/30"
          >
            <div class="flex items-center gap-3">
              <div
                class="flex h-10 w-10 items-center justify-center rounded-lg bg-green-100 dark:bg-green-900/50"
              >
                <Package class="h-5 w-5 text-green-600 dark:text-green-400" />
              </div>
              <div>
                <h3 class="font-semibold text-gray-900 dark:text-gray-100">
                  Últimos 10 Pedidos
                </h3>
                <p class="text-xs text-gray-600 dark:text-gray-400">
                  {{ cliente.pedidos?.length || 0 }} pedido{{
                    (cliente.pedidos?.length || 0) !== 1 ? 's' : ''
                  }}
                  registrado{{
                    (cliente.pedidos?.length || 0) !== 1 ? 's' : ''
                  }}
                </p>
              </div>
            </div>
            <Button
              size="sm"
              @click="handleNewPedido"
              class="bg-green-600 hover:bg-green-700"
            >
              <Plus class="mr-2 h-4 w-4" />
              Nuevo Pedido
            </Button>
          </div>

          <div
            v-if="cliente.pedidos && cliente.pedidos.length > 0"
            class="divide-y divide-gray-200 dark:divide-gray-800"
          >
            <div
              v-for="(pedido, index) in cliente.pedidos"
              :key="pedido.id"
              :class="[
                'flex items-center justify-between p-4 transition-colors',
                index % 2 === 0
                  ? 'bg-white dark:bg-gray-950'
                  : 'bg-gray-50/50 dark:bg-gray-900/30',
                'hover:bg-green-50 dark:hover:bg-green-950/20',
              ]"
            >
              <div class="flex-1 space-y-2">
                <div class="flex items-center gap-2">
                  <span
                    class="font-mono font-bold text-purple-600 dark:text-purple-400"
                    >{{ pedido.codigo }}</span
                  >
                  <Badge
                    v-if="pedido.estadoActual_id"
                    :variant="getEstadoConfig(pedido.estadoActual_id).variant"
                    :class="getEstadoConfig(pedido.estadoActual_id).className"
                  >
                    <component
                      :is="
                        getEstadoConfig(pedido.estadoActual_id).iconComponent
                      "
                      class="mr-1 h-3 w-3"
                    />
                    {{ pedido.estado_actual?.nombre || 'Sin estado' }}
                  </Badge>
                </div>
                <div
                  class="text-sm font-medium text-gray-900 dark:text-gray-100"
                >
                  {{ pedido.equipo }}
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-400">
                  {{ pedido.estado_ingreso }}
                </div>
                <div
                  v-if="pedido.trabajo_realizar"
                  class="text-xs text-gray-500 dark:text-gray-500"
                >
                  {{ pedido.trabajo_realizar }}
                </div>
                <div
                  class="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-500"
                >
                  <span>Ingreso: {{ formatDate(pedido.created_at) }}</span>
                  <span v-if="pedido.cargador" class="flex items-center gap-1">
                    🔌 Con cargador
                  </span>
                </div>
              </div>

              <div class="flex items-center gap-3">
                <div v-if="pedido.presupuesto" class="text-right">
                  <div
                    class="text-base font-bold text-green-600 dark:text-green-400"
                  >
                    {{ formatMoney(pedido.presupuesto) }}
                  </div>
                  <div class="text-xs text-gray-500 dark:text-gray-500">
                    Presupuesto
                  </div>
                </div>
                <Button
                  variant="ghost"
                  size="sm"
                  @click="handleViewPedido(pedido.id)"
                  class="hover:bg-green-100 dark:hover:bg-green-900/50"
                  title="Ver detalles"
                >
                  <Eye class="h-4 w-4" />
                </Button>
              </div>
            </div>
          </div>

          <div v-else class="bg-gray-50 py-12 text-center dark:bg-gray-900/30">
            <Package
              class="mx-auto mb-4 h-12 w-12 text-gray-400 dark:text-gray-600"
            />
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
              No hay pedidos registrados para este cliente.
            </p>
            <Button class="mt-4" size="sm" @click="handleNewPedido">
              <Plus class="mr-2 h-4 w-4" />
              Crear primer pedido
            </Button>
          </div>
        </div>
      </div>
    </div>

    <!-- Diálogo de confirmación -->
    <AlertDialog v-model:open="showDeleteDialog">
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>¿Estás seguro?</AlertDialogTitle>
          <AlertDialogDescription>
            Se eliminará a
            <strong>{{ cliente.nombre }} {{ cliente.apellido }}</strong
            >. Esta acción no se puede deshacer.
          </AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel>Cancelar</AlertDialogCancel>
          <AlertDialogAction
            @click="executeDelete"
            class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
          >
            Eliminar
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  </AppLayout>
</template>

<script setup lang="ts">
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import clienteRoutes from '@/routes/cliente';
import pedidoRoutes from '@/routes/pedido';
import type { BreadcrumbItem } from '@/types';
import type { Cliente } from '@/types/cliente.interface';
import type { Pedido } from '@/types/pedido.interface';
import { formatDate, formatMoney } from '@/utils/formatter';
import { Head, router } from '@inertiajs/vue3';
import {
  BarChart3,
  CheckCircle,
  Clock,
  Eye,
  Package,
  Pencil,
  Plus,
  Search,
  Trash2,
  User,
  Zap,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';

interface Props {
  cliente: Cliente & {
    pedidos?: Pedido[];
  };
}

const props = defineProps<Props>();
const cliente = props.cliente;
const showDeleteDialog = ref(false);

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Clientes',
    href: clienteRoutes.index().url,
  },
  {
    title: `${cliente.nombre} ${cliente.apellido}`,
    href: clienteRoutes.show({ id: cliente.id }).url,
  },
];

const pedidosActivos = computed(() => {
  if (!cliente.pedidos) return 0;
  return cliente.pedidos.filter(
    (p) =>
      p.estadoActual_id && p.estadoActual_id !== 4 && p.estadoActual_id !== 5,
  ).length;
});

const pedidosFinalizados = computed(() => {
  if (!cliente.pedidos) return 0;
  return cliente.pedidos.filter(
    (p) => p.estadoActual_id === 4 || p.estadoActual_id === 5,
  ).length;
});

const totalGastado = computed(() => {
  if (!cliente.pedidos) return formatMoney(0);
  const total = cliente.pedidos
    .filter((p) => p.presupuesto != null && p.presupuesto > 0)
    .reduce((sum, p) => sum + Number(p.presupuesto), 0);
  return formatMoney(total);
});

const getEstadoConfig = (estadoId: number) => {
  const configs = {
    1: {
      variant: 'default',
      className:
        'bg-yellow-100 text-yellow-800 border-yellow-300 dark:bg-yellow-900/30 dark:text-yellow-400 dark:border-yellow-700',
      iconComponent: Search,
    },
    2: {
      variant: 'default',
      className:
        'bg-orange-100 text-orange-800 border-orange-300 dark:bg-orange-900/30 dark:text-orange-400 dark:border-orange-700',
      iconComponent: Clock,
    },
    3: {
      variant: 'default',
      className:
        'bg-blue-100 text-blue-800 border-blue-300 dark:bg-blue-900/30 dark:text-blue-400 dark:border-blue-700',
      iconComponent: Zap,
    },
    4: {
      variant: 'default',
      className:
        'bg-green-100 text-green-800 border-green-300 dark:bg-green-900/30 dark:text-green-400 dark:border-green-700',
      iconComponent: CheckCircle,
    },
    5: {
      variant: 'default',
      className:
        'bg-emerald-100 text-emerald-900 border-emerald-400 font-medium dark:bg-emerald-900/30 dark:text-emerald-400 dark:border-emerald-700',
      iconComponent: Package,
    },
  } as const;
  return configs[estadoId as keyof typeof configs] || configs[3];
};

const handleEdit = () => {
  router.visit(clienteRoutes.edit({ id: cliente.id }).url);
};

const handleNewPedido = () => {
  router.visit(pedidoRoutes.create().url, {
    data: { cliente_id: cliente.id },
  });
};

const handleViewPedido = (pedidoId: number) => {
  router.visit(pedidoRoutes.show({ id: pedidoId }).url);
};

const confirmDelete = () => {
  showDeleteDialog.value = true;
};

const executeDelete = () => {
  router.delete(clienteRoutes.destroy({ id: cliente.id }).url, {
    onSuccess: () => {
      toast.success('¡Cliente eliminado!', {
        description: `${cliente.nombre} ${cliente.apellido} ha sido eliminado exitosamente.`,
      });
    },
    onError: () => {
      toast.error('Error al eliminar', {
        description:
          'No se pudo eliminar el cliente. Por favor, intenta nuevamente.',
      });
    },
  });
};
</script>
