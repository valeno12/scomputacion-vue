<script setup lang="ts">
import FormCard from '@/components/common/FormCard.vue';
import BuscadorProductosIngreso from '@/components/productos/BuscadorProductosIngreso.vue';
import CrearProductoDialog from '@/components/productos/CrearProductoDialog.vue';
import IngresoStockFields from '@/components/productos/IngresoStockFields.vue';
import RevisarIngreso from '@/components/productos/RevisarIngreso.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import FormLayout from '@/layouts/FormLayout.vue';
import productosRoutes from '@/routes/productos_nuevo';
import { hoy, nuevaClave, type Participante } from '@/types/comercio';
import type { ProductoNuevo } from '@/types/producto-nuevo.interface';
import { formatMoney } from '@/utils/formatter';
import { porcentajeInicial, precioProducto } from '@/utils/precioProducto';
import { Head, router, useForm } from '@inertiajs/vue3';
import { PackagePlus, Trash2 } from 'lucide-vue-next';
import { computed, nextTick, provide, ref } from 'vue';
import { toast } from 'vue-sonner';

const props = defineProps<{
  participantes: Participante[];
  productosIniciales: ProductoNuevo[];
}>();
interface FilaIngreso {
  uid: string;
  producto_id: number;
  nombre: string;
  marca: string | null;
  cantidad: number;
  costo: number | string;
  porcentaje_ganancia: number | string;
}
const form = useForm({
  clave: nuevaClave(),
  comprador_id: '' as number | '',
  proveedor: '',
  fecha: hoy(),
  tipo: 'compra',
  items: props.productosIniciales.map(filaIngreso),
});
provide('ingresoProductoForm', form);
const reviewing = ref(false);
const showCreate = ref(false);
const nombreNuevo = ref('');
const catalogRevision = ref(0);
const errors = computed(() => form.errors as Record<string, string>);
const seleccionados = computed(() =>
  form.items.map((item) => item.producto_id),
);
const comprador = computed(
  () =>
    props.participantes.find((p) => p.id === form.comprador_id)?.nombre ?? '',
);
const totalCosto = computed(() =>
  form.items.reduce(
    (total, item) =>
      total + Math.round(Number(item.costo) * 100) * Number(item.cantidad),
    0,
  ),
);
const unidades = computed(() =>
  form.items.reduce((total, item) => total + Number(item.cantidad), 0),
);
function filaIngreso(producto: ProductoNuevo): FilaIngreso {
  const costo =
    producto.costo_unitario_centavos ??
    producto.costo_referencia_centavos ??
    null;
  const uid = nuevaClave();
  return {
    uid,
    producto_id: producto.id,
    nombre: producto.nombre,
    marca: producto.marca,
    cantidad: 1,
    costo: costo === null ? '' : costo / 100,
    porcentaje_ganancia: porcentajeInicial(
      producto.porcentaje_ganancia,
      costo,
      producto.precio_venta_centavos,
    ),
  };
}
function agregar(producto: ProductoNuevo) {
  if (
    form.processing ||
    seleccionados.value.includes(producto.id) ||
    form.items.length >= 100
  )
    return;
  const fila = filaIngreso(producto);
  form.items.push(fila);
  form.clearErrors();
  nextTick(() => document.getElementById(`cantidad-${fila.uid}`)?.focus());
}
function crear(nombre: string) {
  nombreNuevo.value = nombre;
  showCreate.value = true;
}
function creado(producto: ProductoNuevo) {
  agregar(producto);
  catalogRevision.value++;
  toast.success('Producto creado y agregado al ingreso');
}
function quitar(index: number) {
  form.items.splice(index, 1);
  form.clearErrors();
}
function guardar() {
  if (!form.items.length || form.processing) return;
  form
    .transform((data) => ({
      ...data,
      items: data.items.map((item) => ({
        producto_id: item.producto_id,
        cantidad: item.cantidad,
        costo: item.costo,
        porcentaje_ganancia: item.porcentaje_ganancia,
      })),
    }))
    .post(productosRoutes.ingresos.multiple().url, {
      onSuccess: () => toast.success('Ingreso registrado'),
      onError: () => {
        reviewing.value = false;
      },
    });
}
</script>
<template>
  <Head title="Nuevo ingreso" />
  <AppLayout
    :breadcrumbs="[
      { title: 'Productos', href: productosRoutes.index().url },
      { title: 'Nuevo ingreso', href: productosRoutes.ingresos.create().url },
    ]"
  >
    <FormLayout
      wide
      title="Nuevo ingreso"
      description="Buscá o creá los productos, completá los importes y revisá la compra antes de confirmar."
    >
      <FormCard
        card-title="Datos de la compra"
        card-description="Comprador, proveedor y fecha se aplican a todos los productos de este ingreso."
        entity-name="Ingreso"
        submit-label="Revisar ingreso"
        :is-submitting="form.processing"
        :is-dirty="form.items.length > 0"
        @submit="reviewing = true"
        @cancel="router.visit(productosRoutes.index().url)"
      >
        <IngresoStockFields
          wide
          :participantes="participantes"
          :show-cantidad="false"
          :show-costo="false"
        />
        <div class="border-t pt-5">
          <BuscadorProductosIngreso
            :seleccionados="seleccionados"
            :disabled="form.processing || form.items.length >= 100"
            :refresh-key="catalogRevision"
            @agregar="agregar"
            @crear="crear"
          />
        </div>
        <div class="flex flex-wrap items-center justify-between gap-2 pt-3">
          <h2 class="font-semibold">
            Productos del ingreso
            <span v-if="form.items.length" class="ml-1 text-muted-foreground"
              >({{ form.items.length }})</span
            >
          </h2>
          <p class="text-sm text-muted-foreground">
            El porcentaje se suma al costo para calcular el precio de venta.
          </p>
        </div>
        <p
          v-if="form.items.length >= 100"
          class="text-sm text-muted-foreground"
        >
          Este ingreso alcanzó el máximo de 100 productos.
        </p>
        <div
          v-if="!form.items.length"
          class="flex items-center gap-3 rounded-lg border border-dashed p-6 text-muted-foreground"
        >
          <PackagePlus class="size-6 shrink-0" />
          <p class="text-sm">
            Buscá y agregá un producto de la lista de arriba, o creá uno nuevo
            para empezar el ingreso.
          </p>
        </div>
        <div v-else class="overflow-x-auto rounded-lg border">
          <table class="w-full min-w-[850px] text-sm">
            <thead class="bg-muted/50">
              <tr>
                <th class="p-3 text-left">Producto</th>
                <th class="w-28 p-3 text-left">Cantidad</th>
                <th class="w-40 p-3 text-left">Costo unitario</th>
                <th class="w-36 p-3 text-left whitespace-nowrap">Ganancia %</th>
                <th class="w-40 p-3 text-right whitespace-nowrap">
                  Venta unit.
                </th>
                <th class="w-40 p-3 text-right whitespace-nowrap">
                  Costo total
                </th>
                <th class="w-14 p-3"><span class="sr-only">Quitar</span></th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(item, index) in form.items"
                :key="item.uid"
                class="border-t align-top"
              >
                <td class="min-w-56 p-3">
                  <span class="font-medium">{{ item.nombre }}</span
                  ><span class="mt-1 block text-xs text-muted-foreground">{{
                    item.marca
                  }}</span>
                  <p class="mt-1 text-xs text-destructive">
                    {{ errors[`items.${index}.producto_id`] }}
                  </p>
                </td>
                <td class="p-2">
                  <Input
                    :id="`cantidad-${item.uid}`"
                    v-model.number="item.cantidad"
                    :aria-label="`Cantidad de ${item.nombre}`"
                    type="number"
                    min="1"
                    max="100000"
                    step="1"
                    :disabled="form.processing"
                    required
                  />
                  <p class="mt-1 text-xs text-destructive">
                    {{ errors[`items.${index}.cantidad`] }}
                  </p>
                </td>
                <td class="p-2">
                  <Input
                    v-model.number="item.costo"
                    :aria-label="`Costo unitario de ${item.nombre}`"
                    type="number"
                    min="0"
                    max="999999999"
                    step="0.01"
                    :disabled="form.processing"
                    required
                  />
                  <p class="mt-1 text-xs text-destructive">
                    {{ errors[`items.${index}.costo`] }}
                  </p>
                </td>
                <td class="p-2">
                  <Input
                    v-model.number="item.porcentaje_ganancia"
                    :aria-label="`Ganancia sobre costo de ${item.nombre}`"
                    type="number"
                    min="0"
                    max="10000"
                    step="0.01"
                    placeholder="Ej.: 40"
                    :disabled="form.processing"
                    required
                  />
                  <p class="mt-1 text-xs text-destructive">
                    {{ errors[`items.${index}.porcentaje_ganancia`] }}
                  </p>
                </td>
                <td
                  class="p-3 text-right font-medium whitespace-nowrap text-blue-700 dark:text-blue-300"
                >
                  {{
                    item.costo === '' || item.porcentaje_ganancia === ''
                      ? '—'
                      : formatMoney(
                          precioProducto(item.costo, item.porcentaje_ganancia) /
                            100,
                        )
                  }}
                </td>
                <td class="p-3 text-right whitespace-nowrap">
                  {{
                    item.costo === ''
                      ? '—'
                      : formatMoney(
                          (Math.round(Number(item.costo) * 100) *
                            Number(item.cantidad)) /
                            100,
                        )
                  }}
                </td>
                <td class="p-2">
                  <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    :aria-label="`Quitar ${item.nombre}`"
                    :disabled="form.processing"
                    @click="quitar(index)"
                    ><Trash2 class="size-4"
                  /></Button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <p
          v-if="errors.items || errors.clave"
          role="alert"
          class="text-sm text-destructive"
        >
          {{ errors.items || errors.clave }}
        </p>
        <div
          class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-500/25 dark:bg-blue-500/10"
        >
          <div>
            <p class="font-medium">Costo total del ingreso</p>
            <p class="text-sm text-muted-foreground">
              {{ form.items.length }} productos · {{ unidades }} unidades
            </p>
          </div>
          <strong class="text-xl">{{ formatMoney(totalCosto / 100) }}</strong>
        </div>
      </FormCard>
    </FormLayout>
  </AppLayout>
  <CrearProductoDialog
    v-model:open="showCreate"
    :nombre-inicial="nombreNuevo"
    @creado="creado"
  />
  <RevisarIngreso
    v-model:open="reviewing"
    :items="form.items"
    :comprador="comprador"
    :proveedor="form.proveedor"
    :fecha="form.fecha"
    :tipo="form.tipo"
    :processing="form.processing"
    @confirmar="guardar"
  />
</template>
