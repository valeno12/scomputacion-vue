<!-- pages/Clientes/Show.vue -->
<template>
  <Head :title="`${props.cliente.nombre} ${props.cliente.apellido}`" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
      <div class="mx-auto w-full max-w-6xl space-y-6">
        <!-- Header con acciones -->
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-3xl font-bold tracking-tight">
              {{ props.cliente.nombre }} {{ props.cliente.apellido }}
            </h2>
            <p class="text-muted-foreground">
              Cliente desde {{ formatDate(props.cliente.created_at) }}
            </p>
          </div>

          <div class="flex gap-2">
            <Button variant="outline" @click="handleEdit">
              <Pencil class="mr-2 h-4 w-4" />
              Editar
            </Button>
            <Button variant="destructive" @click="confirmDelete">
              <Trash2 class="mr-2 h-4 w-4" />
              Eliminar
            </Button>
          </div>
        </div>

        <!-- Grid de 2 columnas -->
        <div class="grid gap-6 md:grid-cols-2">
          <!-- Información Personal -->
          <ShowCard
            card-title="Información Personal"
            card-description="Datos de contacto del cliente"
          >
            <dl class="space-y-4">
              <div>
                <dt class="text-sm font-medium text-muted-foreground">DNI</dt>
                <dd class="mt-1 text-sm">{{ props.cliente.dni }}</dd>
              </div>

              <div>
                <dt class="text-sm font-medium text-muted-foreground">
                  Nombre completo
                </dt>
                <dd class="mt-1 text-sm">
                  {{ props.cliente.nombre }} {{ props.cliente.apellido }}
                </dd>
              </div>

              <div>
                <dt class="text-sm font-medium text-muted-foreground">
                  Correo Electrónico
                </dt>
                <dd class="mt-1 text-sm">
                  
                    :href="`mailto:${props.cliente.mail}`"
                    class="text-primary hover:underline"
                  >
                    {{ props.cliente.mail }}
                  </a>
                </dd>
              </div>

              <div>
                <dt class="text-sm font-medium text-muted-foreground">
                  Teléfono
                </dt>
                <dd class="mt-1 text-sm">
                  
                    :href="`tel:${props.cliente.telefono}`"
                    class="text-primary hover:underline"
                  >
                    {{ props.cliente.telefono }}
                  </a>
                </dd>
              </div>

              <div>
                <dt class="text-sm font-medium text-muted-foreground">
                  Dirección
                </dt>
                <dd class="mt-1 text-sm">{{ props.cliente.direccion }}</dd>
              </div>
            </dl>
          </ShowCard>

          <!-- Estadísticas -->
          <ShowCard
            card-title="Estadísticas"
            card-description="Resumen de actividad"
          >
            <div class="grid grid-cols-2 gap-4">
              <div class="rounded-lg border p-4">
                <div class="text-2xl font-bold">
                  {{ props.cliente.pedidos?.length || 0 }}
                </div>
                <div class="text-sm text-muted-foreground">
                  Pedidos totales
                </div>
              </div>

              <div class="rounded-lg border p-4">
                <div class="text-2xl font-bold">
                  {{ pedidosActivos }}
                </div>
                <div class="text-sm text-muted-foreground">
                  Pedidos activos
                </div>
              </div>
            </div>
          </ShowCard>
        </div>

        <!-- Pedidos recientes -->
        <ShowCard card-title="Pedidos Recientes">
          <template #header-actions>
            <Button size="sm" @click="handleNewPedido">
              <Plus class="mr-2 h-4 w-4" />
              Nuevo Pedido
            </Button>
          </template>

          <div v-if="props.cliente.pedidos && props.cliente.pedidos.length > 0">
            <div class="space-y-4">
              <div
                v-for="pedido in props.cliente.pedidos"
                :key="pedido.id"
                class="flex items-center justify-between rounded-lg border p-4 hover:bg-accent"
              >
                <div class="flex-1">
                  <div class="flex items-center gap-2">
                    <span class="font-medium">Pedido #{{ pedido.id }}</span>
                    <Badge :variant="getEstadoVariant(pedido.estado_actual)">
                      {{ pedido.estado_actual?.nombre || 'Sin estado' }}
                    </Badge>
                  </div>
                  <div class="mt-1 text-sm text-muted-foreground">
                    {{ pedido.trabajo_realizar || 'Sin descripción' }}
                  </div>
                  <div class="mt-1 text-xs text-muted-foreground">
                    Ingreso: {{ formatDate(pedido.fecha_ingreso) }}
                  </div>
                </div>

                <div class="flex items-center gap-2">
                  <span class="text-sm font-medium">
                    {{ formatMoney(pedido.precio_total) }}
                  </span>
                  <Button
                    variant="ghost"
                    size="sm"
                    @click="handleViewPedido(pedido.id)"
                  >
                    <Eye class="h-4 w-4" />
                  </Button>
                </div>
              </div>
            </div>
          </div>

          <div
            v-else
            class="py-12 text-center text-sm text-muted-foreground"
          >
            No hay pedidos registrados para este cliente.
          </div>
        </ShowCard>
      </div>
    </div>

    <!-- Diálogo de confirmación de eliminación -->
    <AlertDialog v-model:open="showDeleteDialog">
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>¿Estás seguro?</AlertDialogTitle>
          <AlertDialogDescription>
            Se eliminará a
            <strong>{{ props.cliente.nombre }} {{ props.cliente.apellido }}</strong>.
            Esta acción no se puede deshacer.
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
import ShowCard from '@/components/common/ShowCard.vue';
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
import type { BreadcrumbItem } from '@/types';
import type { Cliente } from '@/types/cliente.interface';
import { Head, router } from '@inertiajs/vue3';
import { Eye, Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';

interface Props {
  cliente: Cliente & {
    pedidos?: Array<{
      id: number;
      trabajo_realizar: string;
      fecha_ingreso: string;
      precio_total: number;
      estado_actual?: {
        id: number;
        nombre: string;
      };
    }>;
  };
}

const props = defineProps<Props>();

const showDeleteDialog = ref(false);

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Clientes',
    href: clienteRoutes.index().url,
  },
  {
    title: `${props.cliente.nombre} ${props.cliente.apellido}`,
    href: clienteRoutes.show({ id: props.cliente.id }).url,
  },
];

