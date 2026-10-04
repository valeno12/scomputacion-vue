<script setup lang="ts">
import ItemsEditor from '@/components/comercio/ItemsEditor.vue';
import FormField from '@/components/common/FormField.vue';
import PedidoResumenPresupuesto from '@/components/pedidos/PedidoResumenPresupuesto.vue';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import type { OpcionesComercio } from '@/types/comercio';
import { inject } from 'vue';
const form = inject<any>('pedidoForm');
const opciones = inject<OpcionesComercio>('opcionesComercio')!;
if (!form) throw new Error('PedidoPresupuestoForm requires pedidoForm');
</script>
<template>
  <div class="space-y-6">
    <FormField
      id="trabajo_realizar"
      label="Trabajo a realizar"
      :error="form.errors.trabajo_realizar"
      required
    >
      <Textarea
        id="trabajo_realizar"
        v-model="form.trabajo_realizar"
        placeholder="Describa el trabajo que se realizará"
        rows="4"
        :disabled="form.processing"
      />
    </FormField>
    <FormField
      id="costo_mano_obra"
      label="Costo de mano de obra"
      :error="form.errors.costo_mano_obra"
      required
    >
      <div class="relative">
        <span
          class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-muted-foreground"
          >$</span
        >
        <Input
          id="costo_mano_obra"
          v-model.number="form.costo_mano_obra"
          type="number"
          min="0"
          step="0.01"
          placeholder="0.00"
          class="pl-7"
          :disabled="form.processing"
        />
      </div>
    </FormField>
    <ItemsEditor
      v-model="form.items"
      v-bind="opciones"
      :errores="form.errors"
      ocultar-total
    />
    <PedidoResumenPresupuesto
      :items="form.items"
      :mano-obra="form.costo_mano_obra"
    />
  </div>
</template>
