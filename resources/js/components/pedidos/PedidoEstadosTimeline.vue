<template>
  <div
    class="overflow-hidden rounded-xl border bg-white shadow-sm dark:bg-gray-950"
  >
    <div
      class="border-b bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 dark:from-blue-950/30 dark:to-indigo-950/30"
    >
      <div class="flex items-center gap-3">
        <div
          class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/50"
        >
          <History class="h-5 w-5 text-blue-600 dark:text-blue-400" />
        </div>
        <div>
          <h3 class="font-semibold">Historial de Estados</h3>
          <p class="text-xs text-muted-foreground">
            {{ estados.length }} cambios registrados
          </p>
        </div>
      </div>
    </div>

    <div class="space-y-4 p-6">
      <div
        v-for="(estado, index) in estados"
        :key="estado.id"
        class="relative flex items-start gap-3"
      >
        <!-- Línea vertical -->
        <div
          v-if="index < estados.length - 1"
          class="absolute top-8 left-4 h-full w-0.5 bg-gradient-to-b from-blue-300 to-transparent dark:from-blue-700"
        />

        <!-- Círculo indicador con gradiente -->
        <div
          :class="[
            'relative z-10 flex h-8 w-8 items-center justify-center rounded-full border-2 transition-all',
            index === estados.length - 1
              ? 'border-blue-500 bg-gradient-to-br from-blue-500 to-purple-500 shadow-lg'
              : 'border-gray-300 bg-white dark:border-gray-700 dark:bg-gray-900',
          ]"
        >
          <component
            :is="getEstadoIcon(estado.estado.id)"
            :class="[
              'h-4 w-4',
              index === estados.length - 1
                ? 'text-white'
                : 'text-muted-foreground',
            ]"
          />
        </div>

        <!-- Contenido -->
        <div class="flex-1 pb-4">
          <div class="flex flex-wrap items-start justify-between gap-2">
            <div class="flex-1">
              <p
                :class="[
                  'font-medium',
                  index === estados.length - 1
                    ? 'text-blue-600 dark:text-blue-400'
                    : '',
                ]"
              >
                {{ estado.estado.nombre }}
              </p>
              <p class="text-xs text-muted-foreground">
                {{ formatDate(estado.created_at) }}
              </p>
            </div>

            <!-- Botón eliminar -->
            <Button
              v-if="
                editable && index === estados.length - 1 && estados.length > 1
              "
              variant="ghost"
              :size="accionesClaras ? 'sm' : 'icon'"
              class="min-h-8 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950/30"
              @click="confirmDelete(estado)"
              :title="
                accionesClaras
                  ? 'Volver al estado anterior'
                  : 'Eliminar último estado'
              "
            >
              <Undo2 v-if="accionesClaras" class="h-4 w-4" /><Trash2
                v-else
                class="h-4 w-4"
              /><span v-if="accionesClaras">Volver al estado anterior</span>
            </Button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Diálogo (sin cambios) -->
  <AlertDialog v-model:open="showDeleteDialog">
    <AlertDialogContent>
      <AlertDialogHeader>
        <AlertDialogTitle>{{
          accionesClaras ? 'Volver al estado anterior' : '¿Estás seguro?'
        }}</AlertDialogTitle>
        <AlertDialogDescription>
          <template v-if="accionesClaras">
            El pedido pasará de
            <strong>{{ estadoToDelete?.estado.nombre }}</strong> a
            <strong>{{ estados[estados.length - 2]?.estado.nombre }}</strong
            >.
            <span v-if="estadoToDelete?.estado.id === 5" class="mt-2 block"
              >Los cobros registrados se conservan. Cambiar el estado del
              trabajo no revierte un pago.</span
            >
            <span v-else-if="estadoToDelete?.estado.id === 3" class="mt-2 block"
              >Los productos de las ventas pendientes vuelven al stock. Las
              ventas ya cobradas se conservan.</span
            >
          </template>
          <template v-else
            >Se eliminará el estado
            <strong>{{ estadoToDelete?.estado.nombre }}</strong
            >. Esta acción no se puede deshacer y el pedido volverá al estado
            anterior.</template
          >
        </AlertDialogDescription>
      </AlertDialogHeader>
      <AlertDialogFooter>
        <AlertDialogCancel>Cancelar</AlertDialogCancel>
        <AlertDialogAction
          @click="executeDelete"
          class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
        >
          {{ accionesClaras ? 'Confirmar cambio' : 'Eliminar' }}
        </AlertDialogAction>
      </AlertDialogFooter>
    </AlertDialogContent>
  </AlertDialog>
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
import { Button } from '@/components/ui/button';
import pedidoRoutes from '@/routes/pedido';
import { formatDate } from '@/utils/formatter';
import { router } from '@inertiajs/vue3';
import {
  CheckCircle,
  Clock,
  History,
  Package,
  Search,
  Trash2,
  Undo2,
  Zap,
} from 'lucide-vue-next';
import { ref, type Component } from 'vue';
import { toast } from 'vue-sonner';

interface Estado {
  id: number;
  nombre: string;
}

interface PedidoEstado {
  id: number;
  pedido_id: number;
  estado_id: number;
  created_at: string;
  estado: Estado;
}

interface Props {
  estados: PedidoEstado[];
  pedidoId: number;
  editable?: boolean;
  accionesClaras?: boolean;
}

const props = defineProps<Props>();

const showDeleteDialog = ref(false);
const estadoToDelete = ref<PedidoEstado | null>(null);

const getEstadoIcon = (estadoId: number): Component => {
  const icons: Record<number, Component> = {
    1: Search,
    2: Clock,
    3: Zap,
    4: CheckCircle,
    5: Package,
  };
  return icons[estadoId] || Clock;
};

const confirmDelete = (estado: PedidoEstado) => {
  estadoToDelete.value = estado;
  showDeleteDialog.value = true;
};

const executeDelete = () => {
  if (!estadoToDelete.value) return;

  router.delete(
    pedidoRoutes.eliminarestado({
      pedido_id: props.pedidoId,
      estado_id: estadoToDelete.value.id,
    }).url,
    {
      preserveScroll: true,
      onSuccess: () => {
        toast.success('¡Estado eliminado!', {
          description: 'El estado ha sido eliminado exitosamente.',
        });
        showDeleteDialog.value = false;
        estadoToDelete.value = null;
      },
      onError: () => {
        toast.error('Error al eliminar', {
          description: 'No se pudo eliminar el estado.',
        });
        showDeleteDialog.value = false;
      },
    },
  );
};
</script>
