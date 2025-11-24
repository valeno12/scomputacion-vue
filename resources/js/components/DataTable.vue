<template>
  <div class="space-y-4">
    <!-- Header: Título, Búsqueda y Acciones -->
    <div
      class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center"
    >
      <h3 class="text-2xl font-semibold">{{ title }}</h3>

      <div
        class="flex w-full flex-col items-stretch gap-2 sm:w-auto sm:flex-row sm:items-center"
      >
        <!-- Buscador -->
        <div class="relative w-full sm:w-64">
          <Search
            class="absolute top-2.5 left-2 h-4 w-4 text-muted-foreground"
          />
          <Input v-model="search" placeholder="Buscar..." class="pl-8" />
        </div>

        <!-- Slot para botones personalizados -->
        <slot name="actions"></slot>
      </div>
    </div>

    <!-- Tabla -->
    <div class="rounded-md border">
      <Table>
        <TableHeader>
          <TableRow class="bg-slate-50 hover:bg-slate-50 dark:bg-slate-900/70">
            <TableHead
              v-for="column in columns"
              :key="column.key"
              :class="[
                column.headerClass,
                'font-semibold text-slate-900 dark:text-slate-100',
              ]"
              @click="column.sortable ? toggleSort(column.key) : null"
              :style="{
                cursor: column.sortable ? 'pointer' : 'default',
              }"
            >
              <div class="flex items-center gap-2">
                {{ column.label }}
                <template v-if="column.sortable">
                  <ArrowUpDown
                    v-if="sortBy !== column.key"
                    class="h-4 w-4 text-muted-foreground"
                  />
                  <ArrowUp v-else-if="sortOrder === 'asc'" class="h-4 w-4" />
                  <ArrowDown v-else class="h-4 w-4" />
                </template>
              </div>
            </TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow v-if="isLoading">
            <TableCell :colspan="columns.length" class="py-8 text-center">
              <div class="flex items-center justify-center gap-2">
                <div
                  class="h-5 w-5 animate-spin rounded-full border-b-2 border-primary"
                ></div>
                Cargando...
              </div>
            </TableCell>
          </TableRow>

          <TableRow v-else-if="data.data.length === 0">
            <TableCell
              :colspan="columns.length"
              class="py-8 text-center text-muted-foreground"
            >
              No se encontraron resultados
            </TableCell>
          </TableRow>

          <TableRow
            v-else
            v-for="(item, index) in data.data"
            :key="getItemKey(item)"
            :class="[
              'transition-colors',
              index % 2 === 0
                ? 'bg-white dark:bg-slate-900/10'
                : 'bg-slate-50/50 dark:bg-slate-900/30',
              'hover:bg-slate-100 dark:hover:bg-slate-800/40',
            ]"
          >
            <TableCell
              v-for="column in columns"
              :key="column.key"
              :class="column.cellClass"
            >
              <slot :name="`cell-${column.key}`" :item="item">
                {{ getNestedValue(item, column.key) }}
              </slot>
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>
    </div>

    <!-- Paginación -->
    <div
      v-if="data && data.total > 0"
      class="flex flex-col items-center justify-between gap-4 sm:flex-row"
    >
      <div class="text-sm text-muted-foreground">
        Mostrando {{ data.from || 0 }} a {{ data.to || 0 }} de
        {{ data.total }} resultados
      </div>

      <div class="flex items-center gap-2">
        <Button
          variant="outline"
          size="sm"
          :disabled="!data.prev_page_url"
          @click="goToPage(data.current_page - 1)"
        >
          <ChevronLeft class="h-4 w-4" />
          Anterior
        </Button>

        <div class="flex gap-1">
          <Button
            v-for="pageNum in visiblePages"
            :key="pageNum"
            variant="outline"
            size="sm"
            :class="{
              'bg-primary text-primary-foreground dark:text-white dark:ring-2 dark:ring-white':
                pageNum === data.current_page,
            }"
            @click="goToPage(pageNum)"
          >
            {{ pageNum }}
          </Button>
        </div>

        <Button
          variant="outline"
          size="sm"
          :disabled="!data.next_page_url"
          @click="goToPage(data.current_page + 1)"
        >
          Siguiente
          <ChevronRight class="h-4 w-4" />
        </Button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts" generic="T extends Record<string, any>">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { LaravelPagination } from '@/types/pagination';
import {
  ArrowDown,
  ArrowUp,
  ArrowUpDown,
  ChevronLeft,
  ChevronRight,
  Search,
} from 'lucide-vue-next';
import { computed } from 'vue';

export interface TableColumn {
  key: string;
  label: string;
  sortable?: boolean;
  headerClass?: string;
  cellClass?: string;
}

interface Props {
  title: string;
  columns: TableColumn[];
  data: LaravelPagination<T>;
  search: string;
  sortBy: string;
  sortOrder: 'asc' | 'desc';
  isLoading?: boolean;
}

interface Emits {
  (e: 'update:search', value: string): void;
  (e: 'update:sortBy', value: string): void;
  (e: 'update:sortOrder', value: 'asc' | 'desc'): void;
  (e: 'changePage', page: number): void;
}

const props = withDefaults(defineProps<Props>(), {
  isLoading: false,
});

const emit = defineEmits<Emits>();

const search = computed({
  get: () => props.search,
  set: (value: string) => emit('update:search', value),
});

const sortBy = computed(() => props.sortBy);
const sortOrder = computed(() => props.sortOrder);

const toggleSort = (column: string): void => {
  if (sortBy.value === column) {
    emit('update:sortOrder', sortOrder.value === 'asc' ? 'desc' : 'asc');
  } else {
    emit('update:sortBy', column);
    emit('update:sortOrder', 'asc');
  }
};

const goToPage = (page: number): void => {
  emit('changePage', page);
};

// Calcular páginas visibles (mostrar máximo 5 páginas)
const visiblePages = computed((): number[] => {
  const current = props.data.current_page;
  const last = props.data.last_page;
  const delta = 2;
  const range: number[] = [];
  const rangeWithDots: (number | string)[] = [];

  for (
    let i = Math.max(2, current - delta);
    i <= Math.min(last - 1, current + delta);
    i++
  ) {
    range.push(i);
  }

  if (current - delta > 2) {
    rangeWithDots.push(1, '...');
  } else {
    rangeWithDots.push(1);
  }

  rangeWithDots.push(...range);

  if (current + delta < last - 1) {
    rangeWithDots.push('...', last);
  } else if (last > 1) {
    rangeWithDots.push(last);
  }

  return rangeWithDots.filter((p): p is number => typeof p === 'number');
});

// Utilidad para obtener valores anidados (ej: "usuario.nombre")
const getNestedValue = (obj: T, path: string): any => {
  return path.split('.').reduce((o: any, p: string) => o?.[p], obj);
};

// Obtener key único para cada item
const getItemKey = (item: T): string | number => {
  return (item as any).id || JSON.stringify(item);
};
</script>
