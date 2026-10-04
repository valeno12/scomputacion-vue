<script setup lang="ts">
import DataTable, { type TableColumn } from '@/components/DataTable.vue';
import AumentarPreciosDialog from '@/components/productos/AumentarPreciosDialog.vue';
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
import { Checkbox } from '@/components/ui/checkbox';
import {
  useDataTable,
  type DataTableFilters,
} from '@/composables/useDataTable';
import AppLayout from '@/layouts/AppLayout.vue';
import productos from '@/routes/productos_nuevo';
import type { LaravelPagination } from '@/types/pagination';
import type { ProductoNuevo } from '@/types/producto-nuevo.interface';
import { formatMoney } from '@/utils/formatter';
import { Head, Link, router } from '@inertiajs/vue3';
import {
  History,
  PackagePlus,
  Pencil,
  Plus,
  Trash2,
  TrendingUp,
  X,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';
const props = defineProps<{
  data: LaravelPagination<ProductoNuevo>;
  filters: DataTableFilters;
}>();
const { search, sortBy, sortOrder, isLoading, goToPage } = useDataTable(
  productos.index().url,
  props.filters,
  { preserveState: true },
);
const columns: TableColumn[] = [
  {
    key: 'seleccion',
    label: 'Seleccionar',
    headerClass: 'w-12',
    cellClass: 'w-12',
  },
  { key: 'id', label: 'ID', sortable: true },
  { key: 'nombre', label: 'Nombre', sortable: true },
  { key: 'marca', label: 'Marca', sortable: true },
  {
    key: 'costo_unitario_centavos',
    label: 'Último costo',
    sortable: true,
    sensitive: true,
  },
  {
    key: 'precio_venta_centavos',
    label: 'Precio de venta',
    sortable: true,
    sensitive: true,
  },
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
const seleccion = ref<number[]>([]);
const showPrecios = ref(false);
const preparando = ref(false);
const paginaSeleccionada = computed(() => {
  const count = props.data.data.filter((p) =>
    seleccion.value.includes(p.id),
  ).length;
  return count === 0
    ? false
    : count === props.data.data.length
      ? true
      : 'indeterminate';
});
const fueraDePagina = computed(
  () =>
    seleccion.value.filter((id) => !props.data.data.some((p) => p.id === id))
      .length,
);
function quitarSeleccion(id: number) {
  seleccion.value = seleccion.value.filter((selectedId) => selectedId !== id);
}
function seleccionar(id: number) {
  if (seleccion.value.includes(id)) quitarSeleccion(id);
  else if (seleccion.value.length < 100) seleccion.value.push(id);
  else toast.error('Podés seleccionar hasta 100 productos por operación.');
}
function seleccionarPagina() {
  const ids = props.data.data.map((p) => p.id);
  if (paginaSeleccionada.value === true)
    seleccion.value = seleccion.value.filter((id) => !ids.includes(id));
  else {
    const nuevos = [...new Set([...seleccion.value, ...ids])];
    if (nuevos.length > 100)
      toast.error('Podés seleccionar hasta 100 productos por operación.');
    else seleccion.value = nuevos;
  }
}
function prepararIngreso() {
  preparando.value = true;
  router.get(
    productos.ingresos.create().url,
    { productos: seleccion.value },
    {
      onError: () =>
        toast.error(
          'Un producto seleccionado ya no está disponible. Actualizá la selección.',
        ),
      onFinish: () => {
        preparando.value = false;
      },
    },
  );
}
const selected = ref<ProductoNuevo | null>(null);
const showDelete = ref(false);
const deleting = ref(false);
function confirmDelete(producto: ProductoNuevo) {
  selected.value = producto;
  showDelete.value = true;
}
function remove() {
  if (!selected.value || deleting.value) return;
  deleting.value = true;
  router.delete(productos.destroy({ producto: selected.value.id }).url, {
    preserveScroll: true,
    onSuccess: () => {
      if (selected.value) quitarSeleccion(selected.value.id);
      toast.success('Producto eliminado');
    },
    onError: () => toast.error('No se pudo eliminar el producto'),
    onFinish: () => {
      deleting.value = false;
      showDelete.value = false;
    },
  });
}
</script>
<template>
  <Head title="Gestión de Productos" />
  <AppLayout
    :breadcrumbs="[{ title: 'Productos', href: productos.index().url }]"
  >
    <div
      class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
    >
      <DataTable
        title="Gestión de Productos"
        :columns="columns"
        :data="data"
        v-model:search="search"
        v-model:sort-by="sortBy"
        v-model:sort-order="sortOrder"
        :is-loading="isLoading"
        @change-page="goToPage"
      >
        <template #actions>
          <Button variant="outline" as-child
            ><Link href="/productos_legacy"
              ><History class="size-4" /> Ver productos anteriores</Link
            ></Button
          >
          <Button variant="outline" as-child
            ><Link :href="productos.ingresos.create()"
              ><PackagePlus class="size-4" /> Nuevo ingreso</Link
            ></Button
          >
          <Button as-child
            ><Link :href="productos.create()"
              ><Plus class="size-4" /> Nuevo producto</Link
            ></Button
          >
        </template>
        <template #header-seleccion>
          <Checkbox
            :model-value="paginaSeleccionada"
            :disabled="isLoading || !data.data.length"
            aria-label="Seleccionar productos de esta página"
            @update:model-value="seleccionarPagina"
          />
        </template>
        <template #cell-seleccion="{ item }: { item: ProductoNuevo }">
          <Checkbox
            :model-value="seleccion.includes(item.id)"
            :aria-label="`Seleccionar ${item.nombre} ${item.marca || ''}`"
            @update:model-value="seleccionar(item.id)"
          />
        </template>
        <template #cell-nombre="{ item }: { item: ProductoNuevo }"
          ><Link
            class="font-medium text-blue-600 hover:underline dark:text-blue-400"
            :href="`/productos/${item.id}`"
            >{{ item.nombre }}</Link
          ></template
        >
        <template #toolbar>
          <div
            v-if="seleccion.length"
            class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 dark:border-blue-500/25 dark:bg-blue-500/10"
          >
            <div class="text-sm" role="status">
              <p class="font-semibold text-blue-800 dark:text-blue-200">
                {{ seleccion.length }}
                {{
                  seleccion.length === 1
                    ? 'producto seleccionado'
                    : 'productos seleccionados'
                }}
              </p>
              <p v-if="fueraDePagina" class="text-xs text-muted-foreground">
                {{ fueraDePagina }} fuera de esta página. La selección se
                conserva al buscar.
              </p>
            </div>
            <div class="flex flex-wrap gap-2">
              <Button
                variant="outline"
                :disabled="isLoading || preparando"
                @click="prepararIngreso"
                ><PackagePlus class="size-4" /> Preparar ingreso</Button
              >
              <Button
                :disabled="isLoading || preparando"
                @click="showPrecios = true"
                ><TrendingUp class="size-4" /> Aumentar precios</Button
              >
              <Button
                variant="ghost"
                :disabled="preparando"
                @click="seleccion = []"
                ><X class="size-4" /> Limpiar selección</Button
              >
            </div>
          </div>
        </template>
        <template
          #cell-costo_unitario_centavos="{ item }: { item: ProductoNuevo }"
          ><span
            :title="
              item.costo_unitario_centavos == null
                ? 'Todavía no tiene ingresos'
                : 'Costo unitario del último ingreso'
            "
            >{{
              item.costo_unitario_centavos == null
                ? '—'
                : formatMoney(item.costo_unitario_centavos / 100)
            }}</span
          ></template
        >
        <template
          #cell-precio_venta_centavos="{ item }: { item: ProductoNuevo }"
          ><span
            v-if="!item.tiene_ingresos && item.precio_venta_centavos === 0"
            class="text-muted-foreground"
            >Sin ingresos</span
          ><template v-else>{{
            formatMoney(item.precio_venta_centavos / 100)
          }}</template></template
        >
        <template #cell-acciones="{ item }: { item: ProductoNuevo }">
          <div class="flex items-center justify-end gap-2">
            <Button
              variant="outline"
              size="sm"
              title="Ingresar stock"
              :aria-label="`Ingresar stock de ${item.nombre}`"
              as-child
              ><Link :href="productos.ingreso({ producto: item.id })"
                ><PackagePlus class="size-4" /></Link
            ></Button>
            <Button
              variant="outline"
              size="sm"
              title="Editar"
              :aria-label="`Editar ${item.nombre}`"
              as-child
              ><Link :href="productos.edit({ producto: item.id })"
                ><Pencil class="size-4" /></Link
            ></Button>
            <Button
              variant="destructive"
              size="sm"
              title="Eliminar"
              :aria-label="`Eliminar ${item.nombre}`"
              :disabled="deleting"
              @click="confirmDelete(item)"
              ><Trash2 class="size-4"
            /></Button>
          </div>
        </template>
      </DataTable>
    </div>
  </AppLayout>
  <AumentarPreciosDialog
    v-model:open="showPrecios"
    :ids="seleccion"
    @quitar="quitarSeleccion"
    @actualizado="seleccion = []"
  />
  <AlertDialog v-model:open="showDelete"
    ><AlertDialogContent
      ><AlertDialogHeader
        ><AlertDialogTitle>Eliminar producto</AlertDialogTitle
        ><AlertDialogDescription
          >Se eliminará <strong>{{ selected?.nombre }}</strong> del catálogo.
          Las operaciones registradas conservan sus
          datos.</AlertDialogDescription
        ></AlertDialogHeader
      ><AlertDialogFooter
        ><AlertDialogCancel :disabled="deleting">Cancelar</AlertDialogCancel
        ><AlertDialogAction
          :disabled="deleting"
          class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
          @click="remove"
          >Eliminar</AlertDialogAction
        ></AlertDialogFooter
      ></AlertDialogContent
    ></AlertDialog
  >
</template>
