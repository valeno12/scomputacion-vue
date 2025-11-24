<template>
  <Head title="Editar Entrada de Stock" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
      <div class="mx-auto w-full max-w-4xl">
        <div class="mb-6">
          <h2 class="text-3xl font-bold tracking-tight">
            Editar Entrada de Stock
          </h2>
          <p class="text-muted-foreground">
            Modificar cantidad y precio de
            {{ props.movimiento.producto?.nombre }}
            {{ props.movimiento.producto?.marca }}
          </p>
        </div>

        <MovimientoStockForm :movimiento="props.movimiento" />
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import MovimientoStockForm from '@/components/movimiento-stock/MovimientoStockForm.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import movimientosStock from '@/routes/movimientos-stock';
import type { BreadcrumbItem } from '@/types';
import type { MovimientoStock } from '@/types/movimiento-stock.interface';
import { Head } from '@inertiajs/vue3';

interface Props {
  movimiento: MovimientoStock;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Movimientos de Stock',
    href: movimientosStock.index().url + '?tipo=entradas',
  },
  {
    title: `${props.movimiento.producto?.nombre || 'Editar'}`,
    href: movimientosStock.edit({ id: props.movimiento.id }).url,
  },
];
</script>
``` ## Estructura de archivos: ``` components/ └── movimientos-stock/ ├──
MovimientoStockForm.vue (usa FormCard) └── MovimientoStockFormFields.vue (campos
del formulario) Pages/ └── MovimientosStock/ ├── Index.vue (tabs con DataTable)
└── Edit.vue (usa MovimientoStockForm)
