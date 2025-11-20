<template>
  <Head title="Gestión de proveedores" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div
      class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
    >
      <DataTable
        title="Gestión de Proveedores"
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
            Nuevo Proveedor
          </Button>
        </template>

        <!-- Columna de acciones personalizada -->
        <template #cell-acciones="{ item }: { item: Proveedor }">
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
            Se eliminará a
            <strong>{{ proveedorToDelete?.nombre }}</strong
            >. Esta acción no se puede deshacer.
          </AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel @click="cancelDelete">
            Cancelar
          </AlertDialogCancel>
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
import proveedor from '@/routes/proveedor';
import type { BreadcrumbItem } from '@/types';
import { LaravelPagination } from '@/types/pagination';
import { Proveedor } from '@/types/proveedor.interface';
import { Head, router } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';
import { toast } from 'vue-sonner';

interface Props {
  data: LaravelPagination<Proveedor>;
  filters: DataTableFilters;
}

const props = defineProps<Props>();

// Estado para el diálogo de confirmación
const showDeleteDialog = ref(false);
const proveedorToDelete = ref<Proveedor | null>(null);

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Proveedores',
    href: proveedor.index().url,
  },
];

// Usar el composable para manejar la tabla
const { search, sortBy, sortOrder, isLoading, goToPage } = useDataTable(
  proveedor.index().url,
  props.filters,
);

// Definición de columnas
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
    key: 'acciones',
    label: 'Acciones',
    sortable: false,
    headerClass: 'text-right',
    cellClass: 'text-right',
  },
];

// Funciones de navegación
const handleCreate = (): void => {
  router.visit(proveedor.create().url);
};

const handleEdit = (id: number): void => {
  router.visit(proveedor.edit({ id }).url);
};

// Confirmar eliminación
const confirmDelete = (proveedorData: Proveedor): void => {
  proveedorToDelete.value = proveedorData;
  showDeleteDialog.value = true;
};

// Ejecutar eliminación
const executeDelete = (): void => {
  if (!proveedorToDelete.value) return;

  const proveedorData = proveedorToDelete.value;

  router.delete(proveedor.destroy({ id: proveedorData.id }).url, {
    preserveScroll: true,
    onSuccess: () => {
      toast.success('Proveedor eliminado!', {
        description: `${proveedorData.nombre} ha sido eliminado exitosamente.`,
      });
      showDeleteDialog.value = false;
      proveedorToDelete.value = null;
    },
    onError: (errors) => {
      toast.error('Error al eliminar', {
        description:
          'No se pudo eliminar el proveedor. Por favor, intenta nuevamente.',
      });
      console.error(errors);
      showDeleteDialog.value = false;
    },
  });
};

// Cancelar eliminación
const cancelDelete = (): void => {
  showDeleteDialog.value = false;
  proveedorToDelete.value = null;
};
</script>
