<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <label class="text-sm font-medium">Productos (opcional)</label>
    </div>

    <!-- Buscador de productos -->
    <div class="relative">
      <div class="flex items-center gap-2">
        <div class="relative flex-1">
          <Search
            class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
          />
          <Input
            v-model="searchQuery"
            @input="buscarProductos"
            @focus="showSuggestions = true"
            placeholder="Buscar producto por nombre o marca..."
            class="pl-9"
          />
        </div>
      </div>

      <!-- Dropdown de sugerencias -->
      <div
        v-if="showSuggestions && suggestions.length > 0"
        class="absolute z-50 mt-1 max-h-60 w-full overflow-y-auto rounded-md border bg-popover p-1 shadow-md"
      >
        <button
          v-for="producto in suggestions"
          :key="producto.id"
          @mousedown="agregarProducto(producto)"
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

    <!-- Lista de productos seleccionados -->
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
              Precio: {{ formatMoney(item.producto.precio) }} | Stock
              disponible: {{ item.producto.cantidad_disponible }}
            </div>
          </div>

          <!-- Selector de cantidad -->
          <div class="flex items-center gap-2">
            <label class="text-sm font-medium">Cantidad:</label>
            <Select v-model="item.cantidad">
              <SelectTrigger class="w-24">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem
                  v-for="n in item.producto.cantidad_disponible +
                  item.cantidadInicial"
                  :key="n"
                  :value="n.toString()"
                >
                  {{ n }}
                </SelectItem>
              </SelectContent>
            </Select>
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
            <span class="text-muted-foreground">Costo productos:</span>
            <span class="font-medium">{{ formatMoney(costoProductos) }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-muted-foreground">Ganancia productos (30%):</span>
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
      <p class="text-sm">No hay productos agregados</p>
      <p class="mt-1 text-xs">Busca y selecciona productos para agregarlos</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import productoRoutes from '@/routes/producto';
import type { Producto } from '@/types/producto.interface';
import { formatMoney } from '@/utils/formatter';
import axios from 'axios';
import { Search, X } from 'lucide-vue-next';
import { computed, inject, onMounted, ref, watch } from 'vue';

interface ProductoSeleccionado {
  producto: Producto;
  cantidad: string;
  cantidadInicial: number;
}

const form = inject<any>('pedidoForm');
if (!form) {
  throw new Error(
    'ProductoSelector must be used inside a component that provides pedidoForm',
  );
}

const searchQuery = ref('');
const suggestions = ref<Producto[]>([]);
const showSuggestions = ref(false);
const searching = ref(false);
const productosSeleccionados = ref<ProductoSeleccionado[]>([]);
const productosIniciales = inject<any[]>('productosIniciales', []);

let searchTimeout: ReturnType<typeof setTimeout>;

// Buscar productos
const buscarProductos = () => {
  clearTimeout(searchTimeout);

  if (searchQuery.value.length === 0) {
    suggestions.value = [];
    showSuggestions.value = false;
    return;
  }

  searching.value = true;

  searchTimeout = setTimeout(async () => {
    try {
      const response = await axios.get(productoRoutes.search().url, {
        params: { q: searchQuery.value },
      });
      suggestions.value = response.data;
      showSuggestions.value = true;
    } catch (error) {
      console.error('Error buscando productos:', error);
    } finally {
      searching.value = false;
    }
  }, 300);
};

// Agregar producto a la lista
const agregarProducto = (producto: Producto) => {
  // Verificar si ya está agregado
  const yaAgregado = productosSeleccionados.value.find(
    (item) => item.producto.id === producto.id,
  );

  if (yaAgregado) {
    // Si ya está, incrementar cantidad si hay stock
    const cantidadActual = Number(yaAgregado.cantidad);
    if (cantidadActual < producto.cantidad_disponible) {
      yaAgregado.cantidad = (cantidadActual + 1).toString();
    }
  } else {
    // Agregar nuevo
    productosSeleccionados.value.push({
      producto,
      cantidad: '1',
      cantidadInicial: 0,
    });
  }

  // Limpiar búsqueda
  searchQuery.value = '';
  suggestions.value = [];
  showSuggestions.value = false;
};

// Eliminar producto
const eliminarProducto = (index: number) => {
  productosSeleccionados.value.splice(index, 1);
};

// Calcular subtotal de un item
const calcularSubtotal = (item: ProductoSeleccionado): number => {
  const precioVenta = item.producto.precio * 1.3;
  return precioVenta * Number(item.cantidad);
};

// Calcular costo total
const costoProductos = computed(() => {
  return productosSeleccionados.value.reduce((total, item) => {
    return total + item.producto.precio * Number(item.cantidad);
  }, 0);
});

// Calcular ganancia total
const gananciaProductos = computed(() => {
  return productosSeleccionados.value.reduce((total, item) => {
    const gananciaUnitaria = item.producto.precio * 1.3 - item.producto.precio;
    return total + gananciaUnitaria * Number(item.cantidad);
  }, 0);
});

// ✅ AGREGAR: Calcular presupuesto total
const presupuestoTotal = computed(() => {
  const costoManoObra = Number(form.costo_mano_obra) || 0;
  return costoProductos.value + gananciaProductos.value + costoManoObra;
});

// Sincronizar con el form de Inertia
watch(
  productosSeleccionados,
  (newValue) => {
    form.productos = newValue.map((item) => ({
      id: item.producto.id,
      cantidad: Number(item.cantidad),
    }));
  },
  { deep: true },
);

onMounted(() => {
  if (
    productosIniciales.length > 0 &&
    productosSeleccionados.value.length === 0
  ) {
    productosSeleccionados.value = productosIniciales.map((item) => ({
      producto: item.producto,
      cantidad: String(item.cantidad),
      cantidadInicial: item.cantidad,
    }));
  }
});
</script>
