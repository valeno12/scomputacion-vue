<template>
  <Head title="Crear Cliente" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
      <div class="mx-auto w-full max-w-4xl">
        <div class="mb-6">
          <h2 class="text-3xl font-bold tracking-tight">Nuevo Proveedor</h2>
          <p class="text-muted-foreground">
            Completa el formulario para agregar un nuevo proveedor
          </p>
        </div>

        <ProveedorForm />
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import ProveedorForm from '@/components/proveedores/ProveedorForm.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import proveedor from '@/routes/proveedor';
import type { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Proveedores',
    href: proveedor.index().url,
  },
  {
    title: 'Nuevo',
    href: proveedor.create().url,
  },
];
</script>
