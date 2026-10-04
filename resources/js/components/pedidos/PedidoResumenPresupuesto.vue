<script setup lang="ts">
import { moneda, type ItemForm } from '@/types/comercio';
import { importesPedido } from '@/utils/importesPedido';
import { computed } from 'vue';
const props = defineProps<{
  items: ItemForm[];
  manoObra: number | string | null;
}>();
const importe = computed(() => importesPedido(props.items, props.manoObra));
</script>
<template>
  <dl class="space-y-2 rounded-lg border bg-muted/50 p-4 text-sm">
    <div class="flex justify-between gap-4">
      <dt class="text-muted-foreground">Mano de obra:</dt>
      <dd class="font-medium tabular-nums">{{ moneda(importe.servicio) }}</dd>
    </div>
    <div class="flex justify-between gap-4">
      <dt class="text-muted-foreground">Repuestos:</dt>
      <dd class="font-medium tabular-nums">{{ moneda(importe.repuestos) }}</dd>
    </div>
    <div class="flex justify-between gap-4">
      <dt class="text-muted-foreground">Productos:</dt>
      <dd class="font-medium tabular-nums">{{ moneda(importe.productos) }}</dd>
    </div>
    <div class="flex justify-between gap-4 border-t pt-2 font-semibold">
      <dt>Presupuesto total:</dt>
      <dd class="text-lg tabular-nums">{{ moneda(importe.total) }}</dd>
    </div>
  </dl>
</template>
