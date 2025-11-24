<template>
  <Head title="Crear Cliente" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
      <div class="mx-auto w-full max-w-4xl">
        <div class="mb-6">
          <h2 class="text-3xl font-bold tracking-tight">Editar proveedor</h2>
          <p class="text-muted-foreground">
            Modificar datos de {{ props.producto.nombre }}
          </p>
        </div>

        <ProductoForm :is-edit="true" :producto="props.producto" />
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import ProductoForm from '@/components/productos/ProductoForm.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import producto from '@/routes/producto';
import type { BreadcrumbItem } from '@/types';
import { Producto } from '@/types/producto.interface';
import { Head } from '@inertiajs/vue3';

interface props {
  producto: Producto;
}

const props = defineProps<props>();

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Productos',
    href: producto.index().url,
  },
  {
    title: 'Editar',
    href: producto.edit({ id: props.producto.id }).url,
  },
];
</script>
