<template>
  <Head title="Editar Pedido" />

  <AppLayout :breadcrumbs="breadcrumbs" sticky-actions>
    <div class="flex h-full min-w-0 flex-1 flex-col gap-4 p-4 md:p-6">
      <div class="mx-auto w-full max-w-4xl">
        <div
          class="sticky top-0 z-20 mb-5 flex flex-wrap items-center justify-between gap-3 rounded-xl border bg-background/95 p-3 shadow-sm backdrop-blur-md"
          data-testid="editar-pedido-acciones"
        >
          <Button
            variant="ghost"
            @click="handleCancel"
            :disabled="form.processing"
            ><ArrowLeft class="size-4" />{{
              fromIndex ? 'Volver a pedidos' : 'Volver al pedido'
            }}</Button
          >
          <Button @click="handleSave" :disabled="form.processing">{{
            form.processing ? 'Guardando…' : 'Guardar cambios'
          }}</Button>
        </div>
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
            <div class="flex gap-2" aria-label="Secciones del pedido">
              <Button
                :variant="
                  currentStep === 'datos-iniciales' ? 'default' : 'outline'
                "
                @click="currentStep = 'datos-iniciales'"
                >Datos del pedido</Button
              >
              <Button
                :variant="currentStep === 'presupuesto' ? 'default' : 'outline'"
                @click="handleSiguiente"
                >Presupuesto</Button
              >
            </div>
          </CardHeader>

          <CardContent class="pt-6">
            <div
              v-if="Object.keys(form.errors).length"
              role="alert"
              class="mb-4 rounded border border-destructive p-3 text-destructive"
            >
              <p v-for="(error, key) in form.errors" :key="key">{{ error }}</p>
            </div>
            <!-- Paso 1 -->
            <PedidoDatosInicialesForm
              v-if="currentStep === 'datos-iniciales'"
            />

            <!-- Paso 2 -->
            <PedidoPresupuestoForm v-if="currentStep === 'presupuesto'" />
          </CardContent>

          <CardFooter class="flex flex-wrap justify-between gap-3">
            <Button variant="outline" @click="handleCancel">Cancelar</Button>

            <div
              class="ml-auto flex flex-wrap justify-end gap-1.5 sm:gap-2 [&>button]:px-2.5 sm:[&>button]:px-4"
            >
              <Button
                v-if="currentStep === 'presupuesto'"
                variant="outline"
                @click="currentStep = 'datos-iniciales'"
              >
                Datos del pedido
              </Button>

              <Button
                v-if="currentStep === 'datos-iniciales'"
                @click="handleSiguiente"
              >
                Ir al presupuesto
              </Button>

              <Button @click="handleSave" :disabled="form.processing">
                Guardar cambios
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
import { Button } from '@/components/ui/button';
import {
  Card,
  CardContent,
  CardFooter,
  CardHeader,
} from '@/components/ui/card';
import { usePedidoNavigation } from '@/composables/usePedidoNavigation';
import AppLayout from '@/layouts/AppLayout.vue';
import pedidoRoutes from '@/routes/pedido';
import type { BreadcrumbItem } from '@/types';
import {
  copiarReparto,
  itemsFormulario,
  type OpcionesComercio,
} from '@/types/comercio';
import type { Pedido } from '@/types/pedido.interface';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import { provide, ref } from 'vue';
import { toast } from 'vue-sonner';

interface Props extends OpcionesComercio {
  pedido: Pedido;
  clienteActual: any;
}

const props = defineProps<Props>();
const { listadoUrl, volverAlListado } = usePedidoNavigation(
  props.pedido.id,
  props.pedido.estadoActual_id,
);
provide('opcionesComercio', props);
const operacion = props.pedido.operaciones?.find(
  (o) => o.tipo === 'reparacion',
);

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Pedidos', href: listadoUrl() },
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
  cargador: !!props.pedido.cargador,
  trabajo_realizar: props.pedido.trabajo_realizar || '',
  costo_mano_obra: props.pedido.costo_mano_obra ?? null,
  items: itemsFormulario([
    ...(operacion?.items ?? []),
    ...(props.pedido.operaciones
      ?.filter((o) => o.es_presupuesto && o.estado !== 'anulada')
      .flatMap((o) => o.items) ?? []),
  ]),
  reparto_mano_obra: copiarReparto(
    operacion?.items.find((i) => i.tipo === 'mano_obra')?.reparto ??
      props.repartoManoObra,
  ),
  cambiar_estado: urlParams.get('step') === '2',
});

provide('pedidoForm', form);
provide('clienteInicial', props.clienteActual);
provide('productosIniciales', props.pedido.productos_seleccionados || []);

const handleCancel = () => {
  if (fromIndex) {
    volverAlListado();
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

    if (form.costo_mano_obra === null || Number(form.costo_mano_obra) < 0) {
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
