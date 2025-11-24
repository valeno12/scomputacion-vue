<template>
  <div
    class="overflow-hidden rounded-xl border bg-gradient-to-br from-purple-50 to-pink-50 shadow-sm dark:from-purple-950/20 dark:to-pink-950/20"
  >
    <div class="border-b bg-white/50 px-6 py-4 dark:bg-gray-950/50">
      <div class="flex items-center gap-3">
        <div
          class="flex h-10 w-10 items-center justify-center rounded-lg bg-purple-100 dark:bg-purple-900/50"
        >
          <Zap class="h-5 w-5 text-purple-600 dark:text-purple-400" />
        </div>
        <div>
          <h3 class="font-semibold">Acciones Rápidas</h3>
          <p class="text-xs text-muted-foreground">Cambiar estado del pedido</p>
        </div>
      </div>
    </div>

    <div class="space-y-3 p-6">
      <!-- Badge con estado actual -->
      <div
        class="flex items-center justify-between rounded-lg bg-white/60 p-3 dark:bg-gray-900/60"
      >
        <span class="text-sm font-medium">Estado Actual</span>
        <Badge variant="default">
          {{ pedido.estado_actual?.nombre || 'Sin estado' }}
        </Badge>
      </div>

      <!-- Botones de acción -->
      <div>
        <!-- Caso especial: Estado 1 → Ir a Edit con step=2 -->
        <Button
          v-if="pedido.estadoActual_id === 1 && siguienteEstado"
          @click="handleCambiarAEstado2"
          class="w-full bg-gradient-to-r from-purple-500 to-pink-500 hover:from-purple-600 hover:to-pink-600"
          :disabled="loading"
        >
          <ArrowRight class="mr-2 h-4 w-4" />
          Cambiar a {{ siguienteEstado.nombre }}
        </Button>

        <!-- Casos normales: Avanzar al siguiente estado -->
        <Button
          v-else-if="siguienteEstado"
          @click="confirmCambioEstado"
          class="w-full bg-gradient-to-r from-blue-500 to-purple-500 hover:from-blue-600 hover:to-purple-600"
          :disabled="loading"
        >
          <ArrowRight class="mr-2 h-4 w-4" />
          Cambiar a {{ siguienteEstado.nombre }}
        </Button>

        <!-- Estado final (5 = Entregado) -->
        <div
          v-else
          class="rounded-lg border border-dashed border-green-300 bg-green-50 p-4 text-center dark:border-green-800 dark:bg-green-950/20"
        >
          <CheckCircle
            class="mx-auto mb-2 h-8 w-8 text-green-600 dark:text-green-400"
          />
          <p class="text-sm font-medium text-green-900 dark:text-green-100">
            Pedido Completado
          </p>
          <p class="text-xs text-green-700 dark:text-green-300">
            Este pedido está en su estado final
          </p>
        </div>
      </div>
    </div>
  </div>

  <!-- Diálogo de confirmación -->
  <AlertDialog v-model:open="showConfirmDialog">
    <AlertDialogContent>
      <AlertDialogHeader>
        <AlertDialogTitle>Confirmar cambio de estado</AlertDialogTitle>
        <AlertDialogDescription>
          El pedido pasará de
          <strong>{{ pedido.estado_actual?.nombre }}</strong>
          a
          <strong>{{ siguienteEstado?.nombre }}</strong
          >.
          <span
            v-if="siguienteEstado?.id === 3"
            class="mt-2 block text-destructive"
          >
            ⚠️ Al aprobar el pedido se descontará el stock de los productos.
          </span>
          <span v-if="siguienteEstado?.id === 5" class="mt-2 block">
            Se registrará la fecha de pago automáticamente.
          </span>
        </AlertDialogDescription>
      </AlertDialogHeader>
      <AlertDialogFooter>
        <AlertDialogCancel>Cancelar</AlertDialogCancel>
        <AlertDialogAction @click="executeCambioEstado">
          Confirmar
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
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import pedidoRoutes from '@/routes/pedido';
import type { Estado } from '@/types/estado.interface';
import type { Pedido } from '@/types/pedido.interface';
import { router } from '@inertiajs/vue3';
import { ArrowRight, CheckCircle, Zap } from 'lucide-vue-next';
import { ref } from 'vue';
import { toast } from 'vue-sonner';

interface Props {
  pedido: Pedido & {
    estado_actual: Estado;
  };
  siguienteEstado: Estado | null;
}

const props = defineProps<Props>();

const showConfirmDialog = ref(false);
const loading = ref(false);

const handleCambiarAEstado2 = () => {
  router.visit(pedidoRoutes.edit({ id: props.pedido.id }).url + '?step=2');
};

const confirmCambioEstado = () => {
  showConfirmDialog.value = true;
};

const executeCambioEstado = () => {
  if (!props.siguienteEstado) return;

  loading.value = true;

  router.visit(
    pedidoRoutes.actualizarestado({
      pedido_id: props.pedido.id,
      estado_id: props.siguienteEstado.id,
    }).url,
    {
      method: 'get',
      preserveScroll: true,
      onSuccess: () => {
        toast.success('¡Estado actualizado!', {
          description: `El pedido ahora está en estado ${props.siguienteEstado?.nombre}.`,
        });
        showConfirmDialog.value = false;
        loading.value = false;
      },
      onError: () => {
        toast.error('Error al cambiar estado', {
          description: 'No se pudo actualizar el estado del pedido.',
        });
        loading.value = false;
      },
    },
  );
};
</script>
