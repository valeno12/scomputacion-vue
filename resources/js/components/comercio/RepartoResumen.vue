<script setup lang="ts">
import { usePrivacyMode } from '@/composables/usePrivacyMode';
import {
  moneda as formatMoney,
  type ResumenParticipante,
} from '@/types/comercio';
import { UsersRound } from 'lucide-vue-next';
import { computed } from 'vue';
const props = withDefaults(
  defineProps<{
    reparto: ResumenParticipante[];
    previsto?: boolean;
    anulado?: boolean;
    soloTitular?: boolean;
    contexto?: string;
  }>(),
  { previsto: false, anulado: false, soloTitular: false },
);
const privacy = usePrivacyMode();
const moneda = (value: number) =>
  privacy.value ? '••••••' : formatMoney(value);
const personas = computed(() =>
  props.reparto.filter((r) => !props.soloTitular || r.rol === 'titular'),
);
</script>
<template>
  <section
    class="@container overflow-hidden rounded-xl border"
    :class="anulado ? 'opacity-60' : ''"
  >
    <div
      class="flex flex-wrap items-center justify-between gap-2 border-b bg-violet-50/70 px-4 py-3 dark:bg-violet-500/10"
    >
      <h3 class="flex items-center gap-2 text-sm font-semibold">
        <UsersRound class="size-4 text-violet-600 dark:text-violet-300" />{{
          soloTitular ? 'Importe para el titular' : 'Cómo se reparte el cobro'
        }}
      </h3>
      <span class="text-xs text-muted-foreground">{{
        contexto ||
        (anulado
          ? 'Venta anulada'
          : previsto
            ? 'Al cobrar la operación'
            : 'Operación cobrada')
      }}</span>
    </div>
    <div class="divide-y">
      <div
        v-for="r in personas"
        :key="r.participante_id"
        class="grid grid-cols-[1fr_auto] items-center gap-x-5 gap-y-2 px-4 py-3 @xl:grid-cols-[minmax(140px,1fr)_1fr_auto]"
        :class="
          r.rol !== 'titular'
            ? 'bg-violet-50/30 dark:bg-violet-500/5'
            : 'bg-card'
        "
      >
        <div class="flex min-w-0 items-center gap-2.5">
          <span
            class="flex size-8 shrink-0 items-center justify-center rounded-full text-xs font-bold"
            :class="
              r.rol === 'titular'
                ? 'bg-blue-100 text-blue-700 dark:bg-blue-500/20 dark:text-blue-300'
                : 'bg-violet-100 text-violet-700 dark:bg-violet-500/20 dark:text-violet-300'
            "
            >{{ r.nombre.slice(0, 1).toUpperCase() }}</span
          >
          <div class="min-w-0">
            <p class="truncate text-sm font-semibold">Para {{ r.nombre }}</p>
            <p class="text-xs text-muted-foreground">
              {{ r.rol === 'titular' ? 'Titular' : 'Participante' }}
            </p>
          </div>
        </div>
        <p
          class="col-span-2 row-start-2 text-xs leading-relaxed text-muted-foreground @xl:col-span-1 @xl:col-start-2 @xl:row-start-1"
        >
          {{ moneda(r.costo_centavos) }} de costo <span class="mx-1">+</span>
          {{ moneda(r.ganancia_centavos) }} de ganancia
        </p>
        <strong
          class="col-start-2 row-start-1 text-right text-lg whitespace-nowrap tabular-nums @xl:col-start-3"
          :class="{ 'line-through': anulado }"
          >{{ moneda(r.total_centavos) }}</strong
        >
      </div>
    </div>
    <p class="border-t bg-muted/20 px-4 py-2 text-xs text-muted-foreground">
      {{
        anulado
          ? 'No se suma a Rendimientos.'
          : 'El costo vuelve a quien compró. Estos importes indican cuánto corresponde a cada persona, no si ya se transfirió.'
      }}
    </p>
  </section>
</template>