const pedidosActivos = computed(() => {
  if (!props.cliente.pedidos) return 0;
  return props.cliente.pedidos.filter(
    (p) => p.estado_actual && p.estado_actual.id !== 4 && p.estado_actual.id !== 5
  ).length;
});

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('es-AR', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  });
};

const formatMoney = (amount: number) => {
  return new Intl.NumberFormat('es-AR', {
    style: 'currency',
    currency: 'ARS',
  }).format(amount || 0);
};

const getEstadoVariant = (estado: any) => {
  if (!estado) return 'secondary';
  
  const variants: Record<number, any> = {
    1: 'default',      // Ingresado
    2: 'secondary',    // En reparación
    3: 'secondary',    // Esperando repuesto
    4: 'success',      // Finalizado
    5: 'outline',      // Entregado
  };
  
  return variants[estado.id] || 'secondary';
};

const handleEdit = () => {
  router.visit(clienteRoutes.edit({ id: props.cliente.id }).url);
};

const handleNewPedido = () => {
  // TODO: Implementar creación de pedido
  toast.info('Funcionalidad en desarrollo');
};

const handleViewPedido = (pedidoId: number) => {
  // TODO: Implementar vista de pedido
  toast.info(`Ver pedido #${pedidoId} (en desarrollo)`);
};

const confirmDelete = () => {
  showDeleteDialog.value = true;
};

const executeDelete = () => {
  router.delete(clienteRoutes.destroy({ id: props.cliente.id }).url, {
    preserveScroll: true,
    onSuccess: () => {
      toast.success('¡Cliente eliminado!', {
        description: `${props.cliente.nombre} ${props.cliente.apellido} ha sido eliminado exitosamente.`,
      });
    },
    onError: () => {
      toast.error('Error al eliminar', {
        description: 'No se pudo eliminar el cliente. Por favor, intenta nuevamente.',
      });
    },
  });
};
</script>