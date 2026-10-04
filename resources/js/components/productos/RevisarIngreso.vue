<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { formatMoney } from '@/utils/formatter';
import {
  precioProducto,
  type IngresoResumenItem,
} from '@/utils/precioProducto';
import { computed } from 'vue';
const open = defineModel<boolean>('open', { required: true });
const props = defineProps<{
  items: IngresoResumenItem[];
  comprador: string;
  proveedor: string;
  fecha: string;
  tipo: string;
  processing: boolean;
}>();
defineEmits<{ confirmar: [] }>();
const rows = computed(() =>
  props.items.map((item) => ({
    ...item,
    precio: precioProducto(item.costo, item.porcentaje_ganancia),
    totalCosto: Math.round(Number(item.costo) * 100) * Number(item.cantidad),
  })),
);
const totalCosto = computed(() =>
  rows.value.reduce((sum, item) => sum + item.totalCosto, 0),
);
const totalVenta = computed(() =>
  rows.value.reduce(
    (sum, item) => sum + item.precio * Number(item.cantidad),
    0,
  ),
);
const unidades = computed(() =>
  rows.value.reduce((sum, item) => sum + Number(item.cantidad), 0),
);
</script>
<template>
  <Dialog v-model:open="open">
    <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-4xl">
      <DialogHeader
        ><DialogTitle>Revisar ingreso</DialogTitle
        ><DialogDescription
          >Revisá los datos antes de confirmar. Todavía no se guardó el
          ingreso.</DialogDescription
        ></DialogHeader
      >
      <dl
        class="grid grid-cols-2 gap-3 rounded-lg bg-muted/50 p-4 text-sm sm:grid-cols-4"
      >
        <div>
          <dt class="text-muted-foreground">Proveedor</dt>
          <dd class="font-medium break-words">{{ proveedor }}</dd>
        </div>
        <div>
          <dt class="text-muted-foreground">Quién compró</dt>
          <dd class="font-medium">{{ comprador }}</dd>
        </div>
        <div>
          <dt class="text-muted-foreground">Fecha</dt>
          <dd class="font-medium">
            {{ fecha.split('-').reverse().join('/') }}
          </dd>
        </div>
        <div>
          <dt class="text-muted-foreground">Tipo</dt>
          <dd class="font-medium">
            {{ tipo === 'inicial' ? 'Stock inicial' : 'Compra' }}
          </dd>
        </div>
      </dl>
      <div class="overflow-x-auto rounded-lg border">
        <table class="w-full text-sm">
          <thead class="bg-muted/50">
            <tr>
              <th class="p-3 text-left">Producto</th>
              <th class="p-3 text-right">Cant.</th>
              <th class="p-3 text-right whitespace-nowrap">Costo unit.</th>
              <th class="p-3 text-right">Ganancia %</th>
              <th class="p-3 text-right whitespace-nowrap">Venta unit.</th>
              <th class="p-3 text-right whitespace-nowrap">Costo total</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(item, index) in rows" :key="index" class="border-t">
              <td class="p-3">
                <span class="font-medium">{{ item.nombre }}</span
                ><span
                  v-if="item.marca"
                  class="block text-xs text-muted-foreground"
                  >{{ item.marca }}</span
                >
              </td>
              <td class="p-3 text-right">{{ item.cantidad }}</td>
              <td class="p-3 text-right whitespace-nowrap">
                {{ formatMoney(Number(item.costo)) }}
              </td>
              <td class="p-3 text-right">{{ item.porcentaje_ganancia }}%</td>
              <td class="p-3 text-right whitespace-nowrap">
                {{ formatMoney(item.precio / 100) }}
              </td>
              <td class="p-3 text-right whitespace-nowrap">
                {{ formatMoney(item.totalCosto / 100) }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="grid gap-3 sm:grid-cols-3">
        <div
          class="rounded-lg border border-blue-200 bg-blue-50 p-3 dark:border-blue-500/25 dark:bg-blue-500/10"
        >
          <p class="text-sm text-muted-foreground">
            Costo total · {{ unidades }} unidades
          </p>
          <p class="text-xl font-semibold text-blue-700 dark:text-blue-300">
            {{ formatMoney(totalCosto / 100) }}
          </p>
        </div>
        <div class="rounded-lg border p-3">
          <p class="text-sm text-muted-foreground">Venta prevista</p>
          <p class="text-xl font-semibold">
            {{ formatMoney(totalVenta / 100) }}
          </p>
        </div>
        <div
          class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 dark:border-emerald-500/25 dark:bg-emerald-500/10"
        >
          <p class="text-sm text-muted-foreground">Ganancia prevista</p>
          <p
            class="text-xl font-semibold text-emerald-700 dark:text-emerald-300"
          >
            {{ formatMoney((totalVenta - totalCosto) / 100) }}
          </p>
        </div>
      </div>
      <p class="text-xs text-muted-foreground">
        La venta y la ganancia previstas suponen vender todas las unidades al
        precio calculado. Los precios del catálogo se actualizarán para próximas
        ventas.
      </p>
      <DialogFooter
        ><Button
          type="button"
          variant="outline"
          :disabled="processing"
          @click="open = false"
          >Volver a editar</Button
        ><Button
          type="button"
          :disabled="processing"
          @click="$emit('confirmar')"
          >{{ processing ? 'Guardando…' : 'Confirmar ingreso' }}</Button
        ></DialogFooter
      >
    </DialogContent>
  </Dialog>
</template>
