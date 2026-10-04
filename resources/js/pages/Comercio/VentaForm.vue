<script setup lang="ts">
import ClienteSelector from '@/components/clientes/ClienteSelector.vue';
import CobroDialog from '@/components/comercio/CobroDialog.vue';
import ItemsEditor from '@/components/comercio/ItemsEditor.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import FormLayout from '@/layouts/FormLayout.vue';
import {
  hoy,
  nuevaClave,
  type ItemForm,
  type OpcionesComercio,
} from '@/types/comercio';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import { computed, ref } from 'vue';
const props = defineProps<OpcionesComercio>();
const form = useForm({
  clave: nuevaClave(),
  cliente_id: null as number | null,
  fecha: hoy(),
  items: [] as ItemForm[],
});
const cobrar = ref(false);
const total = computed(() =>
  form.items.reduce(
    (sum, item) =>
      sum + Math.round(Number(item.precio) * 100) * Number(item.cantidad),
    0,
  ),
);
</script>
<template>
  <Head title="Registrar venta" />
  <AppLayout
    :breadcrumbs="[
      { title: 'Ventas', href: '/comercio/ventas' },
      { title: 'Registrar venta', href: '#' },
    ]"
  >
    <FormLayout
      title="Registrar venta"
      description="Buscá los productos y elegí si queda pendiente o se cobra ahora."
    >
      <Button variant="ghost" class="mb-4" as-child
        ><Link href="/comercio/ventas"
          ><ArrowLeft class="size-4" />Volver a ventas</Link
        ></Button
      >
      <form
        class="space-y-6 rounded-xl border bg-card p-5 sm:p-6"
        @submit.prevent="cobrar = true"
      >
        <div class="grid gap-5 sm:grid-cols-[2fr_1fr]">
          <div class="space-y-2">
            <Label>Cliente (opcional)</Label
            ><ClienteSelector v-model="form.cliente_id" />
            <p v-if="!form.cliente_id" class="text-xs text-muted-foreground">
              Consumidor final
            </p>
            <Button
              v-else
              type="button"
              variant="link"
              class="h-auto p-0 text-xs"
              @click="form.cliente_id = null"
              >Usar consumidor final</Button
            ><InputError :message="form.errors.cliente_id" />
          </div>
          <div class="space-y-2">
            <Label for="sale-date">Fecha de venta</Label
            ><Input
              id="sale-date"
              v-model="form.fecha"
              type="date"
              :max="hoy()"
              required
            /><InputError :message="form.errors.fecha" />
          </div>
        </div>
        <div class="border-t pt-5">
          <ItemsEditor
            v-model="form.items"
            v-bind="props"
            :errores="form.errors"
            solo-stock
          />
        </div>
        <div
          v-if="Object.keys(form.errors).length"
          role="alert"
          class="text-sm text-destructive"
        >
          <p v-for="(error, key) in form.errors" :key="key">{{ error }}</p>
        </div>
        <p class="text-xs text-muted-foreground">
          Al guardar se descuentan las unidades del inventario. La ganancia se
          suma a rendimientos cuando se registra el cobro.
        </p>
        <div class="flex flex-wrap justify-end gap-3 border-t pt-5">
          <Button
            type="button"
            variant="outline"
            :disabled="form.processing || !form.items.length"
            @click="form.post('/comercio/ventas')"
            >Guardar pendiente</Button
          >
          <Button
            type="submit"
            :disabled="form.processing || !form.items.length"
            >Confirmar venta y cobro</Button
          >
        </div>
      </form>
      <CobroDialog
        v-model:open="cobrar"
        endpoint="/comercio/ventas"
        titulo="Confirmar venta y cobro"
        confirmar="Guardar venta cobrada"
        :total="total"
        :minimo="form.fecha"
        :payload="{ ...form.data(), cobrar: true }"
      />
    </FormLayout>
  </AppLayout>
</template>
