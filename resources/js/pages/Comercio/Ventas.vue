<script setup lang="ts">
import DataTable, { type TableColumn } from '@/components/DataTable.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  useDataTable,
  type DataTableFilters,
} from '@/composables/useDataTable';
import AppLayout from '@/layouts/AppLayout.vue';
import { moneda, type Operacion } from '@/types/comercio';
import type { LaravelPagination } from '@/types/pagination';
import { Head, Link, router } from '@inertiajs/vue3';
import { Eye, Plus } from 'lucide-vue-next';
import { ref } from 'vue';
const props = defineProps<{
  data: LaravelPagination<Operacion>;
  filters: DataTableFilters & { cobro?: string };
}>();
const { search, sortBy, sortOrder, isLoading, goToPage } = useDataTable(
  '/comercio/ventas',
  props.filters,
);
const estado = ref(props.filters.cobro || 'todos');
const desde = ref(props.filters.desde || '');
const hasta = ref(props.filters.hasta || '');
function filter() {
  router.get(
    '/comercio/ventas',
    {
      search: search.value,
      cobro: estado.value === 'todos' ? '' : estado.value,
      desde: desde.value,
      hasta: hasta.value,
      sort_by: sortBy.value,
      sort_order: sortOrder.value,
    },
    { preserveState: false },
  );
}
const columns: TableColumn[] = [
  { key: 'id', label: 'Venta', sortable: true },
  { key: 'fecha', label: 'Fecha', sortable: true },
  { key: 'cliente', label: 'Cliente / pedido' },
  { key: 'estado', label: 'Estado' },
  { key: 'total_centavos', label: 'Total', sortable: true, sensitive: true },
  { key: 'origen', label: 'Origen' },
  { key: 'registrador', label: 'Registró' },
  {
    key: 'acciones',
    label: 'Acciones',
    cellClass: 'text-right',
    headerClass: 'text-right',
  },
];
</script>
<template>
  <Head title="Ventas" /><AppLayout
    :breadcrumbs="[{ title: 'Ventas', href: '/comercio/ventas' }]"
    ><div
      class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
    >
      <DataTable
        title="Ventas"
        :columns="columns"
        :data="data"
        v-model:search="search"
        v-model:sort-by="sortBy"
        v-model:sort-order="sortOrder"
        :is-loading="isLoading"
        @change-page="goToPage"
      >
        <template #actions
          ><Button as-child
            ><Link href="/comercio/ventas/crear"
              ><Plus class="size-4" /> Registrar venta</Link
            ></Button
          ></template
        >
        <template #toolbar
          ><form
            class="flex flex-wrap items-end gap-3"
            @submit.prevent="filter"
          >
            <div class="w-40 space-y-1">
              <Label>Estado</Label
              ><Select v-model="estado"
                ><SelectTrigger aria-label="Estado de las ventas"
                  ><SelectValue /></SelectTrigger
                ><SelectContent
                  ><SelectItem value="todos">Todos</SelectItem
                  ><SelectItem value="pendiente">Pendientes de cobro</SelectItem
                  ><SelectItem value="cobrada">Cobradas</SelectItem
                  ><SelectItem value="anulada"
                    >Anuladas</SelectItem
                  ></SelectContent
                ></Select
              >
            </div>
            <div class="space-y-1">
              <Label for="sales-from">Desde</Label
              ><Input id="sales-from" v-model="desde" type="date" />
            </div>
            <div class="space-y-1">
              <Label for="sales-to">Hasta</Label
              ><Input id="sales-to" v-model="hasta" type="date" :min="desde" />
            </div>
            <Button variant="outline" type="submit">Aplicar filtros</Button>
          </form></template
        >
        <template #cell-id="{ item }">#{{ item.id }}</template>
        <template #cell-cliente="{ item }"
          ><span>{{
            item.cliente
              ? `${item.cliente.nombre} ${item.cliente.apellido}`
              : 'Consumidor final'
          }}</span
          ><Link
            v-if="item.pedido && !item.pedido.deleted_at"
            class="block text-xs text-blue-600 dark:text-blue-400"
            :href="`/Pedido/${item.pedido.id}`"
            >{{ item.pedido.codigo }}</Link
          ></template
        >
        <template #cell-estado="{ item }"
          ><span
            class="rounded-full px-2.5 py-1 text-xs font-medium"
            :class="
              item.estado !== 'anulada' && item.fecha_cobro
                ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300'
                : 'bg-muted text-muted-foreground'
            "
            >{{
              item.estado === 'anulada'
                ? 'Anulada'
                : item.fecha_cobro
                  ? 'Cobrada'
                  : 'Pendiente de cobro'
            }}</span
          ></template
        >
        <template #cell-total_centavos="{ item }">{{
          moneda(item.total_centavos)
        }}</template>
        <template #cell-origen="{ item }">{{
          item.pedido_id ? 'Pedido' : 'Venta directa'
        }}</template>
        <template #cell-registrador="{ item }">{{
          item.registrador?.name || 'Sin registro anterior'
        }}</template>
        <template #cell-acciones="{ item }"
          ><Button variant="ghost" size="icon" as-child
            ><Link
              :href="`/comercio/operaciones/${item.id}`"
              :aria-label="`Ver venta ${item.id}`"
              ><Eye class="size-4" /></Link></Button
        ></template>
      </DataTable></div
  ></AppLayout>
</template>
