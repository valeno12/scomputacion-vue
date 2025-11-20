<template>
  <FormCard
    card-title="Información"
    card-description="Datos básicos del proveedor"
    entity-name="Proveedor"
    :is-submitting="form.processing"
    :is-dirty="form.isDirty"
    @submit="submit"
    @cancel="handleCancel"
  >
    <FormField id="nombre" label="Nombre" :error="form.errors.nombre" required>
      <Input
        id="nombre"
        v-model="form.nombre"
        type="text"
        placeholder="Mercado Libre"
        :disabled="form.processing"
        :class="{ 'border-destructive': form.errors.nombre }"
      />
    </FormField>
  </FormCard>
</template>

<script setup lang="ts">
import FormCard from '@/components/common/FormCard.vue';
import FormField from '@/components/common/FormField.vue';
import { Input } from '@/components/ui/input';
import proveedorRoutes from '@/routes/proveedor';
import type { Proveedor } from '@/types/proveedor.interface';
import { router, useForm } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';

interface Props {
  proveedor?: Proveedor;
}

const props = defineProps<Props>();

const form = useForm({
  nombre: props.proveedor?.nombre || '',
});

const submit = () => {
  const isEdit = !!props.proveedor;
  const url = isEdit
    ? proveedorRoutes.update({ id: props.proveedor!.id }).url
    : proveedorRoutes.store().url;

  const options = {
    onSuccess: () => {
      toast.success(isEdit ? '¡Proveedor actualizado!' : '¡Proveedor creado!', {
        description: `${form.nombre} ha sido ${isEdit ? 'actualizado' : 'creado'} exitosamente.`,
      });
    },
    onError: () => {
      toast.error('Error en el formulario', {
        description: 'Por favor, revisa los campos marcados.',
      });
    },
  };

  if (isEdit) {
    form.put(url, options);
  } else {
    form.post(url, options);
  }
};

const handleCancel = () => {
  router.visit(proveedorRoutes.index().url);
};
</script>
