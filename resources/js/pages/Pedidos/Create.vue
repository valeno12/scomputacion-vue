<template>
  <Head title="Crear Pedido" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
      <div class="mx-auto w-full max-w-4xl">
        <div class="mb-6">
          <h2 class="text-3xl font-bold tracking-tight">Crear Pedido</h2>
          <p class="text-muted-foreground">
            Complete los datos del nuevo pedido
          </p>
        </div>

        <Card>
          <CardHeader>
            <PedidoStepper :current-step-id="currentStep.id" />
          </CardHeader>

          <CardContent class="pt-6">
            <PedidoDatosInicialesForm
              v-if="currentStep.id === 'datos-iniciales'"
            />
            <PedidoPresupuestoForm v-if="currentStep.id === 'presupuesto'" />
          </CardContent>

          <CardFooter class="flex justify-between">
            <Button variant="outline" @click="handleCancel"> Cancelar </Button>

            <div class="flex gap-2">
              <Button
                v-if="!isFirstStep"
                variant="outline"
                @click="stepper.prev"
              >
                Anterior
              </Button>

              <Button
                v-if="currentStep.id === 'datos-iniciales'"
                @click="handleSaveIngresado"
                :disabled="form.processing"
              >
                Guardar como Ingresado
              </Button>

              <Button
                v-if="currentStep.id === 'datos-iniciales'"
                @click="handleContinuarPresupuesto"
                :disabled="form.processing"
              >
                Continuar con Presupuesto
              </Button>

              <Button
                v-if="currentStep.id === 'presupuesto'"
                @click="handleSaveConPresupuesto"
                :disabled="form.processing"
              >
                Guardar con Presupuesto
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
import { Head, router, useForm } from '@inertiajs/vue3';
import { defineStepper } from '@stepperize/vue';
import { computed, provide } from 'vue';
import { toast } from 'vue-sonner';

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Pedidos', href: pedidoRoutes.index().url },
  { title: 'Crear', href: pedidoRoutes.create().url },
];

const { useStepper } = defineStepper(
  { id: 'datos-iniciales', title: 'Datos Iniciales' },
  { id: 'presupuesto', title: 'Presupuesto' },
);

const stepper = useStepper();
const currentStep = computed(() => stepper.value.current);
const isFirstStep = computed(() => stepper.value.isFirst);

const form = useForm({
  cliente_id: null as number | null,
  equipo: '',
  estado_ingreso: '',
  cargador: false,
  trabajo_realizar: '',
  costo_mano_obra: null as number | null,
  productos: [] as Array<{ id: number; cantidad: number }>,
});

provide('pedidoForm', form);

const handleCancel = () => {
  router.visit(pedidoRoutes.index().url);
};

// Validar Paso 1 antes de continuar
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

// Avanzar al Paso 2 con validación
const handleContinuarPresupuesto = () => {
  if (validarPaso1()) {
    stepper.value.next();
  }
};

// Guardar solo Paso 1 (Estado = 1)
const handleSaveIngresado = () => {
  form.post(pedidoRoutes.store().url, {
    onSuccess: () => {
      toast.success('¡Pedido creado!', {
        description: 'El pedido ha sido ingresado exitosamente.',
      });
    },
  });
};

// Guardar completo (Paso 1 + Paso 2)
const handleSaveConPresupuesto = () => {
  // Validar campos del Paso 2
  if (!form.trabajo_realizar) {
    toast.error('Completá el trabajo a realizar');
    return;
  }

  if (!form.costo_mano_obra || form.costo_mano_obra <= 0) {
    toast.error('Ingresá el costo de mano de obra');
    return;
  }

  // Una sola request con TODOS los datos
  form.post(pedidoRoutes.store().url, {
    onSuccess: () => {
      toast.success('¡Pedido creado con presupuesto!', {
        description: 'El pedido ha sido creado exitosamente.',
      });
    },
  });
};
</script>
