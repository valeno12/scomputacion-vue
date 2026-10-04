<script setup lang="ts">
import { usePrivacyMode } from '@/composables/usePrivacyMode';
import {
  moneda as formatMoney,
  quienRecuperaCosto,
  type ItemOperacion,
} from '@/types/comercio';
defineProps<{ items: ItemOperacion[] }>();
const privacy = usePrivacyMode();
const moneda = (value: number) =>
  privacy.value ? '••••••' : formatMoney(value);
</script>
<template>
  <div class="overflow-x-auto">
    <table class="sale-items w-full text-sm">
      <thead class="bg-muted/40 text-left text-foreground/80">
        <tr>
          <th class="px-4 py-3 font-medium">Producto</th>
          <th class="px-4 py-3 font-medium">Quién recupera el costo</th>
          <th class="px-4 py-3 text-center font-medium">Cant.</th>
          <th class="px-4 py-3 text-right font-medium">Precio</th>
          <th class="px-4 py-3 text-right font-medium">Subtotal</th>
        </tr>
      </thead>
      <tbody class="divide-y">
        <tr v-for="item in items" :key="item.id">
          <td class="px-4 py-4 font-medium">{{ item.descripcion }}</td>
          <td data-label="Quién recupera el costo" class="px-4 py-4">
            {{ quienRecuperaCosto(item) }}
          </td>
          <td data-label="Cantidad" class="px-4 py-4 text-center">
            {{ item.cantidad }}
          </td>
          <td
            data-label="Precio"
            class="px-4 py-4 text-right whitespace-nowrap tabular-nums"
          >
            {{ moneda(item.precio_unitario_centavos) }}
          </td>
          <td
            data-label="Subtotal"
            class="px-4 py-4 text-right font-semibold whitespace-nowrap tabular-nums"
          >
            {{ moneda(item.precio_unitario_centavos * item.cantidad) }}
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
<style scoped>
@media (max-width: 639px) {
  .sale-items,
  .sale-items tbody {
    display: block;
  }
  .sale-items thead {
    display: none;
  }
  .sale-items tr {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1rem;
    padding: 1rem;
  }
  .sale-items td {
    padding: 0;
    text-align: left;
  }
  .sale-items td:first-child {
    grid-column: 1 / -1;
  }
  .sale-items td[data-label]::before {
    content: attr(data-label);
    display: block;
    font-size: 0.75rem;
    font-weight: 400;
    margin-bottom: 0.25rem;
    color: var(--muted-foreground);
  }
}
</style>
