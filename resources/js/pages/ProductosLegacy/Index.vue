<script setup lang="ts">
import DataTable, { type TableColumn } from '@/components/DataTable.vue';
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
import productosLegacy from '@/routes/productos_legacy';
import type { LaravelPagination } from '@/types/pagination';
import type { Producto } from '@/types/producto.interface';
import { formatMoney } from '@/utils/formatter';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, History, Pencil, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';
import { toast } from 'vue-sonner';

const props = defineProps<{
  data: LaravelPagination<Producto>;
  filters: DataTableFilters;
}>();
const { search, sortBy, sortOrder, isLoading, goToPage } = useDataTable(
  productosLegacy.index().url,
  props.filters,
);
const selected = ref<Producto | null>(null);
const showDelete = ref(false);
const deleting = ref(false);
const columns: TableColumn[] = [
  { key: 'id', label: 'ID', sortable: true },
  { key: 'nombre', label: 'Nombre', sortable: true },
  { key: 'marca', label: 'Marca', sortable: true },
  { key: 'precio', label: 'Costo unitario', sortable: true, sensitive: true },
  {
    key: 'cantidad_disponible',
    label: 'Cantidad disponible',
    sortable: true,
    sensitive: true,
  },
  {
    key: 'acciones',
    label: 'Acciones',
    headerClass: 'text-right',
    cellClass: 'text-right',
  },
];
function confirmDelete(product: Producto) {
  selected.value = product;
  showDelete.value = true;
}
function removeProduct() {
  if (!selected.value || deleting.value) return;
  deleting.value = true;
  router.delete(productosLegacy.destroy({ id: selected.value.id }).url, {
    preserveScroll: true,
    onSuccess: () => toast.success('Producto anterior eliminado'),
    onError: (errors) =>
      toast.error(errors.producto || 'No se pudo eliminar el producto'),
    onFinish: () => {
      deleting.value = false;
      showDelete.value = false;
    },
  });
}
</script>

<template>
  <Head title="Productos anteriores" />
  <AppLayout
    :breadcrumbs="[
      { title: 'Productos', href: '/productos' },
      { title: 'Productos anteriores', href: productosLegacy.index().url },
    ]"
  >
    <div class="flex flex-1 flex-col gap-4 p-4">
      <div
        class="flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50 p-4 text-amber-950 dark:border-amber-900 dark:bg-amber-950/25 dark:text-amber-100"
      >
        <History class="mt-0.5 size-5 shrink-0" />
        <div class="space-y-1">
          <p class="text-sm font-medium">Catálogo anterior</p>
          <p class="text-sm text-amber-800 dark:text-amber-200/80">
            Productos utilizados por los pedidos anteriores. Podés corregir sus
            datos y consultar sus movimientos. Los productos nuevos se cargan en
            Productos.
          </p>
        </div>
      </div>
      <DataTable
        title="Productos anteriores"
        :columns="columns"
        :data="data"
        v-model:search="search"
        v-model:sort-by="sortBy"
        v-model:sort-order="sortOrder"
        :is-loading="isLoading"
        @change-page="goToPage"
      >
        <template #actions>
          <div class="flex flex-wrap gap-2">
            <Button variant="outline" as-child
              ><Link href="/movimientos-stock"
                ><History class="size-4" /> Movimientos de stock</Link
              ></Button
            >
            <Button variant="outline" as-child
              ><Link href="/productos"
                ><ArrowLeft class="size-4" /> Volver a Productos</Link
              ></Button
            >
          </div>
        </template>
        <template #cell-precio="{ item }: { item: Producto }">{{
          formatMoney(item.precio)
        }}</template>
        <template #cell-cantidad_disponible="{ item }: { item: Producto }">
          {{ item.cantidad_disponible }}
          <span
            v-if="item.cantidad_pendientes && item.cantidad_pendientes > 0"
            class="ml-1 text-xs text-muted-foreground"
            >({{ item.cantidad_pendientes }} en pedidos pendientes)</span
          >
        </template>
        <template #cell-acciones="{ item }: { item: Producto }">
          <div class="flex items-center justify-end gap-2">
            <Button
              variant="outline"
              size="icon"
              :aria-label="`Editar ${item.nombre}`"
              title="Editar producto anterior"
              as-child
              ><Link :href="productosLegacy.edit({ id: item.id })"
                ><Pencil class="size-4" /></Link
            ></Button>
            <Button
              variant="destructive"
              size="icon"
              :disabled="deleting"
              :aria-label="`Eliminar ${item.nombre}`"
              title="Eliminar producto anterior"
              @click="confirmDelete(item)"
              ><Trash2 class="size-4"
            /></Button>
          </div>
        </template>
      </DataTable>
    </div>
  </AppLayout>
  <AlertDialog v-model:open="showDelete">
    <AlertDialogContent>
      <AlertDialogHeader>
        <AlertDialogTitle>Eliminar producto anterior</AlertDialogTitle>
        <AlertDialogDescription
          ><strong>{{ selected?.nombre }}</strong> se eliminará junto con sus
          movimientos.</AlertDialogDescription
        >
      </AlertDialogHeader>
      <AlertDialogFooter>
        <AlertDialogCancel :disabled="deleting">Cancelar</AlertDialogCancel>
        <AlertDialogAction
          :disabled="deleting"
          class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
          @click="removeProduct"
          >Eliminar</AlertDialogAction
        >
      </AlertDialogFooter>
    </AlertDialogContent>
  </AlertDialog>
</template>
