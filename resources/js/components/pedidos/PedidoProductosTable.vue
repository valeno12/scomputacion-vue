<template>
  <div
    class="overflow-hidden rounded-xl border bg-white shadow-sm dark:bg-gray-950"
  >
    <div
      class="border-b bg-gradient-to-r from-green-50 to-emerald-50 px-6 py-4 dark:from-green-950/30 dark:to-emerald-950/30"
    >
      <div class="flex items-center gap-3">
        <div
          class="flex h-10 w-10 items-center justify-center rounded-lg bg-green-100 dark:bg-green-900/50"
        >
          <Package class="h-5 w-5 text-green-600 dark:text-green-400" />
        </div>
        <div>
          <h3 class="font-semibold">Productos Seleccionados</h3>
          <p class="text-xs text-muted-foreground">
            {{ productos.length }} producto{{
              productos.length !== 1 ? 's' : ''
            }}
          </p>
        </div>
      </div>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full">
        <thead class="bg-gray-50 dark:bg-gray-900/50">
          <tr class="border-b">
            <th class="px-4 py-3 text-left text-sm font-semibold">Producto</th>
            <th class="px-4 py-3 text-right text-sm font-semibold">Cantidad</th>
            <th class="px-4 py-3 text-right text-sm font-semibold">
              Precio Unit.
            </th>
            <th class="px-4 py-3 text-right text-sm font-semibold">
              Precio Venta Unit.
            </th>
            <th class="px-4 py-3 text-right text-sm font-semibold">
              Costo Total
            </th>
            <th class="px-4 py-3 text-right text-sm font-semibold">
              Total Venta
            </th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="(item, index) in productos"
            :key="item.id"
            :class="[
              'border-b transition-colors last:border-0',
              index % 2 === 0
                ? 'bg-white dark:bg-gray-950'
                : 'bg-gray-50/50 dark:bg-gray-900/30',
              'hover:bg-green-50 dark:hover:bg-green-950/20',
            ]"
          >
            <td class="px-4 py-3">
              <div class="flex items-center gap-2">
                <div
                  class="flex h-8 w-8 items-center justify-center rounded bg-gray-100 dark:bg-gray-800"
                >
                  <Package class="h-4 w-4 text-gray-500" />
                </div>
                <div>
                  <div class="font-medium">{{ item.producto.nombre }}</div>
                  <div class="text-xs text-muted-foreground">
                    {{ item.producto.marca }}
                  </div>
                </div>
              </div>
            </td>
            <td class="px-4 py-3 text-right">
              <span
                class="rounded-full bg-gray-100 px-2 py-1 text-xs font-medium dark:bg-gray-800"
              >
                {{ item.cantidad }}
              </span>
            </td>
            <td class="px-4 py-3 text-right">
              {{ formatMoney(item.precio) }}
            </td>
            <td
              class="px-4 py-3 text-right font-medium text-green-600 dark:text-green-400"
            >
              {{ formatMoney(item.precio * 1.3) }}
            </td>
            <td class="px-4 py-3 text-right">
              {{ formatMoney(item.precio * item.cantidad) }}
            </td>
            <td
              class="px-4 py-3 text-right font-semibold text-green-600 dark:text-green-400"
            >
              {{ formatMoney(item.precio * 1.3 * item.cantidad) }}
            </td>
          </tr>
        </tbody>
        <tfoot
          v-if="productos.length > 0"
          class="border-t bg-gradient-to-r from-gray-50 to-gray-100 font-semibold dark:from-gray-900/50 dark:to-gray-900/30"
        >
          <tr>
            <td colspan="4" class="px-4 py-4 text-right">
              <span class="text-lg">Totales:</span>
            </td>
            <td class="px-4 py-4 text-right">
              {{ formatMoney(costoTotal) }}
            </td>
            <td class="px-4 py-4 text-right">
              <span
                class="text-lg font-bold text-green-600 dark:text-green-400"
              >
                {{ formatMoney(precioVentaTotal) }}
              </span>
            </td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
import { formatMoney } from '@/utils/formatter';
import { Package } from 'lucide-vue-next';
import { computed } from 'vue';

interface ProductoSeleccionado {
  id: number;
  producto: {
    nombre: string;
    marca: string;
  };
  cantidad: number;
  precio: number;
}

interface Props {
  productos: ProductoSeleccionado[];
}

const props = defineProps<Props>();

const costoTotal = computed(() => {
  return props.productos.reduce((total, item) => {
    return total + item.precio * item.cantidad;
  }, 0);
});

const precioVentaTotal = computed(() => {
  return props.productos.reduce((total, item) => {
    return total + item.precio * 1.3 * item.cantidad;
  }, 0);
});
</script>
