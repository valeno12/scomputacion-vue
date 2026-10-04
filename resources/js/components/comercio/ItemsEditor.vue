<script setup lang="ts">
import BuscadorSelector from '@/components/common/BuscadorSelector.vue';
import ProveedorAutocomplete from '@/components/proveedores/ProveedorAutocomplete.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  moneda,
  nuevaClave,
  type Articulo,
  type ItemForm,
  type OpcionesComercio,
} from '@/types/comercio';
import { precioItemCentavos as price } from '@/utils/importesPedido';
import { ChevronDown, Plus, X } from 'lucide-vue-next';
import { computed, nextTick, ref } from 'vue';
const props = defineProps<
  OpcionesComercio & {
    soloStock?: boolean;
    errores?: Record<string, string>;
    ocultarTotal?: boolean;
  }
>();
const items = defineModel<ItemForm[]>({ required: true });
type ProductoDisponible = Articulo & {
  cantidad_disponible: number;
  financiadores: { id: number; nombre: string; cantidad: number }[];
};
const busquedaProducto = ref('');
const stock = computed(() => items.value.filter((i) => i.tipo === 'stock'));
const repuestos = computed(() =>
  items.value.filter((i) => i.tipo === 'repuesto'),
);
const errors = computed(() => [
  ...new Set(
    Object.entries(props.errores || {})
      .filter(([key]) => key.startsWith('items'))
      .map(([, value]) => value),
  ),
]);
const total = computed(() =>
  items.value.reduce((sum, i) => sum + (price(i) || 0) * Number(i.cantidad), 0),
);
function empty(tipo: ItemForm['tipo']): ItemForm {
  return {
    grupo: nuevaClave(),
    tipo,
    descripcion: '',
    lote_id: '',
    comprador_id: '',
    proveedor_id: '',
    proveedor: '',
    cantidad: 1,
    costo: '',
    precio: '',
    porcentaje_ganancia: '',
    fecha_compra: '',
    reparto: [],
  };
}
function addProduct(p: ProductoDisponible) {
  const buyer = p.financiadores.length === 1 ? p.financiadores[0].id : '';
  const previous = stock.value.find(
    (i) =>
      i.producto_id === p.id &&
      i.comprador_id === buyer &&
      price(i) === p.precio_venta_centavos,
  );
  if (previous) {
    previous.cantidad = Number(previous.cantidad) + 1;
    nextTick(() => (busquedaProducto.value = ''));
    return;
  }
  items.value.push({
    ...empty('stock'),
    producto_id: p.id,
    descripcion: [p.nombre, p.marca].filter(Boolean).join(' · '),
    financiadores: p.financiadores,
    comprador_id: p.financiadores.length === 1 ? p.financiadores[0].id : '',
    precio: p.precio_venta_centavos / 100,
  });
  nextTick(() => (busquedaProducto.value = ''));
}
function another(i: ItemForm) {
  const alternatives = (i.financiadores || []).filter(
    (p) => p.id !== i.comprador_id,
  );
  items.value.push({
    ...i,
    id: undefined,
    grupo: nuevaClave(),
    cantidad: 1,
    comprador_id: alternatives.length === 1 ? alternatives[0].id : '',
  });
}
function remove(i: ItemForm) {
  items.value = items.value.filter((row) => row !== i);
}
</script>
<template>
  <div class="space-y-6">
    <section
      v-if="!soloStock"
      class="space-y-4"
      aria-labelledby="repuestos-heading"
    >
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h3 id="repuestos-heading" class="text-sm font-medium">
            Repuestos del pedido
            <span class="font-normal text-muted-foreground">(opcional)</span>
          </h3>
          <p class="mt-1 text-xs text-muted-foreground">
            Piezas que comprás para esta reparación. Se cargan sólo en este
            pedido.
          </p>
        </div>
        <Button
          type="button"
          variant="outline"
          size="sm"
          @click="items.push(empty('repuesto'))"
          ><Plus class="size-4" />Agregar repuesto</Button
        >
      </div>
      <div
        v-for="i in repuestos"
        :key="i.grupo"
        class="space-y-4 rounded-lg border p-4"
      >
        <div class="flex items-end gap-3">
          <div class="min-w-0 flex-1 space-y-2">
            <label :for="`descripcion-${i.grupo}`" class="text-sm font-medium"
              >Descripción del repuesto</label
            >
            <Input
              :id="`descripcion-${i.grupo}`"
              v-model="i.descripcion"
              placeholder="Ej.: batería para notebook"
              maxlength="255"
              required
            />
          </div>
          <Button
            type="button"
            variant="ghost"
            size="icon"
            aria-label="Quitar repuesto"
            @click="remove(i)"
            ><X class="size-4 text-destructive"
          /></Button>
        </div>
        <div
          class="grid grid-cols-2 items-end gap-4 sm:grid-cols-[90px_1fr_1fr_1fr]"
        >
          <div class="space-y-2">
            <label
              :for="`cantidad-repuesto-${i.grupo}`"
              class="text-sm font-medium"
              >Cantidad</label
            >
            <Input
              :id="`cantidad-repuesto-${i.grupo}`"
              v-model="i.cantidad"
              type="number"
              min="1"
              max="100000"
              required
              :disabled="!!i.compra_id"
              aria-label="Cantidad del repuesto"
            />
          </div>
          <div class="space-y-2">
            <label :for="`costo-${i.grupo}`" class="text-sm font-medium"
              >Costo unitario</label
            >
            <div class="relative">
              <span
                class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-muted-foreground"
                >$</span
              >
              <Input
                :id="`costo-${i.grupo}`"
                v-model="i.costo"
                class="pl-7"
                type="number"
                min="0"
                step="0.01"
                required
                :disabled="!!i.compra_id"
                aria-label="Costo unitario del repuesto"
              />
            </div>
          </div>
          <div class="space-y-2">
            <label :for="`ganancia-${i.grupo}`" class="text-sm font-medium"
              >Ganancia %</label
            >
            <Input
              :id="`ganancia-${i.grupo}`"
              v-model="i.porcentaje_ganancia"
              type="number"
              min="0"
              max="10000"
              step="0.01"
              placeholder="Ej.: 30"
              required
              aria-label="Ganancia del repuesto en porcentaje"
            />
          </div>
          <div class="space-y-2">
            <p class="text-sm text-muted-foreground">Precio de venta</p>
            <p class="flex h-9 items-center text-sm font-medium tabular-nums">
              {{
                i.costo !== '' && i.porcentaje_ganancia !== ''
                  ? moneda(price(i))
                  : '—'
              }}
            </p>
          </div>
        </div>
        <div
          class="flex flex-col items-stretch gap-3 sm:flex-row sm:items-start sm:justify-between"
        >
          <details
            :open="!!i.proveedor || !!i.fecha_compra"
            class="group/compra min-w-0 flex-1"
          >
            <summary
              class="inline-flex cursor-pointer list-none items-center gap-1 text-xs text-muted-foreground hover:text-foreground"
            >
              <ChevronDown
                class="size-3.5 group-open/compra:rotate-180"
              />Proveedor y fecha de compra
              <span v-if="i.compra_id">· Compra registrada</span
              ><span v-else>· Opcional</span>
            </summary>
            <div class="mt-3 grid gap-3 sm:grid-cols-2">
              <div class="space-y-2">
                <label :for="`proveedor-${i.grupo}`" class="text-xs font-medium"
                  >Proveedor</label
                >
                <ProveedorAutocomplete
                  :input-id="`proveedor-${i.grupo}`"
                  :model-value="i.proveedor || ''"
                  :disabled="!!i.compra_id"
                  @update:model-value="i.proveedor = $event"
                />
              </div>
              <div class="space-y-2">
                <label :for="`compra-${i.grupo}`" class="text-xs font-medium"
                  >Fecha, si ya lo compraste</label
                >
                <Input
                  :id="`compra-${i.grupo}`"
                  v-model="i.fecha_compra"
                  type="date"
                  :disabled="!!i.compra_id"
                  aria-label="Fecha de compra del repuesto"
                />
              </div>
              <p
                v-if="i.compra_id"
                class="text-xs text-muted-foreground sm:col-span-2"
              >
                El gasto se conserva aunque quites este repuesto del
                presupuesto.
              </p>
            </div>
          </details>
          <p
            class="self-end text-sm whitespace-nowrap text-muted-foreground sm:self-start"
          >
            Subtotal:
            <span class="font-medium text-foreground tabular-nums">{{
              moneda(price(i) * i.cantidad)
            }}</span>
          </p>
        </div>
      </div>
      <p
        v-if="!repuestos.length"
        class="rounded-lg border border-dashed p-4 text-center text-sm text-muted-foreground"
      >
        No hay repuestos agregados.
      </p>
    </section>

    <section
      class="space-y-4"
      :class="{ 'border-t pt-6': !soloStock }"
      aria-labelledby="productos-heading"
    >
      <div>
        <h3 id="productos-heading" class="text-sm font-medium">
          Productos del inventario
          <span v-if="!soloStock" class="font-normal text-muted-foreground"
            >(opcional)</span
          >
        </h3>
        <p class="mt-1 text-xs text-muted-foreground">
          Artículos que ya tenés en stock, con su precio de venta definido.
        </p>
      </div>
      <BuscadorSelector
        v-model:text-value="busquedaProducto"
        input-mode
        endpoint="/productos/buscar"
        label="Buscar producto del inventario"
        placeholder="Buscar producto por nombre o marca…"
        :params="{ inventario: 1 }"
        :map-item="
          (p: ProductoDisponible) => ({
            id: p.id,
            label: p.nombre,
            description: `${p.marca || ''} · ${p.cantidad_disponible} disponibles · ${moneda(p.precio_venta_centavos)}`,
            raw: p,
          })
        "
        @select="addProduct"
      />
      <div
        v-for="i in stock"
        :key="i.grupo"
        class="flex items-start gap-3 rounded-lg border p-4"
      >
        <div class="min-w-0 flex-1 space-y-3">
          <div>
            <p class="font-medium">{{ i.descripcion }}</p>
            <p class="text-sm text-muted-foreground">
              Precio de venta: {{ moneda(price(i))
              }}<span v-if="i.financiadores?.length">
                · Stock disponible:
                {{
                  i.financiadores.reduce((sum, p) => sum + p.cantidad, 0)
                }}</span
              >
            </p>
          </div>
          <p
            v-if="i.financiadores?.length === 1"
            class="text-xs text-muted-foreground"
          >
            El costo lo recupera {{ i.financiadores[0].nombre }}.
          </p>
          <div
            v-if="(i.financiadores?.length || 0) > 1"
            class="max-w-sm space-y-2"
          >
            <p class="text-xs text-muted-foreground">
              Elegí de quién usar el stock.
            </p>
            <Select
              :model-value="String(i.comprador_id || '')"
              @update:model-value="i.comprador_id = Number($event)"
            >
              <SelectTrigger
                :aria-label="`Quién recupera el costo de ${i.descripcion}`"
                ><SelectValue placeholder="Quién compró estas unidades"
              /></SelectTrigger>
              <SelectContent
                ><SelectItem
                  v-for="p in i.financiadores"
                  :key="p.id"
                  :value="String(p.id)"
                  >{{ p.nombre }} · {{ p.cantidad }} unidades</SelectItem
                ></SelectContent
              >
            </Select>
            <Button
              v-if="i.comprador_id"
              type="button"
              variant="link"
              size="sm"
              class="h-auto px-0 text-xs"
              @click="another(i)"
              >Usar también stock de otra persona</Button
            >
          </div>
          <div class="flex flex-wrap items-center gap-2">
            <label
              :for="`cantidad-producto-${i.grupo}`"
              class="text-sm font-medium"
              >Cantidad:</label
            >
            <Input
              :id="`cantidad-producto-${i.grupo}`"
              v-model="i.cantidad"
              type="number"
              min="1"
              max="100000"
              required
              class="w-24"
              :aria-label="`Cantidad de ${i.descripcion}`"
            />
            <span class="text-sm text-muted-foreground"
              >Subtotal:
              <span class="font-medium text-foreground tabular-nums">{{
                moneda(price(i) * i.cantidad)
              }}</span></span
            >
          </div>
        </div>
        <Button
          type="button"
          variant="ghost"
          size="icon"
          :aria-label="`Quitar ${i.descripcion}`"
          @click="remove(i)"
          ><X class="size-4 text-destructive"
        /></Button>
      </div>
      <p
        v-if="!stock.length"
        class="rounded-lg border border-dashed p-4 text-center text-sm text-muted-foreground"
      >
        No hay productos agregados.
      </p>
    </section>
    <div
      v-if="errors.length"
      role="alert"
      class="space-y-1 text-sm text-destructive"
    >
      <p v-for="error in errors" :key="error">{{ error }}</p>
    </div>
    <div
      v-if="!ocultarTotal"
      class="flex justify-between gap-4 rounded-lg border bg-muted/50 p-4 text-sm font-semibold"
    >
      <span>Total:</span
      ><span class="text-lg tabular-nums">{{ moneda(total) }}</span>
    </div>
  </div>
</template>
