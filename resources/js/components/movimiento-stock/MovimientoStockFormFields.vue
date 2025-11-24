<template>
  <div class="space-y-6">
    <!-- Producto (readonly) -->
    <div class="space-y-2">
      <Label for="producto">Producto</Label>
      <div
        class="flex items-center gap-3 rounded-lg border bg-gray-50 p-3 dark:bg-gray-900"
      >
        <Package class="h-5 w-5 text-muted-foreground" />
        <div>
          <p class="font-medium">{{ movimiento.producto?.nombre }}</p>
          <p
            v-if="movimiento.producto?.marca"
            class="text-sm text-muted-foreground"
          >
            {{ movimiento.producto.marca }}
          </p>
        </div>
      </div>
    </div>

    <!-- Fecha (readonly) - Ahora como div -->
    <div class="space-y-2">
      <Label>Fecha</Label>
      <div
        class="flex h-10 w-full rounded-md border border-input bg-gray-50 px-3 py-2 text-sm dark:bg-gray-900"
      >
        {{
          movimiento.fecha
            ? formatDate(movimiento.fecha, { includeTime: true })
            : 'Sin fecha'
        }}
      </div>
    </div>

    <!-- Cantidad -->
    <div class="space-y-2">
      <Label for="cantidad">
        Cantidad
        <span class="text-red-500">*</span>
      </Label>
      <Input
        id="cantidad"
        name="cantidad"
        type="number"
        min="1"
        step="1"
        v-model.number="form.cantidad"
        :class="{ 'border-red-500': form.errors.cantidad }"
        placeholder="Ingrese la cantidad"
      />
      <p v-if="form.errors.cantidad" class="text-sm text-red-500">
        {{ form.errors.cantidad }}
      </p>
    </div>

    <!-- Precio -->
    <div class="space-y-2">
      <Label for="precio">
        Precio Unitario
        <span class="text-red-500">*</span>
      </Label>
      <Input
        id="precio"
        name="precio"
        type="number"
        min="0"
        step="0.01"
        v-model.number="form.precio"
        :class="{ 'border-red-500': form.errors.precio }"
        placeholder="Ingrese el precio"
      />
      <p v-if="form.errors.precio" class="text-sm text-red-500">
        {{ form.errors.precio }}
      </p>
    </div>

    <!-- Total calculado -->
    <div class="rounded-lg border bg-blue-50 p-4 dark:bg-blue-950/20">
      <div class="flex items-center justify-between">
        <span class="font-medium text-blue-900 dark:text-blue-100">
          Precio Total
        </span>
        <span class="text-xl font-bold text-blue-900 dark:text-blue-100">
          {{ formatMoney(totalCalculado) }}
        </span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { MovimientoStock } from '@/types/movimiento-stock.interface';
import { formatDate, formatMoney } from '@/utils/formatter';
import { Package } from 'lucide-vue-next';
import { computed, inject } from 'vue';

interface Props {
  movimiento: MovimientoStock;
}

defineProps<Props>();

const form = inject<any>('movimientoStockForm');

const totalCalculado = computed(() => {
  return (form.cantidad || 0) * (form.precio || 0);
});
</script>
