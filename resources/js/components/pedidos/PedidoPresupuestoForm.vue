<template>
  <div class="space-y-6">
    <!-- Trabajo a realizar -->
    <FormField
      id="trabajo_realizar"
      label="Trabajo a realizar"
      :error="form.errors.trabajo_realizar"
      required
    >
      <Textarea
        v-model="form.trabajo_realizar"
        placeholder="Describa el trabajo que se realizará"
        rows="4"
        :disabled="form.processing"
      />
    </FormField>

    <!-- Costo de mano de obra -->
    <FormField
      id="costo_mano_obra"
      label="Costo de mano de obra"
      :error="form.errors.costo_mano_obra"
      required
    >
      <div class="relative">
        <span
          class="absolute top-1/2 left-3 -translate-y-1/2 text-muted-foreground"
        >
          $
        </span>
        <Input
          v-model.number="form.costo_mano_obra"
          type="number"
          step="0.01"
          placeholder="0.00"
          class="pl-7"
          :disabled="form.processing"
        />
      </div>
    </FormField>

    <ProductoSelector />
  </div>
</template>

<script setup lang="ts">
import FormField from '@/components/common/FormField.vue';
import ProductoSelector from '@/components/productos/ProductoSelector.vue';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { inject } from 'vue';

const form = inject<any>('pedidoForm');

if (!form) {
  throw new Error(
    'PedidoPresupuestoForm must be used inside a component that provides pedidoForm',
  );
}
</script>
