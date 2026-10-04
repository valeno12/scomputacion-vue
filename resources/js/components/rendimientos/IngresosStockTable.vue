<script setup lang="ts">
import { moneda } from '@/types/comercio';
import { Link } from '@inertiajs/vue3';
import { PackagePlus } from 'lucide-vue-next';
const props = defineProps<{
  ingresos: {
    id: string;
    descripcion: string;
    url: string;
    comprador: string;
    proveedor: string;
    cantidad: number;
    fecha: string;
    total_centavos: number;
  }[];
}>();
</script>
<template>
  <section class="overflow-hidden rounded-xl border bg-card shadow-sm">
    <header
      class="flex items-center gap-3 border-b bg-gradient-to-r from-blue-50 to-indigo-50 px-5 py-4 dark:from-blue-950/30 dark:to-indigo-950/20"
    >
      <span
        class="flex size-10 items-center justify-center rounded-lg bg-blue-100 text-blue-600 dark:bg-blue-500/20 dark:text-blue-300"
        ><PackagePlus class="size-5"
      /></span>
      <div>
        <h2 class="font-semibold">Ingresos de stock</h2>
        <p class="text-xs text-muted-foreground">
          Todas las compras del mes, sin importar quién las pagó.
        </p>
      </div>
    </header>
    <div v-if="props.ingresos.length" class="max-h-[390px] overflow-auto">
      <table class="w-full min-w-[760px] text-sm">
        <thead
          class="sticky top-0 z-10 bg-muted text-left text-xs text-muted-foreground"
        >
          <tr>
            <th class="px-5 py-3 font-medium">Producto</th>
            <th class="px-3 py-3 font-medium">Comprador</th>
            <th class="px-3 py-3 font-medium">Proveedor</th>
            <th class="px-3 py-3 text-right font-medium">Cant.</th>
            <th class="px-3 py-3 font-medium">Fecha</th>
            <th class="px-5 py-3 text-right font-medium">Costo</th>
          </tr>
        </thead>
        <tbody class="divide-y">
          <tr v-for="ingreso in props.ingresos" :key="ingreso.id">
            <td class="px-5 py-3 font-medium">
              <Link
                :href="ingreso.url"
                class="hover:text-blue-600 hover:underline dark:hover:text-blue-300"
                >{{ ingreso.descripcion }}</Link
              >
            </td>
            <td class="px-3 py-3">{{ ingreso.comprador }}</td>
            <td class="px-3 py-3 text-muted-foreground">
              {{ ingreso.proveedor }}
            </td>
            <td class="px-3 py-3 text-right tabular-nums">
              {{ ingreso.cantidad }}
            </td>
            <td class="px-3 py-3 text-muted-foreground">{{ ingreso.fecha }}</td>
            <td class="px-5 py-3 text-right font-semibold tabular-nums">
              {{ moneda(ingreso.total_centavos) }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <p v-else class="px-5 py-8 text-sm text-muted-foreground">
      No hubo ingresos de stock este mes.
    </p>
  </section>
</template>
