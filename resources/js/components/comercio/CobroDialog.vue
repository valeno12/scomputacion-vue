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
import { computed, watch } from 'vue';
const open = defineModel<boolean>('open', { required: true });
const props = defineProps<{
  endpoint: string;
  titulo?: string;
  descripcion?: string;
  confirmar?: string;
  total: number;
  minimo?: string;
  conceptos?: { nombre: string; total: number }[];
  payload?: Record<string, any>;
}>();
const emit = defineEmits<{ guardado: [] }>();
const form = useForm({ fecha_cobro: hoy(), medio_pago: '', total_esperado: 0 });
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
      form.total_esperado = props.total;
    }
  },
  { immediate: true },
);
function guardar() {
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
              v-model="form.fecha_cobro"
              type="date"
              :min="minimo"
              :max="hoy()"
              required
            />
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
