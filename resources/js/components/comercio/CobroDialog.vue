<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { hoy, moneda } from '@/types/comercio';
import { useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
const open = defineModel<boolean>('open', { required: true });
const props = defineProps<{
  endpoint: string;
  titulo?: string;
  descripcion?: string;
  confirmar?: string;
  total: number;
  conceptos?: { nombre: string; total: number }[];
  payload?: Record<string, any>;
}>();
const emit = defineEmits<{ guardado: [] }>();
const form = useForm({ fecha_cobro: hoy(), medio_pago: '', total_esperado: 0 });
const fechaVisible = ref('');
const fechaMaxima = ref(hoy());
const fechaTocada = ref(false);

function fechaArgentina(fecha?: string) {
  if (!fecha) return '';
  const [year, month, day] = fecha.split('-');
  return year && month && day ? `${day}/${month}/${year}` : fecha;
}

function fechaIso(fecha: string) {
  const match = fecha.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
  if (!match) return null;

  const [, day, month, year] = match;
  const parsed = new Date(
    Date.UTC(Number(year), Number(month) - 1, Number(day)),
  );
  if (
    parsed.getUTCFullYear() !== Number(year) ||
    parsed.getUTCMonth() + 1 !== Number(month) ||
    parsed.getUTCDate() !== Number(day)
  )
    return null;

  return `${year}-${month}-${day}`;
}

function actualizarFecha(event: Event) {
  const input = event.target as HTMLInputElement;
  const digits = input.value.replace(/\D/g, '').slice(0, 8);
  fechaVisible.value =
    digits.length <= 2
      ? digits
      : digits.length <= 4
        ? `${digits.slice(0, 2)}/${digits.slice(2)}`
        : `${digits.slice(0, 2)}/${digits.slice(2, 4)}/${digits.slice(4)}`;
  form.fecha_cobro = fechaIso(fechaVisible.value) || '';
}

const errorFecha = computed(() => {
  if (!fechaVisible.value) return 'Ingresá la fecha de cobro.';
  if (!form.fecha_cobro)
    return 'Ingresá una fecha válida en formato DD/MM/AAAA.';
  if (form.fecha_cobro > fechaMaxima.value)
    return `La fecha no puede ser posterior al ${fechaArgentina(fechaMaxima.value)}.`;
  return '';
});

const ayudaFecha = computed(() => {
  const hasta = fechaArgentina(fechaMaxima.value);
  return `Se permite cualquier fecha hasta el ${hasta}.`;
});
const visible = computed({
  get: () => open.value,
  set: (value) => {
    if (!form.processing) open.value = value;
  },
});
watch(
  open,
  (value) => {
    if (value) {
      form.reset();
      form.clearErrors();
      fechaMaxima.value = hoy();
      form.fecha_cobro = fechaMaxima.value;
      fechaVisible.value = fechaArgentina(form.fecha_cobro);
      fechaTocada.value = false;
      form.total_esperado = props.total;
    }
  },
  { immediate: true },
);
function guardar() {
  fechaTocada.value = true;
  if (errorFecha.value) return;

  form
    .transform((data) => ({ ...props.payload, ...data }))
    .post(props.endpoint, {
      preserveScroll: true,
      onSuccess: () => {
        open.value = false;
        emit('guardado');
      },
    });
}
</script>
<template>
  <Dialog v-model:open="visible">
    <DialogContent class="max-h-[90dvh] overflow-y-auto dark:bg-zinc-900">
      <DialogHeader>
        <DialogTitle>{{ titulo || 'Registrar cobro' }}</DialogTitle>
        <DialogDescription>{{
          descripcion ||
          'Confirmá el importe recibido, la fecha y el medio de pago.'
        }}</DialogDescription>
      </DialogHeader>
      <form class="space-y-5" @submit.prevent="guardar">
        <div class="rounded-lg border bg-muted/50 p-4">
          <dl
            v-if="conceptos?.length"
            class="mb-3 space-y-2 border-b pb-3 text-sm"
          >
            <div
              v-for="(concepto, i) in conceptos"
              :key="i"
              class="flex justify-between gap-4"
            >
              <dt>{{ concepto.nombre }}</dt>
              <dd class="whitespace-nowrap tabular-nums">
                {{ moneda(concepto.total) }}
              </dd>
            </div>
          </dl>
          <div class="flex justify-between gap-3 font-semibold">
            <span>Total a cobrar</span
            ><strong class="text-xl tabular-nums">{{ moneda(total) }}</strong>
          </div>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
          <div class="space-y-2">
            <Label for="cobro-fecha">Fecha de cobro</Label
            ><Input
              id="cobro-fecha"
              :model-value="fechaVisible"
              type="text"
              inputmode="numeric"
              placeholder="DD/MM/AAAA"
              maxlength="10"
              autocomplete="off"
              :aria-invalid="fechaTocada && !!errorFecha"
              aria-describedby="cobro-fecha-ayuda cobro-fecha-error"
              @input="actualizarFecha"
              @blur="fechaTocada = true"
            />
            <p id="cobro-fecha-ayuda" class="text-xs text-muted-foreground">
              {{ ayudaFecha }} Formato: DD/MM/AAAA.
            </p>
            <p
              v-if="fechaTocada && errorFecha"
              id="cobro-fecha-error"
              class="text-xs text-destructive"
            >
              {{ errorFecha }}
            </p>
          </div>
          <div class="space-y-2">
            <Label for="cobro-medio">Medio de pago</Label
            ><Input
              id="cobro-medio"
              v-model="form.medio_pago"
              list="medios-cobro"
              placeholder="Ej.: Efectivo"
              maxlength="100"
              required
            /><datalist id="medios-cobro">
              <option>Efectivo</option>
              <option>Transferencia</option>
              <option>Tarjeta</option>
              <option>Mercado Pago</option>
            </datalist>
          </div>
        </div>
        <div
          v-if="Object.keys(form.errors).length"
          role="alert"
          class="space-y-1 text-sm text-destructive"
        >
          <p v-for="(error, key) in form.errors" :key="key">{{ error }}</p>
        </div>
        <div class="flex flex-wrap justify-end gap-2 border-t pt-4">
          <Button
            type="button"
            variant="outline"
            :disabled="form.processing"
            @click="visible = false"
            >Cancelar</Button
          >
          <Button type="submit" :disabled="form.processing">{{
            form.processing ? 'Guardando…' : confirmar || 'Confirmar cobro'
          }}</Button>
        </div>
      </form>
    </DialogContent>
  </Dialog>
</template>
