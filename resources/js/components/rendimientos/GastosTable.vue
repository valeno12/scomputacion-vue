<template>
  <div
    class="overflow-hidden rounded-xl border bg-white shadow-sm dark:bg-gray-950"
  >
    <!-- Header -->
    <div
      class="flex items-center justify-between border-b bg-gradient-to-r from-red-50 to-rose-50 px-6 py-4 dark:from-red-950/30 dark:to-rose-950/30"
    >
      <div class="flex items-center gap-3">
        <div
          class="flex h-10 w-10 items-center justify-center rounded-lg bg-red-100 dark:bg-red-900/50"
        >
          <ShoppingCart class="h-5 w-5 text-red-600 dark:text-red-400" />
        </div>
        <div>
          <h3 class="font-semibold text-gray-900 dark:text-gray-100">
            Gastos del Mes
          </h3>
          <p class="text-sm text-gray-500 dark:text-gray-400">
            Compras e insumos
          </p>
        </div>
      </div>
      <div class="rounded-lg bg-red-100 px-3 py-1 dark:bg-red-900/50">
        <span class="text-sm font-semibold text-red-700 dark:text-red-300">
          {{ gastos.length }} items
        </span>
      </div>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto">
      <Table>
        <TableHeader>
          <TableRow
            class="bg-gray-50 hover:bg-gray-50 dark:bg-gray-900/50 dark:hover:bg-gray-900/50"
          >
            <TableHead class="font-semibold">Producto</TableHead>
            <TableHead class="font-semibold">P. Unitario</TableHead>
            <TableHead class="font-semibold">P. Total</TableHead>
            <TableHead class="font-semibold">Cantidad</TableHead>
            <TableHead class="font-semibold">Fecha</TableHead>
          </TableRow>
        </TableHeader>

        <TableBody>
          <TableRow v-if="gastos.length === 0">
            <TableCell colspan="5" class="py-12 text-center">
              <div
                class="flex flex-col items-center gap-2 text-muted-foreground"
              >
                <ShoppingCart class="h-12 w-12 opacity-20" />
                <p>No hay gastos registrados</p>
              </div>
            </TableCell>
          </TableRow>

          <TableRow
            v-else
            v-for="(gasto, index) in gastos"
            :key="gasto.id"
            :class="[
              'transition-colors',
              index % 2 === 0
                ? 'bg-white dark:bg-gray-950'
                : 'bg-gray-50/50 dark:bg-gray-900/30',
              'hover:bg-red-50 dark:hover:bg-red-950/20',
            ]"
          >
            <TableCell>
              <div class="flex items-center gap-2">
                <div
                  class="flex h-8 w-8 items-center justify-center rounded bg-gray-100 dark:bg-gray-800"
                >
                  <Package class="h-4 w-4 text-gray-500 dark:text-gray-400" />
                </div>
                <div>
                  <p class="font-medium">{{ gasto.producto?.nombre }}</p>
                  <p class="text-xs text-muted-foreground">
                    {{ gasto.producto?.marca }}
                  </p>
                </div>
              </div>
            </TableCell>
            <TableCell class="font-medium">
              {{ formatMoney(gasto.precio || 0) }}
            </TableCell>
            <TableCell class="font-semibold text-red-600 dark:text-red-400">
              {{ formatMoney((gasto.precio || 0) * gasto.cantidad) }}
            </TableCell>
            <TableCell>
              <span
                class="rounded-full bg-gray-100 px-2 py-1 text-xs font-medium dark:bg-gray-800"
              >
                {{ gasto.cantidad }}
              </span>
            </TableCell>
            <TableCell class="text-sm text-muted-foreground">
              {{ formatDate(gasto.fecha, { includeTime: false }) }}
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>
    </div>

    <!-- Footer con total -->
    <div
      v-if="gastos.length > 0"
      class="border-t bg-gray-50 px-6 py-3 dark:bg-gray-900/30"
    >
      <div class="flex items-center justify-between">
        <span class="text-sm font-medium text-gray-600 dark:text-gray-400"
          >Total Gastos</span
        >
        <span class="text-lg font-bold text-red-600 dark:text-red-400">
          {{ formatMoney(totalGastos) }}
        </span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import type { MovimientoStock } from '@/types/movimiento-stock.interface';
import { formatDate, formatMoney } from '@/utils/formatter';
import { Package, ShoppingCart } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
  gastos: MovimientoStock[];
}

const props = defineProps<Props>();

const totalGastos = computed(() => {
  return props.gastos.reduce((sum, gasto) => {
    return sum + (gasto.precio || 0) * gasto.cantidad;
  }, 0);
});
</script>
