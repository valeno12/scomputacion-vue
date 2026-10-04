<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import type { Participante, Reparto } from '@/types/comercio';
import { Check, Package, Plus, Wrench, X } from 'lucide-vue-next';
import { computed, useId } from 'vue';

const props = withDefaults(
  defineProps<{
    participantes: Participante[];
    label?: string;
    description?: string;
    errors?: string[];
    disabled?: boolean;
    tone?: 'neutral' | 'blue' | 'violet' | 'amber';
  }>(),
  {
    label: 'Reparto de la ganancia',
    description:
      'Se reparte la ganancia: precio de venta menos costo. El costo se devuelve a quien compró.',
    errors: () => [],
    disabled: false,
    tone: 'neutral',
  },
);
const tones = {
  neutral: { panel: 'bg-card', header: '', icon: '', title: '' },
  blue: {
    panel:
      'border-blue-200 bg-blue-50/30 dark:border-blue-800/70 dark:bg-slate-900/60',
    header:
      'min-h-32 border-blue-200 bg-blue-100/70 dark:border-blue-800/70 dark:bg-blue-900/35',
    icon: 'bg-blue-600 text-white',
    title: 'text-blue-900 dark:text-blue-200',
  },
  violet: {
    panel:
      'border-violet-200 bg-violet-50/30 dark:border-violet-800/70 dark:bg-slate-900/60',
    header:
      'min-h-32 border-violet-200 bg-violet-100/70 dark:border-violet-800/70 dark:bg-violet-900/35',
    icon: 'bg-violet-600 text-white',
    title: 'text-violet-900 dark:text-violet-200',
  },
  amber: {
    panel:
      'border-amber-200 bg-amber-50/30 dark:border-amber-800/70 dark:bg-slate-900/60',
    header:
      'min-h-32 border-amber-200 bg-amber-100/70 dark:border-amber-800/70 dark:bg-amber-900/35',
    icon: 'bg-amber-600 text-white',
    title: 'text-amber-900 dark:text-amber-200',
  },
};
const rows = defineModel<Reparto[]>({ required: true });
const id = useId();
const total = computed(() =>
  rows.value.reduce(
    (sum, row) => sum + Math.round(Number(row.porcentaje || 0) * 100),
    0,
  ),
);
const formatPercent = (value: number) =>
  (value / 100).toLocaleString('es-AR', { maximumFractionDigits: 2 });
const available = computed(() =>
  props.participantes.filter(
    (person) => !rows.value.some((row) => row.participante_id === person.id),
  ),
);
function add() {
  const person = available.value[0];
  if (!person) return;
  rows.value.push({
    participante_id: person.id,
    porcentaje: Math.max(0, 10000 - total.value) / 100,
  });
}
</script>

<template>
  <fieldset
    class="min-w-0 overflow-hidden rounded-lg border"
    :class="tones[tone].panel"
    :disabled="disabled"
    :aria-describedby="`${id}-description`"
  >
    <legend class="sr-only">{{ label }}</legend>
    <div
      class="flex items-start gap-3 border-b px-4 py-4 sm:px-5"
      :class="tones[tone].header"
    >
      <span
        v-if="tone !== 'neutral'"
        class="flex size-9 shrink-0 items-center justify-center rounded-lg"
        :class="tones[tone].icon"
      >
        <component :is="tone === 'blue' ? Package : Wrench" class="size-4" />
      </span>
      <div class="space-y-1">
        <h3 class="text-sm font-semibold" :class="tones[tone].title">
          {{ label }}
        </h3>
        <p :id="`${id}-description`" class="text-sm text-muted-foreground">
          {{ description }}
        </p>
      </div>
    </div>
    <div class="space-y-3 p-4 sm:p-5">
      <p v-if="!participantes.length" class="text-sm text-muted-foreground">
        Agregá un participante para definir este reparto.
      </p>
      <p v-else-if="!rows.length" class="text-sm text-muted-foreground">
        Elegí quiénes participan y qué porcentaje recibe cada persona.
      </p>
      <div
        v-for="(row, index) in rows"
        :key="index"
        class="grid grid-cols-[minmax(0,1fr)_5.5rem_2rem] items-end gap-2 sm:grid-cols-[minmax(0,1fr)_7rem_2rem] sm:gap-3"
      >
        <div class="grid min-w-0 gap-2">
          <Label
            :for="`${id}-person-${index}`"
            :class="{ 'sr-only': index > 0 }"
            >Participante</Label
          >
          <Select v-model="row.participante_id" :disabled="disabled" required>
            <SelectTrigger
              :id="`${id}-person-${index}`"
              data-slot="select-trigger"
              class="h-9 min-w-0"
            >
              <SelectValue placeholder="Seleccionar" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem
                v-for="person in participantes"
                :key="person.id"
                :value="person.id"
                :disabled="
                  rows.some(
                    (other, i) =>
                      i !== index && other.participante_id === person.id,
                  )
                "
                >{{ person.nombre }}</SelectItem
              >
              <SelectItem
                v-if="
                  row.participante_id &&
                  !participantes.some(
                    (person) => person.id === row.participante_id,
                  )
                "
                :value="row.participante_id"
                disabled
                >{{ row.nombre || 'Participante' }} (inactivo)</SelectItem
              >
            </SelectContent>
          </Select>
        </div>
        <div class="grid gap-2">
          <Label
            :for="`${id}-percent-${index}`"
            :class="{ 'sr-only': index > 0 }"
            >Porcentaje</Label
          >
          <div class="relative">
            <Input
              :id="`${id}-percent-${index}`"
              v-model="row.porcentaje"
              class="[appearance:textfield] pr-7 tabular-nums [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
              type="number"
              inputmode="decimal"
              min="0"
              max="100"
              step="0.01"
              :disabled="disabled"
              required
            />
            <span
              aria-hidden="true"
              class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-muted-foreground"
              >%</span
            >
          </div>
        </div>
        <Button
          type="button"
          variant="ghost"
          size="icon"
          class="size-8 self-end text-muted-foreground hover:text-destructive"
          :disabled="disabled"
          :aria-label="`Quitar participante ${index + 1} de ${label}`"
          @click="rows.splice(index, 1)"
          ><X class="size-4"
        /></Button>
      </div>
      <div class="flex flex-wrap items-center justify-between gap-3 pt-1">
        <Button
          type="button"
          variant="outline"
          size="sm"
          :disabled="disabled || !available.length"
          @click="add"
          ><Plus class="size-4" /> Agregar participante</Button
        >
        <span
          v-if="rows.length"
          class="flex items-center gap-1.5 text-sm font-medium tabular-nums"
          :class="
            total === 10000
              ? 'rounded-full bg-emerald-50 px-2.5 py-1 text-emerald-700 dark:bg-emerald-950/70 dark:text-emerald-300'
              : 'text-destructive'
          "
          aria-live="polite"
        >
          <Check
            v-if="total === 10000"
            class="size-4 text-emerald-600 dark:text-emerald-400"
          />
          Total {{ formatPercent(total) }} %
        </span>
      </div>
      <p
        v-if="rows.length && total !== 10000"
        class="text-sm text-destructive"
        aria-live="polite"
      >
        {{
          total < 10000
            ? `Falta asignar ${formatPercent(10000 - total)} %.`
            : `El reparto supera el 100 % por ${formatPercent(total - 10000)} %.`
        }}
      </p>
      <div v-if="errors.length" role="alert" class="space-y-1">
        <InputError v-for="error in errors" :key="error" :message="error" />
      </div>
    </div>
  </fieldset>
</template>
