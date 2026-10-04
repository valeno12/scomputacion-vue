<script setup lang="ts">
import ProductoForm from '@/components/productos/ProductoForm.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import FormLayout from '@/layouts/FormLayout.vue';
import productos from '@/routes/productos_nuevo';
import { Head } from '@inertiajs/vue3';
</script>
<template>
  <Head title="Nuevo producto" />
  <AppLayout
    :breadcrumbs="[
      { title: 'Productos', href: productos.index().url },
      { title: 'Nuevo producto', href: productos.create().url },
    ]"
  >
    <FormLayout
      title="Nuevo producto"
      description="Agregá el nombre y la marca al catálogo. El stock se carga desde Nuevo ingreso."
      ><ProductoForm
    /></FormLayout>
  </AppLayout>
</template>
