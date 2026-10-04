<template>
  <Head title="Editar producto anterior" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
      <div class="mx-auto w-full max-w-4xl">
        <div class="mb-6">
          <h2 class="text-3xl font-bold tracking-tight">
            Editar producto anterior
          </h2>
          <p class="text-muted-foreground">
            Modificar datos de {{ props.producto.nombre }}
          </p>
        </div>

        <ProductoForm :producto="props.producto" />
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import ProductoForm from '@/components/productos_legacy/ProductoForm.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import producto from '@/routes/productos_legacy';
import type { BreadcrumbItem } from '@/types';
import { Producto } from '@/types/producto.interface';
import { Head } from '@inertiajs/vue3';

interface props {
  producto: Producto;
}

const props = defineProps<props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Productos', href: '/productos' },
  {
    title: 'Productos anteriores',
    href: producto.index().url,
  },
  {
    title: 'Editar',
    href: producto.edit({ id: props.producto.id }).url,
  },
];
</script>
