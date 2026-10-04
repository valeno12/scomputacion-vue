<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import {
  hoy,
  moneda,
  nuevaClave,
  type ItemForm,
  type OpcionesComercio,
} from '@/types/comercio';
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import ItemsEditor from './ItemsEditor.vue';
const open = defineModel<boolean>('open', { required: true });
const props = defineProps<{
  pedidoId: number;
  codigo: string;
  saldo: number;
  opciones: OpcionesComercio;
}>();
const form = useForm({
  clave: nuevaClave(),
  pedido_id: props.pedidoId,
  fecha: hoy(),
  items: [] as ItemForm[],
});
const total = computed(() =>
  form.items.reduce(
    (sum, row) =>
      sum + Math.round(Number(row.precio) * 100) * Number(row.cantidad),
    0,
  ),
);
const visible = computed({
  get: () => open.value,
  set: (value) => {
    if (!form.processing) open.value = value;
  },
});
watch(open, (value) => {
  if (value) {
    form.reset();
    form.clearErrors();
    form.clave = nuevaClave();
    form.fecha = hoy();
  }
});
function guardar() {
  form.post('/comercio/ventas', {
    preserveScroll: true,
    onSuccess: () => {
      open.value = false;
    },
  });
}
</script>
<template>
  <Dialog v-model:open="visible">
    <DialogContent
      class="flex max-h-[92dvh] flex-col gap-0 overflow-hidden p-0 sm:max-w-3xl dark:bg-zinc-900"
    >
      <DialogHeader class="border-b p-5 pr-12">
        <DialogTitle>Agregar productos al pedido {{ codigo }}</DialogTitle>
        <DialogDescription
          >Buscá los productos del inventario y elegí las cantidades. Se
          agregarán como una venta pendiente de cobro.</DialogDescription
        >
      </DialogHeader>
      <form class="flex min-h-0 flex-col" @submit.prevent="guardar">
        <div class="min-h-0 space-y-4 overflow-y-auto p-5">
          <ItemsEditor
            v-model="form.items"
            v-bind="opciones"
            :errores="form.errors"
            solo-stock
            ocultar-total
          />
          <div
            v-if="Object.keys(form.errors).length"
            role="alert"
            class="text-sm text-destructive"
          >
            <p v-for="(error, key) in form.errors" :key="key">{{ error }}</p>
          </div>
        </div>
        <div class="space-y-4 border-t bg-muted/40 p-5">
          <dl class="space-y-2 text-sm">
            <div class="flex justify-between gap-3 font-semibold">
              <dt>Productos a agregar</dt>
              <dd>{{ moneda(total) }}</dd>
            </div>
            <div class="flex justify-between gap-3 text-muted-foreground">
              <dt>Saldo del pedido después de agregar</dt>
              <dd>{{ moneda(saldo + total) }}</dd>
            </div>
          </dl>
          <div class="flex flex-wrap justify-end gap-2">
            <Button
              type="button"
              variant="outline"
              :disabled="form.processing"
              @click="visible = false"
              >Cancelar</Button
            ><Button
              type="submit"
              :disabled="form.processing || !form.items.length"
              >{{
                form.processing ? 'Agregando…' : 'Agregar al pedido'
              }}</Button
            >
          </div>
        </div>
      </form>
    </DialogContent>
  </Dialog>
</template>
