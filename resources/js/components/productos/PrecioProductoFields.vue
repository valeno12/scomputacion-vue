<script setup lang="ts">
import FormField from '@/components/common/FormField.vue';
import { Input } from '@/components/ui/input';
import { formatMoney } from '@/utils/formatter';
import { precioProducto } from '@/utils/precioProducto';
import { computed } from 'vue';

const costo = defineModel<number | string>('costo', { required: true });
const porcentaje = defineModel<number | string>('porcentaje', {
  required: true,
});
withDefaults(
  defineProps<{ disabled?: boolean; errors?: Record<string, string> }>(),
  { errors: () => ({}) },
);
const precio = computed(() => precioProducto(costo.value, porcentaje.value));
</script>
<template>
  <div class="grid gap-5 md:grid-cols-2">
    <FormField id="costo" label="Costo unitario" :error="errors.costo" required>
      <Input
        id="costo"
        v-model.number="costo"
        type="number"
        min="0"
        max="999999999"
        step="0.01"
        :disabled="disabled"
        required
      />
    </FormField>
    <FormField
      id="porcentaje_ganancia"
      label="Ganancia sobre costo (%)"
      :error="errors.porcentaje_ganancia"
      hint="Se suma este porcentaje al costo para calcular el precio de venta."
      required
    >
      <Input
        id="porcentaje_ganancia"
        v-model.number="porcentaje"
        type="number"
        min="0"
        max="10000"
        step="0.01"
        placeholder="Ej.: 40"
        :disabled="disabled"
        required
      />
    </FormField>
  </div>
  <div
    aria-live="polite"
    class="grid gap-3 rounded-lg border border-blue-200 bg-blue-50 p-4 sm:grid-cols-2 dark:border-blue-500/25 dark:bg-blue-500/10"
  >
    <div>
      <p class="text-sm text-muted-foreground">Precio de venta por unidad</p>
      <p class="text-xl font-semibold text-blue-700 dark:text-blue-300">
        {{
          costo === '' || porcentaje === '' ? '—' : formatMoney(precio / 100)
        }}
      </p>
    </div>
    <div>
      <p class="text-sm text-muted-foreground">Ganancia por unidad</p>
      <p class="text-xl font-semibold">
        {{
          costo === '' || porcentaje === ''
            ? '—'
            : formatMoney((precio - Math.round(Number(costo) * 100)) / 100)
        }}
      </p>
    </div>
  </div>
</template>
