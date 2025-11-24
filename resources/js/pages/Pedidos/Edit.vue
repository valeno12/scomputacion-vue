<template>
  <Head title="Editar Pedido" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
      <div class="mx-auto w-full max-w-4xl">
        <div class="mb-6">
          <h2 class="text-3xl font-bold tracking-tight">
            Editar Pedido {{ pedido.codigo }}
          </h2>
          <p class="text-muted-foreground">
            {{
              currentStep === 'presupuesto'
                ? 'Agregar presupuesto al pedido'
                : 'Modificar datos del pedido'
            }}
          </p>
        </div>

        <Card>
          <CardHeader>
            <PedidoStepper :current-step-id="currentStep" />
          </CardHeader>

          <CardContent class="pt-6">
            <!-- Paso 1 -->
            <PedidoDatosInicialesForm
              v-if="currentStep === 'datos-iniciales'"
            />

            <!-- Paso 2 -->
            <PedidoPresupuestoForm v-if="currentStep === 'presupuesto'" />
          </CardContent>

          <CardFooter class="flex justify-between">
            <Button variant="outline" @click="handleCancel"> Cancelar </Button>

            <div class="flex gap-2">
              <Button
                v-if="currentStep === 'presupuesto'"
                variant="outline"
                @click="currentStep = 'datos-iniciales'"
              >
                Anterior
              </Button>

              <Button
                v-if="currentStep === 'datos-iniciales'"
                @click="handleSiguiente"
              >
                Siguiente
              </Button>

              <Button @click="handleSave" :disabled="form.processing">
                Guardar Cambios
              </Button>
            </div>
          </CardFooter>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import PedidoDatosInicialesForm from '@/components/pedidos/PedidoDatosInicialesForm.vue';
import PedidoPresupuestoForm from '@/components/pedidos/PedidoPresupuestoForm.vue';
import PedidoStepper from '@/components/pedidos/PedidoStepper.vue';
import { Button } from '@/components/ui/button';
import {
  Card,
  CardContent,
  CardFooter,
  CardHeader,
} from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import pedidoRoutes from '@/routes/pedido';
import type { BreadcrumbItem } from '@/types';
import type { Pedido } from '@/types/pedido.interface';
import { Head, router, useForm } from '@inertiajs/vue3';
import { provide, ref } from 'vue';
import { toast } from 'vue-sonner';

interface Props {
  pedido: Pedido;
  clienteActual: any;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Pedidos', href: pedidoRoutes.index().url },
  {
    title: props.pedido.codigo,
    href: pedidoRoutes.show({ id: props.pedido.id }).url,
  },
  { title: 'Editar', href: pedidoRoutes.edit({ id: props.pedido.id }).url },
];

// ✅ Detectar paso inicial desde query parameter
const urlParams = new URLSearchParams(window.location.search);
const pasoInicial =
  urlParams.get('step') === '2' ? 'presupuesto' : 'datos-iniciales';
const currentStep = ref<'datos-iniciales' | 'presupuesto'>(pasoInicial);
const fromIndex = urlParams.get('from') === 'index';

// Form pre-cargado con datos del pedido
const form = useForm({
  cliente_id: props.pedido.cliente_id,
  equipo: props.pedido.equipo,
  estado_ingreso: props.pedido.estado_ingreso,
  cargador: props.pedido.cargador,
  trabajo_realizar: props.pedido.trabajo_realizar || '',
  costo_mano_obra: props.pedido.costo_mano_obra || null,
  productos: [] as Array<{ id: number; cantidad: number }>,
  cambiar_estado: urlParams.get('step') === '2',
});

provide('pedidoForm', form);
provide('clienteInicial', props.clienteActual);
provide('productosIniciales', props.pedido.productos_seleccionados || []);

const handleCancel = () => {
  if (fromIndex) {
    router.visit(pedidoRoutes.index().url);
  } else {
    router.visit(pedidoRoutes.show({ id: props.pedido.id }).url);
  }
};
// Agregar función de validación
const validarPaso1 = (): boolean => {
  const errores: string[] = [];

  if (!form.cliente_id) errores.push('Debe seleccionar un cliente');
  if (!form.equipo) errores.push('Debe ingresar el equipo');
  if (!form.estado_ingreso) errores.push('Debe describir el problema');

  if (errores.length > 0) {
    toast.error('Completá los campos requeridos', {
      description: errores.join(', '),
    });
    return false;
  }

  return true;
};

// Actualizar el botón "Siguiente"
const handleSiguiente = () => {
  if (validarPaso1()) {
    currentStep.value = 'presupuesto';
  }
};

const handleSave = () => {
  // Validación según el paso actual
  if (currentStep.value === 'presupuesto') {
    if (!form.trabajo_realizar) {
      toast.error('Completá el trabajo a realizar');
      return;
    }

    if (!form.costo_mano_obra || form.costo_mano_obra <= 0) {
      toast.error('Ingresá el costo de mano de obra');
      return;
    }
  }

  form.put(pedidoRoutes.update({ id: props.pedido.id }).url, {
    onSuccess: () => {
      toast.success('¡Pedido actualizado!', {
        description: 'Los cambios se guardaron exitosamente.',
      });
    },
  });
};
</script>
