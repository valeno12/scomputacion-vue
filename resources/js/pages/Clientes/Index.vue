<template>
  <Head title="Gestión de Clientes" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div
      class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
    >
      <DataTable
        title="Gestión de Clientes"
        :columns="columns"
        :data="tableData || props.data"
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
            Nuevo Cliente
          </Button>
        </template>

        <!-- Columna de acciones personalizada -->
        <template #cell-acciones="{ item }: { item: Cliente }">
          <div class="flex items-center justify-end gap-2">
            <Button
              variant="outline"
              size="sm"
              @click="handleShow(item.id)"
              title="Ver detalles"
            >
              <Eye class="h-4 w-4" />
            </Button>

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

        <!-- Personalizar columna de email (truncar texto) -->
        <template #cell-mail="{ item }: { item: Cliente }">
          <div class="max-w-[280px] truncate" :title="item.mail">
            {{ item.mail }}
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
            <strong
              >{{ clienteToDelete?.nombre }}
              {{ clienteToDelete?.apellido }}</strong
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
import cliente from '@/routes/cliente';
import type { BreadcrumbItem } from '@/types';
import type { Cliente } from '@/types/cliente.interface';
import { LaravelPagination } from '@/types/pagination';
import { Head, router } from '@inertiajs/vue3';
import { Eye, Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';
import { toast } from 'vue-sonner';

interface Props {
  data: LaravelPagination<Cliente>;
  filters: DataTableFilters;
}

const props = defineProps<Props>();

// Estado para el diálogo de confirmación
const showDeleteDialog = ref(false);
const clienteToDelete = ref<Cliente | null>(null);

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Clientes',
    href: cliente.index().url,
  },
];

// Usar el composable para manejar la tabla
const {
  search,
  sortBy,
  sortOrder,
  isLoading,
  goToPage,
  data: tableData,
} = useDataTable<Cliente>(cliente.index().url, props.filters);

// Definición de columnas
const columns: TableColumn[] = [
  {
    key: 'dni',
    label: 'DNI',
    sortable: true,
  },
  {
    key: 'nombre',
    label: 'Nombre',
    sortable: true,
  },
  {
    key: 'apellido',
    label: 'Apellido',
    sortable: true,
  },
  {
    key: 'mail',
    label: 'E-Mail',
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
  router.visit(cliente.create().url);
};

const handleShow = (id: number): void => {
  router.visit(cliente.show({ id }).url);
};

const handleEdit = (id: number): void => {
  router.visit(cliente.edit({ id }).url);
};

// Confirmar eliminación
const confirmDelete = (clienteData: Cliente): void => {
  clienteToDelete.value = clienteData;
  showDeleteDialog.value = true;
};

// Ejecutar eliminación
const executeDelete = (): void => {
  if (!clienteToDelete.value) return;

  const clienteData = clienteToDelete.value;

  router.delete(cliente.destroy({ id: clienteData.id }).url, {
    preserveScroll: true,
    onSuccess: () => {
      toast.success('¡Cliente eliminado!', {
        description: `${clienteData.nombre} ${clienteData.apellido} ha sido eliminado exitosamente.`,
      });
      showDeleteDialog.value = false;
      clienteToDelete.value = null;
    },
    onError: (errors) => {
      toast.error('Error al eliminar', {
        description:
          'No se pudo eliminar el cliente. Por favor, intenta nuevamente.',
      });
      console.error(errors);
      showDeleteDialog.value = false;
    },
  });
};

// Cancelar eliminación
const cancelDelete = (): void => {
  showDeleteDialog.value = false;
  clienteToDelete.value = null;
};
</script>
