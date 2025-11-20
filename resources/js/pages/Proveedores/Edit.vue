<template>
  <Head title="Crear Cliente" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
      <div class="mx-auto w-full max-w-2xl">
        <div class="mb-6">
          <h2 class="text-3xl font-bold tracking-tight">Editar proveedor</h2>
          <p class="text-muted-foreground">
            Modificar datos de {{ props.proveedor.nombre }}
          </p>
        </div>

        <ProveedorForm :proveedor="props.proveedor" />
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import ProveedorForm from '@/components/proveedores/ProveedorForm.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import cliente from '@/routes/cliente';
import proveedor from '@/routes/proveedor';
import type { BreadcrumbItem } from '@/types';
import { Proveedor } from '@/types/proveedor.interface';
import { Head } from '@inertiajs/vue3';

interface props {
  proveedor: Proveedor;
}

const props = defineProps<props>();

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Proveedores',
    href: cliente.index().url,
  },
  {
    title: 'Editar',
    href: proveedor.edit({ id: props.proveedor.id }).url,
  },
];
</script>
