<script setup lang="ts">
import CobroDialog from '@/components/comercio/CobroDialog.vue';
import OperacionDetalle from '@/components/comercio/OperacionDetalle.vue';
import VentaDistribucionTable from '@/components/comercio/VentaDistribucionTable.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import FormLayout from '@/layouts/FormLayout.vue';
import { moneda, type Operacion } from '@/types/comercio';
import { formatDate } from '@/utils/formatter';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import { computed, ref } from 'vue';
const props = defineProps<{ operacion: Operacion }>();
const form = useForm({});
const confirmarAnulacion = ref(false);
const cobrar = ref(false);
const page = usePage();
const desdePedido = computed(
  () =>
    page.url.includes('desde=pedido') &&
    props.operacion.pedido &&
    !props.operacion.pedido.deleted_at,
);
const fecha = (value: string) => formatDate(value, { includeTime: false });
</script>
<template>
  <Head :title="`Venta V${operacion.id}`" />
  <AppLayout
    :breadcrumbs="[
      { title: 'Ventas', href: '/comercio/ventas' },
      { title: 'V' + operacion.id, href: '#' },
    ]"
  >
    <FormLayout
      wide
      :title="`Venta V${operacion.id}`"
      description="Productos, cobro y reparto de esta venta."
    >
      <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <Button variant="outline" as-child
          ><Link
            :href="
              desdePedido
                ? `/Pedido/${operacion.pedido!.id}`
                : '/comercio/ventas'
            "
            ><ArrowLeft class="size-4" />{{
              desdePedido
                ? 'Volver al pedido ' + operacion.pedido!.codigo
                : 'Volver a ventas'
            }}</Link
          ></Button
        >
        <Button
          v-if="operacion.estado !== 'anulada' && !operacion.fecha_cobro"
          @click="cobrar = true"
          >Registrar cobro</Button
        >
      </div>
      <div class="mb-6 rounded-xl border bg-card p-5">
        <div class="flex flex-wrap justify-between gap-5">
          <div class="space-y-2 text-sm">
            <p class="font-semibold">
              {{
                operacion.cliente
                  ? operacion.cliente.nombre + ' ' + operacion.cliente.apellido
                  : 'Consumidor final'
              }}
            </p>
            <p class="text-muted-foreground">
              Venta del {{ fecha(operacion.fecha)
              }}<span v-if="operacion.registrador">
                · Registró {{ operacion.registrador.name }}</span
              >
            </p>
            <Link
              v-if="operacion.pedido && !operacion.pedido.deleted_at"
              class="text-blue-600 hover:underline dark:text-blue-300"
              :href="`/Pedido/${operacion.pedido.id}`"
              >Asociada al pedido {{ operacion.pedido.codigo }}</Link
            >
            <p v-else class="text-muted-foreground">
              {{
                operacion.pedido?.deleted_at
                  ? 'Pedido eliminado · venta conservada'
                  : 'Venta directa'
              }}
            </p>
          </div>
          <div class="text-right">
            <p class="text-sm text-muted-foreground">Total de la venta</p>
            <strong class="mt-1 block text-2xl tabular-nums">{{
              moneda(operacion.total_centavos)
            }}</strong>
            <p
              v-if="operacion.estado === 'anulada'"
              class="mt-2 text-sm text-destructive"
            >
              Venta anulada
            </p>
            <p
              v-else-if="operacion.fecha_cobro"
              class="mt-2 text-sm text-emerald-700 dark:text-emerald-300"
            >
              Cobrada el {{ fecha(operacion.fecha_cobro) }} ·
              {{ operacion.medio_pago || 'Sin medio registrado' }}
            </p>
            <p v-else class="mt-2 text-sm text-amber-700 dark:text-amber-300">
              Pendiente de cobro
            </p>
          </div>
        </div>
      </div>
      <div
        v-if="operacion.tipo === 'venta'"
        class="mb-6 overflow-hidden rounded-xl border bg-card"
      >
        <VentaDistribucionTable :items="operacion.items" />
      </div>
      <OperacionDetalle v-else :operacion="operacion" ocultar-reparto />
      <details class="mb-6 rounded-lg border p-4 text-sm">
        <summary class="cursor-pointer font-medium">
          Ver costos y porcentajes guardados
        </summary>
        <div
          v-for="item in operacion.items"
          :key="item.id"
          class="mt-3 space-y-1 border-t pt-3"
        >
          <p class="font-medium">{{ item.descripcion }}</p>
          <p>Costo por unidad: {{ moneda(item.costo_unitario_centavos) }}</p>
          <p class="text-muted-foreground">
            Ganancia:
            {{
              item.reparto
                .map((r) => r.nombre + ' ' + r.porcentaje + '%')
                .join(' · ')
            }}
          </p>
        </div>
      </details>
      <div
        v-if="operacion.tipo === 'venta' && operacion.estado !== 'anulada'"
        class="rounded-lg border p-4"
      >
        <Button
          v-if="!confirmarAnulacion"
          variant="outline"
          @click="confirmarAnulacion = true"
          >Anular venta</Button
        >
        <div v-else class="space-y-3 text-sm">
          <p>
            La venta dejará de sumar al pedido y a rendimientos. Las unidades
            descontadas volverán al inventario. Si ya se cobró, confirmá también
            la devolución del dinero y la mercadería.
          </p>
          <div class="flex gap-3">
            <Button
              :disabled="form.processing"
              @click="
                form.post(`/comercio/operaciones/${operacion.id}/anular`, {
                  onSuccess: () => (confirmarAnulacion = false),
                })
              "
              >Confirmar anulación</Button
            ><Button variant="outline" @click="confirmarAnulacion = false"
              >Cancelar</Button
            >
          </div>
        </div>
      </div>
      <CobroDialog
        v-model:open="cobrar"
        :endpoint="`/comercio/operaciones/${operacion.id}/cobrar`"
        :total="operacion.total_centavos"
        :minimo="operacion.fecha"
      />
    </FormLayout>
  </AppLayout>
</template>
