<template>
  <FormCard
    card-title="Información de Entrada"
    card-description="Edita la cantidad y precio de la entrada de stock"
    entity-name="Entrada"
    :is-submitting="form.processing"
    :is-dirty="form.isDirty"
    @submit="submit"
    @cancel="handleCancel"
  >
    <MovimientoStockFormFields :movimiento="movimiento" />
  </FormCard>
</template>

<script setup lang="ts">
import FormCard from '@/components/common/FormCard.vue';
import movimientosStock from '@/routes/movimientos-stock';
import type { MovimientoStock } from '@/types/movimiento-stock.interface';
import { router, useForm } from '@inertiajs/vue3';
import { provide } from 'vue';
import { toast } from 'vue-sonner';
import MovimientoStockFormFields from './MovimientoStockFormFields.vue';

interface Props {
  movimiento: MovimientoStock;
}

const props = defineProps<Props>();

const form = useForm({
  cantidad: props.movimiento.cantidad,
  precio: props.movimiento.precio || 0,
});

provide('movimientoStockForm', form);

const submit = () => {
  form.put(movimientosStock.update({ id: props.movimiento.id }).url, {
    preserveScroll: true,
    onSuccess: () => {
      toast.success('¡Entrada actualizada!', {
        description: 'Los cambios se han guardado correctamente.',
      });
    },
    onError: () => {
      toast.error('Error en el formulario', {
        description: 'Por favor, revisa los campos marcados en rojo.',
      });
    },
  });
};

const handleCancel = () => {
  router.visit(movimientosStock.index().url + '?tipo=entradas');
};
</script>
