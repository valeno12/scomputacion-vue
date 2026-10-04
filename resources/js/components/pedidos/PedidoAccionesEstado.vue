<template>
  <div
    v-if="siguienteEstado"
    class="flex flex-col items-start gap-1.5 sm:items-end"
  >
    <Button
      type="button"
      size="lg"
      class="w-full bg-blue-600 text-white shadow-sm hover:bg-blue-700 sm:w-auto"
      :disabled="loading"
      @click="
        pedido.estadoActual_id === 1
          ? handleCambiarAEstado2()
          : confirmCambioEstado()
      "
      ><ArrowRight class="size-4" />{{
        etiquetaExplicita
          ? etiquetas[siguienteEstado.id] || 'Cambiar estado'
          : 'Cambiar estado'
      }}</Button
    >
    <span v-if="!etiquetaExplicita" class="text-xs text-muted-foreground"
      >Siguiente:
      <span class="font-medium text-foreground">{{
        siguienteEstado.nombre
      }}</span></span
    >
  </div>
  <div
    v-else
    class="inline-flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-medium text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-300"
  >
    <CheckCircle class="size-4" /> Pedido entregado
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
            Al aprobar el pedido se descontará el stock de los productos.
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
  <CobroDialog
    v-if="pedido.comercio_version === 2"
    v-model:open="cobrar"
    :endpoint="`/Pedido/${pedido.id}/actualizarEstado/5`"
    titulo="Entregar y cobrar pedido"
    descripcion="Incluye la reparación y las ventas que siguen pendientes. Los importes ya cobrados no se vuelven a sumar."
    confirmar="Confirmar entrega y cobro"
    :total="totalPendiente"
    :conceptos="conceptosCobro"
    :minimo="
      pendientes
        .map((op) => op.fecha)
        .sort()
        .at(-1)
    "
  />
</template>

<script setup lang="ts">
import CobroDialog from '@/components/comercio/CobroDialog.vue';
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
import type { Estado } from '@/types/estado.interface';
import type { Pedido } from '@/types/pedido.interface';
import { router } from '@inertiajs/vue3';
import { ArrowRight, CheckCircle } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';

interface Props {
  pedido: Pedido & {
    estado_actual: Estado;
  };
  siguienteEstado: Estado | null;
  etiquetaExplicita?: boolean;
}

const props = defineProps<Props>();
const etiquetas: Record<number, string> = {
  2: 'Cargar presupuesto',
  3: 'Aprobar presupuesto',
  4: 'Marcar finalizado',
  5: 'Entregar y cobrar',
};

const cobrar = ref(false);
const pendientes = computed(() =>
  (props.pedido.operaciones || []).filter(
    (op) => op.estado !== 'anulada' && !op.fecha_cobro,
  ),
);
const totalPendiente = computed(() =>
  pendientes.value.reduce((sum, op) => sum + op.total_centavos, 0),
);
const conceptosCobro = computed(() =>
  pendientes.value.map((op) => ({
    nombre:
      op.tipo === 'venta'
        ? 'Venta V' + op.id
        : 'Reparación (mano de obra y repuestos)',
    total: op.total_centavos,
  })),
);
const showConfirmDialog = ref(false);
const loading = ref(false);

const handleCambiarAEstado2 = () => {
  router.visit(pedidoRoutes.edit({ id: props.pedido.id }).url + '?step=2');
};

const confirmCambioEstado = () => {
  if (props.pedido.comercio_version === 2 && props.siguienteEstado?.id === 5)
    cobrar.value = true;
  else showConfirmDialog.value = true;
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
      method: 'post',
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
