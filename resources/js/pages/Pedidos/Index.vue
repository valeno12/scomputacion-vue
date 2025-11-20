<template>
  <Head title="Gestión de Pedidos" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div
      class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
    >
      <!-- Tabs -->
      <Tabs
        :default-value="props.filters.estado || 'activos'"
        @update:model-value="handleTabChange"
      >
        <TabsList class="grid w-full max-w-md grid-cols-3">
          <TabsTrigger value="activos">Activos</TabsTrigger>
          <TabsTrigger value="finalizados">Finalizados</TabsTrigger>
          <TabsTrigger value="entregados">Entregados</TabsTrigger>
        </TabsList>

        <TabsContent :value="props.filters.estado || 'activos'" class="mt-4">
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
            <!-- Botón de crear en el header -->
            <template #actions>
              <Button @click="handleCreate">
                <Plus class="mr-2 h-4 w-4" />
                Nuevo Pedido
              </Button>
            </template>

            <!-- Columna cliente -->
            <template #cell-cliente="{ item }: { item: Pedido }">
              <div v-if="item.cliente">
                {{ item.cliente.nombre }} {{ item.cliente.apellido }}
              </div>
              <div v-else class="text-muted-foreground">Sin cliente</div>
            </template>

            <!-- Columna DNI -->
            <template #cell-dni="{ item }: { item: Pedido }">
              <div v-if="item.cliente">
                {{ item.cliente.dni }}
              </div>
              <div v-else class="text-muted-foreground">-</div>
            </template>

            <!-- Columna estado -->
            <template #cell-estado="{ item }: { item: Pedido }">
              <Badge
                v-if="item.estado_actual"
                :variant="getEstadoVariant(item.estado_actual.id)"
              >
                {{ item.estado_actual.nombre }}
              </Badge>
              <span v-else class="text-muted-foreground">Sin estado</span>
            </template>

            <!-- Slot para fecha finalizado -->
            <template #cell-fecha_finalizado="{ item }: { item: Pedido }">
              <div v-if="item.estado_finalizado?.created_at">
                {{ formatDate(item.estado_finalizado.created_at) }}
              </div>
              <div v-else class="text-muted-foreground">—</div>
            </template>

            <!-- Slot para fecha entregado -->
            <template #cell-fecha_entregado="{ item }: { item: Pedido }">
              <div v-if="item.estado_entregado?.created_at">
                {{ formatDate(item.estado_entregado.created_at) }}
              </div>
              <div v-else class="text-muted-foreground">—</div>
            </template>

            <!-- Columna de acciones personalizada -->
            <template #cell-acciones="{ item }: { item: Pedido }">
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
          </DataTable>
        </TabsContent>
      </Tabs>
    </div>

    <!-- Diálogo de confirmación de eliminación -->
    <AlertDialog v-model:open="showDeleteDialog">
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>¿Estás seguro?</AlertDialogTitle>
          <AlertDialogDescription>
            Se eliminará el pedido
            <strong>{{ pedidoToDelete?.codigo }}</strong
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
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import {
  useDataTable,
  type DataTableFilters,
} from '@/composables/useDataTable';
import AppLayout from '@/layouts/AppLayout.vue';
import pedidoRoutes from '@/routes/pedido';
import type { BreadcrumbItem } from '@/types';
import type { LaravelPagination } from '@/types/pagination';
import type { Pedido } from '@/types/pedido.interface';
import { formatDate } from '@/utils/formatter';
import { Head, router } from '@inertiajs/vue3';
import { Eye, Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';

interface Props {
  data: LaravelPagination<Pedido>;
  filters: DataTableFilters & {
    estado?: string;
  };
}

const props = defineProps<Props>();

// Estado para el diálogo de confirmación
const showDeleteDialog = ref(false);
const pedidoToDelete = ref<Pedido | null>(null);

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Pedidos',
    href: pedidoRoutes.index().url,
  },
];

// Usar el composable para manejar la tabla
const { search, sortBy, sortOrder, isLoading, goToPage } = useDataTable(
  pedidoRoutes.index().url,
  props.filters,
);

// Definición de columnas
const columns = computed<TableColumn[]>(() => {
  const estado = props.filters.estado || 'activos';

  const baseColumns: TableColumn[] = [
    { key: 'codigo', label: 'Código', sortable: true },
    { key: 'cliente', label: 'Cliente', sortable: false },
    { key: 'dni', label: 'DNI', sortable: false },
    { key: 'equipo', label: 'Equipo', sortable: true },
  ];

  // Columna diferente según el estado
  if (estado === 'finalizados') {
    baseColumns.push({
      key: 'fecha_finalizado',
      label: 'Fecha Finalización',
      sortable: false,
    });
  } else if (estado === 'entregados') {
    baseColumns.push({
      key: 'fecha_entregado',
      label: 'Fecha Entrega',
      sortable: false,
    });
  } else {
    // Activos
    baseColumns.push({
      key: 'estado',
      label: 'Estado',
      sortable: false,
    });
  }

  // Acciones siempre al final
  baseColumns.push({
    key: 'acciones',
    label: 'Acciones',
    sortable: false,
    headerClass: 'text-right',
    cellClass: 'text-right',
  });

  return baseColumns;
});

// Título dinámico según el tab
const getTitle = () => {
  const estado = props.filters.estado || 'activos';
  const titles: Record<string, string> = {
    activos: 'Pedidos Activos',
    finalizados: 'Pedidos Finalizados',
    entregados: 'Pedidos Entregados',
  };
  return titles[estado] || 'Pedidos';
};

// Variante de badge según estado
const getEstadoVariant = (estadoId: number) => {
  const variants: Record<number, any> = {
    1: 'default', // Ingresado
    2: 'secondary', // En reparación
    3: 'secondary', // Esperando repuesto
    4: 'outline', // Finalizado
    5: 'default', // Entregado
  };
  return variants[estadoId] || 'secondary';
};

// Cambio de tab
const handleTabChange = (newTab: string) => {
  router.get(
    pedidoRoutes.index().url,
    { estado: newTab },
    {
      preserveState: false,
      preserveScroll: true,
      replace: true,
    },
  );
};

// Funciones de navegación
const handleCreate = (): void => {
  router.visit(pedidoRoutes.create().url);
};

const handleShow = (id: number): void => {
  router.visit(pedidoRoutes.showI({ id }).url);
};

const handleEdit = (id: number): void => {
  router.visit(pedidoRoutes.editI({ id }).url);
};

// Confirmar eliminación
const confirmDelete = (pedidoData: Pedido): void => {
  pedidoToDelete.value = pedidoData;
  showDeleteDialog.value = true;
};

// Ejecutar eliminación
const executeDelete = (): void => {
  if (!pedidoToDelete.value) return;

  const pedidoData = pedidoToDelete.value;

  router.delete(pedidoRoutes.destroy({ id: pedidoData.id }).url, {
    preserveScroll: true,
    onSuccess: () => {
      toast.success('¡Pedido eliminado!', {
        description: `${pedidoData.codigo} ha sido eliminado exitosamente.`,
      });
      showDeleteDialog.value = false;
      pedidoToDelete.value = null;
    },
    onError: (errors) => {
      toast.error('Error al eliminar', {
        description:
          'No se pudo eliminar el pedido. Por favor, intenta nuevamente.',
      });
      console.error(errors);
      showDeleteDialog.value = false;
    },
  });
};

// Cancelar eliminación
const cancelDelete = (): void => {
  showDeleteDialog.value = false;
  pedidoToDelete.value = null;
};
</script>
