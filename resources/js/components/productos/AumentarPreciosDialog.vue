<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import productos from '@/routes/productos_nuevo';
import type { ProductoNuevo } from '@/types/producto-nuevo.interface';
import { formatMoney } from '@/utils/formatter';
import { precioProducto } from '@/utils/precioProducto';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { Loader2, RefreshCw, X } from 'lucide-vue-next';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { toast } from 'vue-sonner';

const open = defineModel<boolean>('open', { required: true });
const props = defineProps<{ ids: number[] }>();
const emit = defineEmits<{ actualizado: []; quitar: [id: number] }>();
const lista = ref<ProductoNuevo[]>([]);
const loading = ref(false);
const cargaError = ref('');
const form = useForm({
  porcentaje: '' as string | number,
  items: [] as { producto_id: number; precio_actual_centavos: number }[],
});
const errors = computed(() => form.errors as Record<string, string>);
const porcentajeValido = computed(
  () =>
    /^\d+(\.\d{1,2})?$/.test(String(form.porcentaje)) &&
    Number(form.porcentaje) > 0 &&
    Number(form.porcentaje) <= 10000,
);
const filas = computed(() =>
  lista.value.map((producto) => ({
    ...producto,
    nuevo: porcentajeValido.value
      ? precioProducto(producto.precio_venta_centavos / 100, form.porcentaje)
      : null,
  })),
);
const puedeGuardar = computed(
  () =>
    !loading.value &&
    !cargaError.value &&
    !form.processing &&
    porcentajeValido.value &&
    filas.value.length > 0 &&
    filas.value.every(
      (p) =>
        p.precio_venta_centavos > 0 &&
        p.nuevo !== null &&
        p.nuevo > p.precio_venta_centavos &&
        p.nuevo <= 99999999900,
    ),
);
let controller: AbortController | undefined;
let generation = 0;
async function cargar() {
  const current = ++generation;
  controller?.abort();
  controller = new AbortController();
  loading.value = true;
  cargaError.value = '';
  form.clearErrors();
  try {
    const { data } = await axios.get<{ data: ProductoNuevo[] }>(
      productos.seleccion().url,
      {
        params: { productos: props.ids },
        signal: controller.signal,
      },
    );
    if (current !== generation) return;
    lista.value = data.data;
    form.items = data.data.map((p) => ({
      producto_id: p.id,
      precio_actual_centavos: p.precio_venta_centavos,
    }));
  } catch (error) {
    if (current !== generation || axios.isCancel(error)) return;
    cargaError.value =
      axios.isAxiosError(error) && error.response?.status === 422
        ? 'Un producto ya no está disponible. Cerrá esta revisión y actualizá la selección.'
        : 'No se pudieron consultar los precios. Volvé a intentarlo.';
  } finally {
    if (current === generation) loading.value = false;
  }
}
watch(open, (value) => {
  if (value) {
    form.reset();
    lista.value = [];
    cargar();
  } else {
    ++generation;
    controller?.abort();
  }
});
onBeforeUnmount(() => {
  ++generation;
  controller?.abort();
});
function quitar(id: number) {
  lista.value = lista.value.filter((p) => p.id !== id);
  form.items = form.items.filter((p) => p.producto_id !== id);
  form.clearErrors();
  emit('quitar', id);
  if (!lista.value.length) open.value = false;
}
function guardar() {
  if (!puedeGuardar.value) return;
  form.post(productos.precios.update().url, {
    preserveScroll: true,
    onSuccess: () => {
      open.value = false;
      emit('actualizado');
      toast.success('Precios de venta actualizados');
    },
  });
}
</script>
<template>
  <Dialog
    :open="open"
    @update:open="
      (value) => {
        if (!form.processing) open = value;
      }
    "
  >
    <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-4xl">
      <DialogHeader>
        <DialogTitle>Aumentar precios de venta</DialogTitle>
        <DialogDescription
          >Indicá el aumento sobre el precio de venta actual y revisá cómo queda
          cada producto.</DialogDescription
        >
      </DialogHeader>
      <form class="min-w-0 space-y-5" @submit.prevent="guardar">
        <div
          class="flex flex-col gap-3 rounded-lg border border-blue-200 bg-blue-50 p-4 sm:flex-row sm:items-end dark:border-blue-500/25 dark:bg-blue-500/10"
        >
          <div class="w-full space-y-2 sm:w-48">
            <Label for="aumento-precio">Aumento %</Label>
            <div class="relative">
              <Input
                id="aumento-precio"
                v-model="form.porcentaje"
                type="number"
                min="0.01"
                max="10000"
                step="0.01"
                required
                placeholder="Ej.: 10"
                class="bg-background pr-8"
                :disabled="loading || form.processing"
              />
              <span
                class="pointer-events-none absolute top-2 right-3 text-muted-foreground"
                >%</span
              >
            </div>
          </div>
          <p class="text-sm text-muted-foreground">
            Por ejemplo, un precio de $10.000 con un 10 % de aumento queda en
            $11.000.
          </p>
        </div>
        <p
          v-if="errors.porcentaje"
          role="alert"
          class="text-sm text-destructive"
        >
          {{ errors.porcentaje }}
        </p>
        <div
          v-if="loading"
          role="status"
          class="flex items-center gap-2 py-8 text-sm text-muted-foreground"
        >
          <Loader2 class="size-4 animate-spin" /> Consultando precios actuales…
        </div>
        <div v-else-if="cargaError" role="alert" class="space-y-3">
          <p class="text-sm text-destructive">{{ cargaError }}</p>
          <Button type="button" variant="outline" @click="cargar"
            >Reintentar</Button
          >
        </div>
        <template v-else>
          <div class="max-h-[45vh] overflow-auto rounded-lg border">
            <table class="w-full table-fixed text-sm sm:table-auto">
              <thead class="sticky top-0 bg-muted">
                <tr>
                  <th class="w-[35%] p-2 text-left sm:w-auto sm:p-3">
                    Producto
                  </th>
                  <th
                    class="w-1/4 p-2 text-right text-xs sm:w-auto sm:p-3 sm:text-sm"
                  >
                    Precio actual
                  </th>
                  <th
                    class="w-1/4 p-2 text-right text-xs sm:w-auto sm:p-3 sm:text-sm"
                  >
                    Nuevo precio
                  </th>
                  <th class="w-[15%] p-2 sm:w-auto sm:p-3">
                    <span class="sr-only">Quitar</span>
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(producto, index) in filas"
                  :key="producto.id"
                  class="border-t align-top"
                >
                  <td class="p-2 break-words sm:p-3">
                    <span class="font-medium">{{ producto.nombre }}</span
                    ><span class="block text-xs text-muted-foreground">{{
                      producto.marca
                    }}</span>
                    <p
                      v-if="errors[`items.${index}.producto_id`]"
                      class="mt-1 text-xs text-destructive"
                    >
                      {{ errors[`items.${index}.producto_id`] }}
                    </p>
                    <p
                      v-if="producto.precio_venta_centavos <= 0"
                      class="mt-1 text-xs text-destructive"
                    >
                      Registrá su ingreso primero o quitalo de esta
                      actualización.
                    </p>
                    <p
                      v-else-if="
                        producto.nuevo !== null &&
                        producto.nuevo <= producto.precio_venta_centavos
                      "
                      class="mt-1 text-xs text-destructive"
                    >
                      El aumento debe alcanzar al menos un centavo.
                    </p>
                    <p
                      v-else-if="
                        producto.nuevo !== null && producto.nuevo > 99999999900
                      "
                      class="mt-1 text-xs text-destructive"
                    >
                      El precio calculado supera el máximo permitido.
                    </p>
                  </td>
                  <td
                    class="p-2 text-right text-xs whitespace-nowrap sm:p-3 sm:text-sm"
                  >
                    {{
                      producto.precio_venta_centavos > 0
                        ? formatMoney(producto.precio_venta_centavos / 100)
                        : 'Sin precio'
                    }}
                  </td>
                  <td
                    class="p-2 text-right text-xs font-semibold whitespace-nowrap text-emerald-700 sm:p-3 sm:text-sm dark:text-emerald-300"
                  >
                    {{
                      producto.nuevo !== null &&
                      producto.precio_venta_centavos > 0
                        ? formatMoney(producto.nuevo / 100)
                        : '—'
                    }}
                  </td>
                  <td class="p-2">
                    <Button
                      type="button"
                      variant="ghost"
                      size="icon"
                      class="size-8 sm:size-9"
                      :aria-label="`Quitar ${producto.nombre} del aumento`"
                      :disabled="form.processing"
                      @click="quitar(producto.id)"
                      ><X class="size-4"
                    /></Button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div
            v-if="Object.keys(errors).some((key) => key.startsWith('items'))"
            role="alert"
            class="flex flex-wrap items-center justify-between gap-3 text-sm"
          >
            <p>
              {{
                errors.items ||
                'No se aplicó ningún aumento. Revisá los productos indicados.'
              }}
            </p>
            <Button
              type="button"
              variant="outline"
              :disabled="form.processing"
              @click="cargar"
              ><RefreshCw class="size-4" /> Actualizar revisión</Button
            >
          </div>
        </template>
        <p class="text-sm text-muted-foreground">
          Se actualizará el precio para próximas ventas. Los costos de los
          ingresos, las ventas y los pedidos ya registrados conservan sus
          importes.
        </p>
        <DialogFooter>
          <Button
            type="button"
            variant="outline"
            :disabled="form.processing"
            @click="open = false"
            >Cancelar</Button
          >
          <Button type="submit" :disabled="!puedeGuardar">{{
            form.processing
              ? 'Actualizando…'
              : `Confirmar aumento · ${lista.length} ${lista.length === 1 ? 'producto' : 'productos'}`
          }}</Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>
