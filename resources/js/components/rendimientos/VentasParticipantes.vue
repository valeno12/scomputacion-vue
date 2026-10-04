<script setup lang="ts">
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { usePrivacyMode } from '@/composables/usePrivacyMode';
import { moneda as formatMoney } from '@/types/comercio';
import type { VentasParticipante } from '@/types/rendimientos.interfaces';
import { ShoppingBag } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
const props = defineProps<{
  participantes: VentasParticipante[];
  titularId: number;
  ocultarSinCobros?: boolean;
  contexto?: string;
}>();
const hayCobros = computed(() =>
  props.participantes.some((p) => p.cantidad > 0),
);
const seleccionado = ref(String(props.titularId));
watch(
  () => props.participantes,
  (rows) => {
    if (!rows.some((r) => String(r.participante_id) === seleccionado.value))
      seleccionado.value = String(rows[0]?.participante_id ?? '');
  },
  { immediate: true },
);
const privacy = usePrivacyMode();
const moneda = (value: number) =>
  privacy.value ? '••••••' : formatMoney(value);
</script>
<template>
  <section
    class="min-w-0 rounded-xl border bg-card shadow-sm"
    aria-labelledby="ventas-participantes-heading"
  >
    <div class="flex items-center gap-2 border-b px-5 py-4">
      <ShoppingBag class="size-4 text-blue-600 dark:text-blue-400" />
      <h2 id="ventas-participantes-heading" class="font-semibold">Ventas</h2>
    </div>
    <Tabs
      v-if="participantes.length && (!ocultarSinCobros || hayCobros)"
      v-model="seleccionado"
      class="gap-0"
    >
      <div class="overflow-x-auto px-4 pt-3">
        <TabsList
          class="h-auto min-w-full justify-start gap-1 bg-transparent p-0"
          aria-label="Participantes de las ventas"
        >
          <TabsTrigger
            v-for="p in participantes"
            :key="p.participante_id"
            :value="String(p.participante_id)"
            class="flex-none px-3 py-2 data-[state=active]:bg-muted data-[state=active]:shadow-none"
            >{{ p.nombre }}</TabsTrigger
          >
        </TabsList>
      </div>
      <TabsContent
        v-for="p in participantes"
        :key="p.participante_id"
        :value="String(p.participante_id)"
        class="space-y-3 px-5 pt-4 pb-5"
      >
        <p v-if="!p.activo" class="text-xs text-muted-foreground">
          Participante inactivo
        </p>
        <p class="text-xs text-muted-foreground">
          {{ p.cantidad }}
          {{
            p.cantidad === 1
              ? 'operación cobrada con productos'
              : 'operaciones cobradas con productos'
          }}
        </p>
        <dl class="space-y-2 text-sm">
          <div class="flex justify-between gap-3">
            <dt class="text-muted-foreground">Costo que recupera</dt>
            <dd class="tabular-nums">{{ moneda(p.costo_centavos) }}</dd>
          </div>
          <div class="flex justify-between gap-3">
            <dt class="text-muted-foreground">Ganancia</dt>
            <dd class="tabular-nums">{{ moneda(p.ganancia_centavos) }}</dd>
          </div>
          <div class="flex justify-between gap-3 border-t pt-3 font-semibold">
            <dt>Para {{ p.nombre }}</dt>
            <dd class="tabular-nums">{{ moneda(p.total_centavos) }}</dd>
          </div>
        </dl>
      </TabsContent>
    </Tabs>
    <p v-else class="px-5 py-5 text-sm leading-relaxed text-foreground/80">
      Todavía no hay productos cobrados en este pedido. El reparto aparecerá al
      cobrarlos.
    </p>
    <p
      v-if="!ocultarSinCobros || hayCobros"
      class="border-t px-5 py-3 text-sm leading-relaxed text-foreground/75"
    >
      {{ contexto || 'Incluye productos vendidos en pedidos cobrados.' }}
    </p>
  </section>
</template>
