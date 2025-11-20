<template>
  <div class="space-y-4">
    <p class="mb-4 text-sm text-muted-foreground">
      Agregue el presupuesto y productos necesarios
    </p>

    <div class="rounded-lg border p-4 text-center text-muted-foreground">
      Form Paso 2 - Lo implementaremos después
    </div>
  </div>
</template>

<script setup lang="ts">
import { inject } from 'vue';

const form = inject<any>('pedidoForm');

if (!form) {
  throw new Error(
    'PedidoPresupuestoForm must be used inside a component that provides pedidoForm',
  );
}
</script>
