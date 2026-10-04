<script setup lang="ts">
import FormCard from '@/components/common/FormCard.vue';
import FormField from '@/components/common/FormField.vue';
import { Input } from '@/components/ui/input';
import productos from '@/routes/productos_nuevo';
import type { ProductoNuevo } from '@/types/producto-nuevo.interface';
import { router, useForm } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
const props = defineProps<{ producto?: ProductoNuevo }>();
const form = useForm({
  nombre: props.producto?.nombre ?? '',
  marca: props.producto?.marca ?? '',
});
function submit() {
  const options = {
    onSuccess: () =>
      toast.success(
        props.producto ? 'Producto actualizado' : 'Producto creado',
      ),
  };
  if (props.producto)
    form.put(productos.update({ producto: props.producto.id }).url, options);
  else form.post(productos.store().url, options);
}
</script>
<template>
  <FormCard
    card-title="Datos del producto"
    card-description="El costo, la cantidad y el porcentaje se definen al registrar un ingreso de stock."
    entity-name="Producto"
    :is-submitting="form.processing"
    :is-dirty="form.isDirty"
    @submit="submit"
    @cancel="router.visit(productos.index().url)"
  >
    <div class="grid gap-5 md:grid-cols-2">
      <FormField id="nombre" label="Nombre" :error="form.errors.nombre" required
        ><Input
          id="nombre"
          v-model="form.nombre"
          maxlength="200"
          :disabled="form.processing"
          required
      /></FormField>
      <FormField id="marca" label="Marca" :error="form.errors.marca" required
        ><Input
          id="marca"
          v-model="form.marca"
          maxlength="100"
          :disabled="form.processing"
          required
      /></FormField>
    </div>
  </FormCard>
</template>
