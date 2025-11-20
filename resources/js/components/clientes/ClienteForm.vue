<template>
  <FormCard
    card-title="Información Personal"
    card-description="Datos básicos del cliente"
    entity-name="Cliente"
    :is-submitting="form.processing"
    :is-dirty="form.isDirty"
    @submit="submit"
    @cancel="handleCancel"
  >
    <ClienteFormFields :form="form" />
  </FormCard>
</template>

<script setup lang="ts">
import FormCard from '@/components/common/FormCard.vue';
import clienteRoutes from '@/routes/cliente';
import type { Cliente } from '@/types/cliente.interface';
import { router, useForm } from '@inertiajs/vue3';
import { provide } from 'vue';
import { toast } from 'vue-sonner';
import ClienteFormFields from './ClienteFormFields.vue';

interface Props {
  cliente?: Cliente;
}

const props = defineProps<Props>();
// Inertia useForm - maneja todo automáticamente
const form = useForm({
  dni: props.cliente?.dni || '',
  nombre: props.cliente?.nombre || '',
  apellido: props.cliente?.apellido || '',
  direccion: props.cliente?.direccion || '',
  telefono: props.cliente?.telefono || '',
  mail: props.cliente?.mail || '',
});

provide('clienteForm', form);
const submit = () => {
  const isEdit = !!props.cliente;
  const url = isEdit
    ? clienteRoutes.update({ id: props.cliente!.id }).url
    : clienteRoutes.store().url;

  const options = {
    onSuccess: () => {
      toast.success(isEdit ? '¡Cliente actualizado!' : '¡Cliente creado!', {
        description: `${form.nombre} ${form.apellido} ha sido ${isEdit ? 'actualizado' : 'creado'} exitosamente.`,
      });
    },
    onError: () => {
      toast.error('Error en el formulario', {
        description: 'Por favor, revisa los campos marcados en rojo.',
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
  router.visit(clienteRoutes.index().url);
};
</script>
