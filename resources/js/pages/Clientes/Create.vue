<template>
  <Head title="Crear Cliente" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
      <div class="mx-auto w-full max-w-2xl">
        <div class="mb-6">
          <h2 class="text-3xl font-bold tracking-tight">Nuevo Cliente</h2>
          <p class="text-muted-foreground">
            Completa el formulario para agregar un nuevo cliente
          </p>
        </div>

        <ClienteForm />
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import ClienteForm from '@/components/clientes/ClienteForm.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import cliente from '@/routes/cliente';
import type { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Clientes',
    href: cliente.index().url,
  },
  {
    title: 'Nuevo',
    href: cliente.create().url,
  },
];
</script>
