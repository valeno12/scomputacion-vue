<template>
  <Head :title="`Editar ${props.cliente.nombre} ${props.cliente.apellido}`" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
      <div class="mx-auto w-full max-w-2xl">
        <div class="mb-6">
          <h2 class="text-3xl font-bold tracking-tight">Editar cliente</h2>
          <p class="text-muted-foreground">
            Modificar datos de {{ props.cliente.nombre }}
            {{ props.cliente.apellido }}
          </p>
        </div>

        <ClienteForm :cliente="props.cliente" />
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import ClienteForm from '@/components/clientes/ClienteForm.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import clienteRoutes from '@/routes/cliente';
import type { BreadcrumbItem } from '@/types';
import type { Cliente } from '@/types/cliente.interface';
import { Head } from '@inertiajs/vue3';

interface Props {
  cliente: Cliente;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Clientes',
    href: clienteRoutes.index().url,
  },
  {
    title: `${props.cliente.nombre} ${props.cliente.apellido}`,
    href: clienteRoutes.edit({ id: props.cliente.id }).url,
  },
];
</script>
