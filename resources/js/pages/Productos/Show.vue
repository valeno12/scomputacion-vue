<script setup lang="ts">
import Paginador from '@/components/comercio/Paginador.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import FormLayout from '@/layouts/FormLayout.vue';
import {
  moneda,
  type ItemOperacion,
  type Lote,
  type Operacion,
  type Paginacion,
} from '@/types/comercio';
import type { ProductoNuevo } from '@/types/producto-nuevo.interface';
import { Head, Link } from '@inertiajs/vue3';
import { PackagePlus, Pencil } from 'lucide-vue-next';
type Actor = { name: string } | null;
defineProps<{
  producto: ProductoNuevo & { registrador: Actor; created_at: string };
  stock: number;
  ingresos: Paginacion<
    Lote & { proveedor: { nombre: string } | null; registrador: Actor }
  >;
  ventas: Paginacion<ItemOperacion & { operacion: Operacion }>;
}>();
</script>
<template>
  <Head :title="producto.nombre" /><AppLayout
    :breadcrumbs="[
      { title: 'Productos', href: '/productos' },
      { title: producto.nombre, href: '#' },
    ]"
    ><FormLayout
      wide
      :title="producto.nombre"
      :description="producto.marca || 'Detalle del producto'"
    >
      <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-muted-foreground">
          Creó {{ producto.registrador?.name || 'Sin registro anterior' }} ·
          {{ producto.activo ? 'Activo' : 'Archivado' }}
        </p>
        <div v-if="producto.activo" class="flex gap-2">
          <Button variant="outline" as-child
            ><Link :href="`/productos/${producto.id}/edit`"
              ><Pencil class="size-4" /> Editar producto</Link
            ></Button
          ><Button as-child
            ><Link :href="`/productos/${producto.id}/ingresos/create`"
              ><PackagePlus class="size-4" /> Nuevo ingreso</Link
            ></Button
          >
        </div>
      </div>
      <div class="grid gap-4 sm:grid-cols-3">
        <div
          class="rounded-xl border border-blue-200 bg-blue-50/60 p-5 dark:border-blue-900 dark:bg-blue-950/25"
        >
          <p class="text-sm text-muted-foreground">Stock disponible</p>
          <p class="mt-2 text-2xl font-semibold">{{ stock }} unidades</p>
        </div>
        <div class="rounded-xl border p-5">
          <p class="text-sm text-muted-foreground">Precio de venta actual</p>
          <p class="mt-2 text-2xl font-semibold">
            {{ moneda(producto.precio_venta_centavos) }}
          </p>
        </div>
        <div class="rounded-xl border p-5">
          <p class="text-sm text-muted-foreground">Historial</p>
          <p class="mt-2 text-sm">
            {{ ingresos.total }} ingresos de stock ·
            {{ ventas.total }} movimientos en ventas y pedidos
          </p>
        </div>
      </div>
      <section class="space-y-3">
        <h2 class="text-lg font-semibold">Ingresos de stock</h2>
        <p class="text-sm text-muted-foreground">
          Quién compró identifica a quien recupera el costo. Registró indica
          quién cargó el ingreso en el sistema.
        </p>
        <div class="history-table overflow-x-auto rounded-xl border">
          <table>
            <thead>
              <tr>
                <th>Fecha / ingreso</th>
                <th>Proveedor</th>
                <th>Ingresaron / quedan</th>
                <th>Costo unitario</th>
                <th>Quién compró</th>
                <th>Registró</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="i in ingresos.data" :key="i.id">
                <td>
                  {{ i.fecha
                  }}<small class="block text-muted-foreground"
                    >#{{ i.id }} ·
                    {{
                      i.tipo === 'inicial' ? 'Stock inicial' : 'Compra'
                    }}</small
                  >
                </td>
                <td>{{ i.proveedor?.nombre || 'Sin proveedor' }}</td>
                <td>{{ i.cantidad_inicial }} / {{ i.cantidad_disponible }}</td>
                <td>{{ moneda(i.costo_unitario_centavos) }}</td>
                <td>{{ i.comprador.nombre }}</td>
                <td>{{ i.registrador?.name || 'Sin registro anterior' }}</td>
              </tr>
              <tr v-if="!ingresos.total">
                <td colspan="6" class="text-center text-muted-foreground">
                  Todavía no tiene ingresos.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <Paginador v-if="ingresos.total > 20" :links="ingresos.links" />
      </section>
      <section class="space-y-3">
        <h2 class="text-lg font-semibold">Ventas y pedidos</h2>
        <p class="text-sm text-muted-foreground">
          Los importes son los guardados en cada operación. Las operaciones
          anuladas y los presupuestos se identifican por su estado.
        </p>
        <div class="history-table overflow-x-auto rounded-xl border">
          <table>
            <thead>
              <tr>
                <th>Operación / fecha</th>
                <th>Cliente</th>
                <th>Estado</th>
                <th>Cantidad</th>
                <th>Costo / venta unitaria</th>
                <th>Recupera costo</th>
                <th>Registró</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="v in ventas.data" :key="v.id">
                <td>
                  <Link
                    class="text-blue-600 hover:underline dark:text-blue-400"
                    :href="`/comercio/operaciones/${v.operacion.id}`"
                    >{{ v.operacion.tipo === 'venta' ? 'Venta' : 'Pedido' }}
                    {{
                      v.operacion.pedido?.codigo || `#${v.operacion.id}`
                    }}</Link
                  ><small class="block text-muted-foreground">{{
                    v.operacion.fecha
                  }}</small>
                </td>
                <td>
                  {{
                    v.operacion.cliente
                      ? `${v.operacion.cliente.nombre} ${v.operacion.cliente.apellido}`
                      : 'Consumidor final'
                  }}
                </td>
                <td>
                  {{
                    v.operacion.pedido?.deleted_at &&
                    v.operacion.tipo !== 'venta'
                      ? 'Pedido eliminado'
                      : v.operacion.estado
                  }}
                </td>
                <td>{{ v.cantidad }}</td>
                <td>
                  {{ moneda(v.costo_unitario_centavos) }} /
                  {{ moneda(v.precio_unitario_centavos) }}
                </td>
                <td>{{ v.comprador?.nombre }}</td>
                <td>
                  {{ v.operacion.registrador?.name || 'Sin registro anterior' }}
                </td>
              </tr>
              <tr v-if="!ventas.total">
                <td colspan="7" class="text-center text-muted-foreground">
                  Todavía no se utilizó en ventas ni pedidos.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <Paginador v-if="ventas.total > 20" :links="ventas.links" />
      </section> </FormLayout
  ></AppLayout>
</template>
<style scoped>
.history-table table {
  width: 100%;
  min-width: 800px;
  font-size: 0.875rem;
}
.history-table th {
  background: var(--muted);
  text-align: left;
  font-weight: 500;
}
.history-table th,
.history-table td {
  padding: 0.85rem 1rem;
}
.history-table tbody tr + tr {
  border-top: 1px solid var(--border);
}
</style>
