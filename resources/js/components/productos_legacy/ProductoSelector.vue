<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <label class="text-sm font-medium">Repuestos (opcional)</label>
    </div>

    <!-- Buscador de repuestos -->
    <div class="relative">
      <div class="flex items-center gap-2">
        <div class="relative flex-1">
          <Search
            class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
          />
          <Input
            v-model="searchQuery"
            placeholder="Buscar repuesto por nombre o marca..."
            class="pl-9"
          />
        </div>
      </div>

      <!-- Dropdown de sugerencias -->
      <div
        v-if="searchQuery.trim() && suggestions.length > 0"
        class="absolute z-50 mt-1 max-h-60 w-full overflow-y-auto rounded-md border bg-popover p-1 shadow-md"
      >
        <button
          v-for="producto in suggestions"
          :key="producto.id"
          type="button"
          @click="agregarProducto(producto)"
          class="flex w-full items-center justify-between rounded-sm px-2 py-2 text-left text-sm outline-none hover:bg-accent"
        >
          <div class="flex-1">
            <div class="font-medium">
              {{ producto.nombre }} - {{ producto.marca }}
            </div>
            <div class="text-xs text-muted-foreground">
              Stock: {{ producto.cantidad_disponible }} | Precio:
              {{ formatMoney(producto.precio) }}
            </div>
          </div>
        </button>
      </div>
    </div>

    <p v-if="searchError" role="alert" class="text-sm text-destructive">
      No se pudieron buscar los productos. Volvé a escribir para reintentar.
    </p>
    <!-- Lista de repuestos seleccionados -->
    <div v-if="productosSeleccionados.length > 0" class="space-y-3">
      <div
        v-for="(item, index) in productosSeleccionados"
        :key="index"
        class="flex items-start gap-3 rounded-lg border p-4"
      >
        <div class="flex-1 space-y-3">
          <!-- Info del producto -->
          <div>
            <div class="font-medium">
              {{ item.producto.nombre }} - {{ item.producto.marca }}
            </div>
            <div class="text-sm text-muted-foreground">
              Precio: {{ formatMoney(item.precio) }} | Stock disponible:
              {{ item.producto.cantidad_disponible }}
            </div>
          </div>

          <!-- Selector de cantidad -->
          <div class="flex items-center gap-2">
            <label class="text-sm font-medium">Cantidad:</label>
            <Input
              v-model.number="item.cantidad"
              type="number"
              min="1"
              step="1"
              class="w-24"
              :aria-label="`Cantidad de ${item.producto.nombre}`"
              :disabled="form.processing"
            />
            <span class="text-sm text-muted-foreground">
              Subtotal: {{ formatMoney(calcularSubtotal(item)) }}
            </span>
          </div>
        </div>

        <!-- Botón eliminar -->
        <Button
          type="button"
          variant="ghost"
          size="icon"
          @click="eliminarProducto(index)"
        >
          <X class="h-4 w-4 text-destructive" />
        </Button>
      </div>

      <!-- Resumen -->
      <div class="rounded-lg border bg-muted/50 p-4">
        <div class="space-y-2 text-sm">
          <div class="flex justify-between">
            <span class="text-muted-foreground">Costo repuestos:</span>
            <span class="font-medium">{{ formatMoney(costoProductos) }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-muted-foreground">Margen repuestos:</span>
            <span class="font-medium">{{
              formatMoney(gananciaProductos)
            }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-muted-foreground">Costo mano de obra:</span>
            <span class="font-medium">{{
              formatMoney(form.costo_mano_obra || 0)
            }}</span>
          </div>
          <div class="flex justify-between border-t pt-2 font-semibold">
            <span>Presupuesto total:</span>
            <span class="text-lg">{{ formatMoney(presupuestoTotal) }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Estado vacío -->
    <div
      v-else
      class="rounded-lg border border-dashed p-8 text-center text-muted-foreground"
    >
      <p class="text-sm">No hay repuestos agregados</p>
      <p class="mt-1 text-xs">Buscá y seleccioná repuestos para agregarlos</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import productoRoutes from '@/routes/producto';
import type { Pedido } from '@/types/pedido.interface';
import type { Producto } from '@/types/producto.interface';
import { formatMoney } from '@/utils/formatter';
import axios from 'axios';
import { Search, X } from 'lucide-vue-next';
import { computed, inject, onBeforeUnmount, ref, watch } from 'vue';

interface ProductoSeleccionado {
  id: number;
  producto: Producto;
  cantidad: number;
  precio: number;
  precio_venta: number;
}

const form = inject<any>('pedidoForm');
const pedido = inject<Pedido>('pedidoInicial')!;
const productosSeleccionados = computed<ProductoSeleccionado[]>(
  () => form.productos,
);
const searchQuery = ref('');
const suggestions = ref<Producto[]>([]);
const searchError = ref(false);
let timer: ReturnType<typeof setTimeout>;
let controller: AbortController | undefined;
let generation = 0;

watch(searchQuery, (query) => {
  clearTimeout(timer);
  controller?.abort();
  const current = ++generation;
  suggestions.value = [];
  searchError.value = false;
  if (!query.trim()) return;
  timer = setTimeout(async () => {
    controller = new AbortController();
    try {
      const { data } = await axios.get<Producto[]>(
        productoRoutes.search().url,
        {
          params: { q: query.trim() },
          signal: controller.signal,
        },
      );
      if (current === generation) suggestions.value = data;
    } catch (error) {
      if (!axios.isCancel(error) && current === generation)
        searchError.value = true;
    }
  }, 250);
});
onBeforeUnmount(() => {
  clearTimeout(timer);
  controller?.abort();
  generation++;
});

function agregarProducto(producto: Producto) {
  const existing = productosSeleccionados.value.find(
    (item) => item.id === producto.id,
  );
  if (!existing) {
    const original = pedido.productos_seleccionados?.find(
      (item) => item.producto_id === producto.id,
    );
    const precio = Number(original?.precio ?? producto.precio);
    form.productos.push({
      id: producto.id,
      producto,
      cantidad: 1,
      precio,
      precio_venta: Number(
        original?.precio_venta ?? Number((precio * 1.3).toFixed(4)),
      ),
    });
  }
  searchQuery.value = '';
}
function eliminarProducto(index: number) {
  form.productos.splice(index, 1);
}
const calcularSubtotal = (item: ProductoSeleccionado) =>
  item.precio_venta * Number(item.cantidad);
const costoAnterior = (pedido.productos_seleccionados ?? []).reduce(
  (sum, item) => sum + Number(item.precio) * item.cantidad,
  0,
);
const ventaAnterior = (pedido.productos_seleccionados ?? []).reduce(
  (sum, item) =>
    sum +
    Number(item.precio_venta ?? Number(item.precio) * 1.3) * item.cantidad,
  0,
);
const costoProductos = computed(
  () =>
    Number(pedido.costo ?? 0) +
    productosSeleccionados.value.reduce(
      (sum, item) => sum + item.precio * Number(item.cantidad),
      0,
    ) -
    costoAnterior,
);
const presupuestoTotal = computed(
  () =>
    Number(pedido.presupuesto ?? 0) +
    productosSeleccionados.value.reduce(
      (sum, item) => sum + calcularSubtotal(item),
      0,
    ) -
    ventaAnterior +
    Number(form.costo_mano_obra || 0) -
    Number(pedido.costo_mano_obra ?? 0),
);
const gananciaProductos = computed(
  () =>
    presupuestoTotal.value -
    costoProductos.value -
    Number(form.costo_mano_obra || 0),
);
</script>
