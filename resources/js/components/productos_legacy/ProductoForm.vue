<template>
  <FormCard
    card-title="Información del producto anterior"
    card-description="Estos datos pertenecen al catálogo de los pedidos anteriores."
    entity-name="Producto"
    :is-submitting="form.processing"
    :is-dirty="form.isDirty"
    @submit="submit"
    @cancel="handleCancel"
  >
    <div class="space-y-6">
      <FormField
        id="nombre"
        label="Nombre"
        :error="form.errors.nombre"
        required
      >
        <ProductoNombreAutocomplete
          v-model="form.nombre"
          input-id="nombre"
          :disabled="form.processing"
        />
      </FormField>

      <FormField id="marca" label="Marca" :error="form.errors.marca" required>
        <ProductoMarcaAutocomplete
          v-model="form.marca"
          input-id="marca"
          :disabled="form.processing"
        />
      </FormField>

      <FormField
        id="precio"
        label="Costo unitario"
        :error="form.errors.precio"
        required
      >
        <div class="relative">
          <span
            class="absolute top-1/2 left-3 -translate-y-1/2 text-muted-foreground"
          >
            $
          </span>
          <Input
            id="precio"
            v-model.number="form.precio"
            type="number"
            step="0.01"
            placeholder="50000"
            class="pl-7"
            :disabled="form.processing"
          />
        </div>
      </FormField>

      <FormField
        id="cantidad_disponible"
        label="Cantidad disponible"
        :error="form.errors.cantidad_disponible"
        required
      >
        <Input
          id="cantidad_disponible"
          v-model.number="form.cantidad_disponible"
          type="number"
          placeholder="0"
          :disabled="form.processing"
        />
      </FormField>
    </div>
  </FormCard>
</template>

<script setup lang="ts">
import FormCard from '@/components/common/FormCard.vue';
import FormField from '@/components/common/FormField.vue';
import ProductoMarcaAutocomplete from '@/components/productos/ProductoMarcaAutocomplete.vue';
import ProductoNombreAutocomplete from '@/components/productos/ProductoNombreAutocomplete.vue';
import { Input } from '@/components/ui/input';
import productoRoutes from '@/routes/productos_legacy';
import type { Producto } from '@/types/producto.interface';
import { router, useForm } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';

interface Props {
  producto: Producto;
}

const props = defineProps<Props>();

const form = useForm({
  nombre: props.producto?.nombre || '',
  marca: props.producto?.marca || '',
  precio: props.producto?.precio || 0,
  cantidad_disponible: props.producto?.cantidad_disponible || 0,
});

const submit = () => {
  const url = productoRoutes.update({ id: props.producto.id }).url;

  const options = {
    onSuccess: () => {
      toast.success('Producto anterior actualizado');
    },
    onError: () => {
      toast.error('Error en el formulario', {
        description: 'Por favor, revisa los campos marcados en rojo.',
      });
    },
  };

  form.put(url, options);
};

const handleCancel = () => {
  router.visit(productoRoutes.index().url);
};
</script>
