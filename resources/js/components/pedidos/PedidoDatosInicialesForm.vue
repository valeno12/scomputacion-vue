<template>
  <div class="space-y-6">
    <!-- Cliente -->
    <FormField id="cliente_id" label="Cliente" required>
      <ClienteSelector v-model="form.cliente_id" />
    </FormField>

    <!-- Equipo -->
    <FormField id="equipo" label="Equipo" :error="form.errors.equipo" required>
      <Input
        v-model="form.equipo"
        placeholder="Ej: Notebook Asus"
        :disabled="form.processing"
      />
    </FormField>

    <!-- Descripción del problema -->
    <FormField
      id="estado_ingreso"
      label="Descripción del problema"
      :error="form.errors.estado_ingreso"
      required
    >
      <Textarea
        v-model="form.estado_ingreso"
        placeholder="Describa el problema reportado por el cliente"
        rows="4"
        :disabled="form.processing"
      />
    </FormField>

    <!-- Cargador -->
    <div class="flex items-center space-x-2">
      <Checkbox
        id="cargador"
        v-model:checked="form.cargador"
        :disabled="form.processing"
      />
      <label
        for="cargador"
        class="text-sm leading-none font-medium peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
      >
        ¿Incluye cargador?
      </label>
    </div>
  </div>
</template>

<script setup lang="ts">
import ClienteSelector from '@/components/clientes/ClienteSelector.vue';
import FormField from '@/components/common/FormField.vue';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { inject } from 'vue';

const form = inject<any>('pedidoForm');

if (!form) {
  throw new Error(
    'PedidoDatosInicialesForm must be used inside a component that provides pedidoForm',
  );
}
</script>
