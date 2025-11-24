<template>
  <Head title="Crear Producto" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
      <div class="mx-auto w-full max-w-4xl">
        <div class="mb-6">
          <h2 class="text-3xl font-bold tracking-tight">Nuevo Producto</h2>
          <p class="text-muted-foreground">
            Completa el formulario para agregar un nuevo producto
          </p>
        </div>

        <ProductoForm />
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import ProductoForm from '@/components/productos/ProductoForm.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import producto from '@/routes/producto';
import type { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Productos',
    href: producto.index().url,
  },
  {
    title: 'Nuevo',
    href: producto.create().url,
  },
];
</script>
