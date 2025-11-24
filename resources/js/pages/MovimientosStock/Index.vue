<template>
  <Head title="Movimientos de Stock" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div
      class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
    >
      <!-- Tabs -->
      <Tabs
        :default-value="props.filters.tipo || 'entradas'"
        @update:model-value="handleTabChange"
      >
        <TabsList class="grid w-full max-w-md grid-cols-2">
          <TabsTrigger value="entradas">Entradas</TabsTrigger>
          <TabsTrigger value="salidas">Salidas</TabsTrigger>
        </TabsList>

        <TabsContent :value="props.filters.tipo || 'entradas'" class="mt-4">
          <DataTable
            :title="getTitle()"
            :columns="columns"
            :data="props.data"
            v-model:search="search"
            v-model:sort-by="sortBy"
            v-model:sort-order="sortOrder"
            :is-loading="isLoading"
            @change-page="goToPage"
          >
            <!-- Columna producto -->
            <template #cell-producto="{ item }: { item: MovimientoStock }">
              <div v-if="item.producto" class="flex items-center gap-2">
                <div
                  class="flex h-8 w-8 items-center justify-center rounded bg-gray-100 dark:bg-gray-800"
                >
                  <Package class="h-4 w-4 text-gray-500 dark:text-gray-400" />
                </div>
                <div>
                  <p class="font-medium">{{ item.producto.nombre }}</p>
                  <p
                    v-if="item.producto.marca"
                    class="text-xs text-muted-foreground"
                  >
                    {{ item.producto.marca }}
                  </p>
                </div>
              </div>
              <div v-else class="text-muted-foreground">Sin producto</div>
            </template>

            <!-- Columna cantidad -->
            <template #cell-cantidad="{ item }: { item: MovimientoStock }">
              <span
                class="rounded-full bg-gray-100 px-2 py-1 text-xs font-medium dark:bg-gray-800"
              >
                {{ item.cantidad }}
              </span>
            </template>

            <!-- Columna fecha -->
            <template #cell-fecha="{ item }: { item: MovimientoStock }">
              <div class="text-sm">
                {{ formatDate(item.fecha) }}
              </div>
            </template>

            <!-- Columna precio unitario (solo entradas) -->
            <template
              #cell-precio_unitario="{ item }: { item: MovimientoStock }"
            >
              <span class="font-medium">{{
                formatMoney(item.precio || 0)
              }}</span>
            </template>

            <!-- Columna precio total (solo entradas) -->
            <template #cell-precio_total="{ item }: { item: MovimientoStock }">
              <span class="font-semibold text-red-600 dark:text-red-400">
                {{ formatMoney((item.precio || 0) * item.cantidad) }}
              </span>
            </template>

            <!-- Columna proveedor (solo entradas) -->
            <template #cell-proveedor="{ item }: { item: MovimientoStock }">
              <div v-if="item.proveedor">
                {{ item.proveedor.nombre }}
              </div>
              <div v-else class="text-muted-foreground">Sin proveedor</div>
            </template>

            <!-- Columna pedido (solo salidas) -->
            <template #cell-pedido="{ item }: { item: MovimientoStock }">
              <div v-if="item.pedido">
                <span
                  class="font-mono font-semibold text-purple-600 dark:text-purple-400"
                >
                  {{ item.pedido.codigo }}
                </span>
              </div>
              <div v-else class="text-muted-foreground">N/A</div>
            </template>

            <!-- Columna de acciones -->
            <template #cell-acciones="{ item }: { item: MovimientoStock }">
              <div class="flex items-center justify-end gap-2">
                <!-- Para entradas: botón editar -->
                <Button
                  v-if="isEntradas"
                  variant="outline"
                  size="sm"
                  @click="handleEdit(item.id)"
                  title="Editar"
                >
                  <Pencil class="h-4 w-4" />
                </Button>

                <!-- Para salidas: botón ver pedido -->
                <Button
                  v-else
                  variant="outline"
                  size="sm"
                  @click="handleViewPedido(item.pedido?.id)"
                  :disabled="!item.pedido"
                  title="Ver pedido"
                >
                  <Eye class="h-4 w-4" />
                </Button>
              </div>
            </template>
          </DataTable>
        </TabsContent>
      </Tabs>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import type { TableColumn } from '@/components/DataTable.vue';
import DataTable from '@/components/DataTable.vue';
import { Button } from '@/components/ui/button';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import {
  useDataTable,
  type DataTableFilters,
} from '@/composables/useDataTable';
import AppLayout from '@/layouts/AppLayout.vue';
import movimientosStock from '@/routes/movimientos-stock';
import pedido from '@/routes/pedido';
import type { BreadcrumbItem } from '@/types';
import type { MovimientoStock } from '@/types/movimiento-stock.interface';
import type { LaravelPagination } from '@/types/pagination';
import { formatDate, formatMoney } from '@/utils/formatter';
import { Head, router } from '@inertiajs/vue3';
import { Eye, Package, Pencil } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
  data: LaravelPagination<MovimientoStock>;
  filters: DataTableFilters & {
    tipo?: string;
  };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Movimientos de Stock',
    href: movimientosStock.index().url,
  },
];

const { search, sortBy, sortOrder, isLoading, goToPage } = useDataTable(
  movimientosStock.index().url,
  props.filters,
);

const isEntradas = computed(() => {
  return (props.filters.tipo || 'entradas') === 'entradas';
});

// Definición de columnas según el tipo
const columns = computed<TableColumn[]>(() => {
  if (isEntradas.value) {
    // Columnas para ENTRADAS
    return [
      { key: 'producto', label: 'Producto', sortable: false },
      { key: 'cantidad', label: 'Cantidad', sortable: true },
      { key: 'fecha', label: 'Fecha', sortable: true },
      { key: 'precio_unitario', label: 'Precio Unitario', sortable: false },
      { key: 'precio_total', label: 'Precio Total', sortable: false },
      { key: 'proveedor', label: 'Proveedor', sortable: false },
      {
        key: 'acciones',
        label: 'Acciones',
        sortable: false,
        headerClass: 'text-right',
        cellClass: 'text-right',
      },
    ];
  } else {
    // Columnas para SALIDAS
    return [
      { key: 'producto', label: 'Producto', sortable: false },
      { key: 'pedido', label: 'Pedido', sortable: false },
      { key: 'cantidad', label: 'Cantidad', sortable: true },
      { key: 'fecha', label: 'Fecha', sortable: true },
      {
        key: 'acciones',
        label: 'Acciones',
        sortable: false,
        headerClass: 'text-right',
        cellClass: 'text-right',
      },
    ];
  }
});

const getTitle = () => {
  return isEntradas.value
    ? 'Historial de Entradas de Stock'
    : 'Historial de Salidas de Stock';
};

const handleTabChange = (newTab: string) => {
  router.get(
    movimientosStock.index().url,
    { tipo: newTab },
    {
      preserveState: false,
      preserveScroll: true,
      replace: true,
    },
  );
};

const handleEdit = (id: number): void => {
  router.visit(movimientosStock.edit({ id }).url);
};

const handleViewPedido = (pedidoId: number | undefined): void => {
  if (pedidoId) {
    router.visit(pedido.show({ id: pedidoId }).url);
  }
};
</script>
