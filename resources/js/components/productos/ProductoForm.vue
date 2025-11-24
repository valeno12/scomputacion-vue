<template>
  <FormCard
    card-title="Información del Producto"
    card-description="Datos del producto y stock"
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
          :disabled="form.processing"
        />
      </FormField>

      <FormField id="marca" label="Marca" :error="form.errors.marca" required>
        <ProductoMarcaAutocomplete
          v-model="form.marca"
          :disabled="form.processing"
        />
      </FormField>

      <!-- ✅ CAMBIAR: Autocomplete en vez de Selector -->
      <FormField
        id="proveedor"
        label="Proveedor"
        :error="form.errors.proveedor"
        v-if="!isEdit"
        required
      >
        <ProveedorAutocomplete
          v-model="form.proveedor"
          :disabled="form.processing"
        />
      </FormField>

      <FormField
        id="precio"
        label="Precio"
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
import ProveedorAutocomplete from '@/components/proveedores/ProveedorAutocomplete.vue';
import { Input } from '@/components/ui/input';
import productoRoutes from '@/routes/producto';
import type { Producto } from '@/types/producto.interface';
import { router, useForm } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import ProductoMarcaAutocomplete from './ProductoMarcaAutocomplete.vue';
import ProductoNombreAutocomplete from './ProductoNombreAutocomplete.vue';

interface Props {
  producto?: Producto;
  isEdit?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  isEdit: false,
});

const form = useForm({
  nombre: props.producto?.nombre || '',
  marca: props.producto?.marca || '',
  proveedor: '',
  precio: props.producto?.precio || 0,
  cantidad_disponible: props.producto?.cantidad_disponible || 0,
});

const submit = () => {
  const isEdit = !!props.producto;
  const url = isEdit
    ? productoRoutes.update({ id: props.producto!.id }).url
    : productoRoutes.store().url;

  const options = {
    onSuccess: () => {
      toast.success(isEdit ? '¡Producto actualizado!' : '¡Producto creado!', {
        description: `${form.nombre} ha sido ${isEdit ? 'actualizado' : 'creado'} exitosamente.`,
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
  router.visit(productoRoutes.index().url);
};
</script>
