<script setup lang="ts">
import { moneda, type Distribucion } from '@/types/comercio';
import { Link } from '@inertiajs/vue3';
defineProps<{
  resumen: {
    ganancia_centavos: number;
    distribucion: Distribucion[];
    operaciones: {
      id: number;
      tipo: string;
      fecha_cobro: string;
      total_centavos: number;
    }[];
    compras: {
      id: string;
      descripcion: string;
      tipo: string;
      fecha: string;
      total_centavos: number;
    }[];
  };
}>();
</script>
<template>
  <section class="commerce space-y-6">
    <div class="panel overflow-x-auto">
      <h2>Repartos de operaciones cobradas</h2>
      <p class="my-3 text-sm text-muted-foreground">
        Ganancia de ventas y reparaciones actuales:
        {{ moneda(resumen.ganancia_centavos) }}. La recuperación del costo se
        muestra por separado.
      </p>
      <table>
        <thead>
          <tr>
            <th>Participante</th>
            <th>Recuperación de costo</th>
            <th>Ganancia</th>
            <th>Total asignado</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="r in resumen.distribucion" :key="r.participante_id">
            <td>{{ r.nombre }}</td>
            <td>{{ moneda(r.costo_centavos) }}</td>
            <td>{{ moneda(r.ganancia_centavos) }}</td>
            <td>{{ moneda(r.costo_centavos + r.ganancia_centavos) }}</td>
          </tr>
          <tr v-if="!resumen.distribucion.length">
            <td colspan="4">No hay repartos cobrados en este mes.</td>
          </tr>
        </tbody>
      </table>
    </div>
    <div class="grid gap-5 lg:grid-cols-2">
      <div class="panel overflow-x-auto">
        <h2>Cobros de ventas y reparaciones actuales</h2>
        <table>
          <thead>
            <tr>
              <th>Operación</th>
              <th>Fecha</th>
              <th>Total</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="o in resumen.operaciones" :key="o.id">
              <td>
                <Link :href="`/comercio/operaciones/${o.id}`"
                  >{{ o.tipo }} #{{ o.id }}</Link
                >
              </td>
              <td>{{ o.fecha_cobro }}</td>
              <td>{{ moneda(o.total_centavos) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="panel overflow-x-auto">
        <h2>Compras de inventario y repuestos</h2>
        <table>
          <thead>
            <tr>
              <th>Concepto</th>
              <th>Fecha</th>
              <th>Total</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="c in resumen.compras" :key="c.id">
              <td>
                {{ c.descripcion
                }}<small class="block text-muted-foreground">{{
                  c.tipo
                }}</small>
              </td>
              <td>{{ c.fecha }}</td>
              <td>{{ moneda(c.total_centavos) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</template>
