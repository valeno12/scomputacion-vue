<script setup lang="ts">
import { usePrivacyMode } from '@/composables/usePrivacyMode';
import { moneda as formatMoney, type ItemOperacion } from '@/types/comercio';
import { computed } from 'vue';
const props = defineProps<{ items: ItemOperacion[] }>();
const privacy = usePrivacyMode();
const moneda = (value: number) =>
  privacy.value ? '••••••' : formatMoney(value);
const filas = computed(() =>
  props.items.flatMap((item) =>
    item.distribucion.map((parte) => ({
      id: `${item.id}-${parte.participante_id}`,
      producto: item.descripcion,
      cantidad: item.cantidad,
      costo: item.costo_unitario_centavos * item.cantidad,
      venta: item.precio_unitario_centavos * item.cantidad,
      participante: parte.nombre,
      recupera: parte.costo_centavos,
      ganancia: parte.ganancia_centavos,
      recibe: parte.costo_centavos + parte.ganancia_centavos,
    })),
  ),
);
const totales = computed(() =>
  filas.value.reduce(
    (t, f) => ({
      recupera: t.recupera + f.recupera,
      ganancia: t.ganancia + f.ganancia,
      recibe: t.recibe + f.recibe,
    }),
    { recupera: 0, ganancia: 0, recibe: 0 },
  ),
);
</script>
<template>
  <div class="overflow-x-auto">
    <table class="w-full min-w-[900px] text-sm">
      <thead class="bg-muted/40 text-left text-foreground/80">
        <tr>
          <th class="px-4 py-3">Producto</th>
          <th class="px-3 py-3 text-right">Costo</th>
          <th class="px-3 py-3 text-right">Venta</th>
          <th class="px-4 py-3">Participante</th>
          <th class="px-3 py-3 text-right">Recupera costo</th>
          <th class="px-3 py-3 text-right">Ganancia</th>
          <th class="px-4 py-3 text-right">Recibe</th>
        </tr>
      </thead>
      <tbody class="divide-y">
        <tr v-for="fila in filas" :key="fila.id">
          <td class="px-4 py-3 font-medium">
            {{ fila.producto }}
            <span class="text-xs text-muted-foreground"
              >× {{ fila.cantidad }}</span
            >
          </td>
          <td class="px-3 py-3 text-right tabular-nums">
            {{ moneda(fila.costo) }}
          </td>
          <td class="px-3 py-3 text-right tabular-nums">
            {{ moneda(fila.venta) }}
          </td>
          <td class="px-4 py-3 font-medium">{{ fila.participante }}</td>
          <td class="px-3 py-3 text-right tabular-nums">
            {{ moneda(fila.recupera) }}
          </td>
          <td class="px-3 py-3 text-right tabular-nums">
            {{ moneda(fila.ganancia) }}
          </td>
          <td class="px-4 py-3 text-right font-semibold tabular-nums">
            {{ moneda(fila.recibe) }}
          </td>
        </tr>
      </tbody>
      <tfoot class="border-t bg-muted/30 font-semibold">
        <tr>
          <td colspan="4" class="px-4 py-3 text-right">Totales distribuidos</td>
          <td class="px-3 py-3 text-right">{{ moneda(totales.recupera) }}</td>
          <td class="px-3 py-3 text-right">{{ moneda(totales.ganancia) }}</td>
          <td class="px-4 py-3 text-right">{{ moneda(totales.recibe) }}</td>
        </tr>
      </tfoot>
    </table>
  </div>
</template>
