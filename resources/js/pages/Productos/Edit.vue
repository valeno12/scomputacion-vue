<script setup lang="ts">
import ProductoForm from '@/components/productos/ProductoForm.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import FormLayout from '@/layouts/FormLayout.vue';
import productos from '@/routes/productos_nuevo';
import type { ProductoNuevo } from '@/types/producto-nuevo.interface';
import { Head } from '@inertiajs/vue3';
defineProps<{ producto: ProductoNuevo }>();
</script>
<template>
  <Head title="Editar producto" />
  <AppLayout
    :breadcrumbs="[
      { title: 'Productos', href: productos.index().url },
      { title: 'Editar', href: productos.edit({ producto: producto.id }).url },
    ]"
  >
    <FormLayout
      title="Editar producto"
      :description="`Modificar datos de ${producto.nombre}`"
      ><ProductoForm :producto="producto"
    /></FormLayout>
  </AppLayout>
</template>
