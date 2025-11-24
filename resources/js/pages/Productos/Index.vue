<template>
  <Head title="Gestión de Productos" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div
      class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
    >
      <DataTable
        title="Gestión de Productos"
        :columns="columns"
        :data="props.data"
        v-model:search="search"
        v-model:sort-by="sortBy"
        v-model:sort-order="sortOrder"
        :is-loading="isLoading"
        @change-page="goToPage"
      >
        <!-- Botón de crear en el header -->
        <template #actions>
          <Button @click="handleCreate">
            <Plus class="mr-2 h-4 w-4" />
            Nuevo Producto
          </Button>
        </template>

        <!-- Agregar este slot -->
        <template #cell-precio="{ item }: { item: Producto }">
          {{ formatMoney(item.precio) }}
        </template>
        <!-- Columna Precio Venta (precio * 1.3) -->
        <template #cell-precio_venta="{ item }: { item: Producto }">
          {{ formatMoney(item.precio * 1.3) }}
        </template>

        <!-- Columna Cantidad Disponible con pendientes -->
        <template #cell-cantidad_disponible="{ item }: { item: Producto }">
          {{ item.cantidad_disponible }}
          <span
            v-if="item.cantidad_pendientes && item.cantidad_pendientes > 0"
            class="text-xs text-muted-foreground"
          >
            ({{ item.cantidad_pendientes }} Pendientes)
          </span>
        </template>

        <!-- Columna de acciones personalizada -->
        <template #cell-acciones="{ item }: { item: Producto }">
          <div class="flex items-center justify-end gap-2">
            <Button
              variant="outline"
              size="sm"
              @click="handleEdit(item.id)"
              title="Editar"
            >
              <Pencil class="h-4 w-4" />
            </Button>
            <Button
              variant="destructive"
              size="sm"
              @click="confirmDelete(item)"
              title="Eliminar"
            >
              <Trash2 class="h-4 w-4" />
            </Button>
          </div>
        </template>
      </DataTable>
    </div>

    <!-- Diálogo de confirmación de eliminación -->
    <AlertDialog v-model:open="showDeleteDialog">
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>¿Estás seguro?</AlertDialogTitle>
          <AlertDialogDescription>
            Se eliminará el producto
            <strong
              >{{ productoToDelete?.nombre }}
              {{ productoToDelete?.marca }}</strong
            >. Esta acción no se puede deshacer.
          </AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel @click="cancelDelete">Cancelar</AlertDialogCancel>
          <AlertDialogAction
            @click="executeDelete"
            class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
          >
            Eliminar
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  </AppLayout>
</template>

<script setup lang="ts">
import type { TableColumn } from '@/components/DataTable.vue';
import DataTable from '@/components/DataTable.vue';
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import {
  useDataTable,
  type DataTableFilters,
} from '@/composables/useDataTable';
import AppLayout from '@/layouts/AppLayout.vue';
import producto from '@/routes/producto';
import type { BreadcrumbItem } from '@/types';
import type { LaravelPagination } from '@/types/pagination';
import type { Producto } from '@/types/producto.interface';
import { formatMoney } from '@/utils/formatter';
import { Head, router } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';
import { toast } from 'vue-sonner';

interface Props {
  data: LaravelPagination<Producto>;
  filters: DataTableFilters;
}

const props = defineProps<Props>();

const showDeleteDialog = ref(false);
const productoToDelete = ref<Producto | null>(null);

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Productos',
    href: producto.index().url,
  },
];

const { search, sortBy, sortOrder, isLoading, goToPage } = useDataTable(
  producto.index().url,
  props.filters,
);

const columns: TableColumn[] = [
  {
    key: 'id',
    label: 'ID',
    sortable: true,
  },
  {
    key: 'nombre',
    label: 'Nombre',
    sortable: true,
  },
  {
    key: 'marca',
    label: 'Marca',
    sortable: true,
  },
  {
    key: 'precio',
    label: 'Precio Proveedor',
    sortable: true,
  },
  {
    key: 'precio_venta', // ⚠️ No existe en DB, es calculado en el slot
    label: 'Precio Venta',
    sortable: false,
  },
  {
    key: 'cantidad_disponible',
    label: 'Cantidad Disponible',
    sortable: true,
  },
  {
    key: 'acciones',
    label: 'Acciones',
    sortable: false,
    headerClass: 'text-right',
    cellClass: 'text-right',
  },
];

const handleCreate = (): void => {
  router.visit(producto.create().url);
};

const handleEdit = (id: number): void => {
  router.visit(producto.edit({ id }).url);
};

const confirmDelete = (productoData: Producto): void => {
  productoToDelete.value = productoData;
  showDeleteDialog.value = true;
};

const executeDelete = (): void => {
  if (!productoToDelete.value) return;

  const productoData = productoToDelete.value;

  router.delete(producto.destroy({ id: productoData.id }).url, {
    preserveScroll: true,
    onSuccess: () => {
      toast.success('¡Producto eliminado!', {
        description: `${productoData.nombre} ha sido eliminado exitosamente.`,
      });
      showDeleteDialog.value = false;
      productoToDelete.value = null;
    },
    onError: (errors) => {
      toast.error('Error al eliminar', {
        description:
          'No se pudo eliminar el producto. Por favor, intenta nuevamente.',
      });
      console.error(errors);
      showDeleteDialog.value = false;
    },
  });
};

const cancelDelete = (): void => {
  showDeleteDialog.value = false;
  productoToDelete.value = null;
};
</script>
