<script setup lang="ts">
import FormField from '@/components/common/FormField.vue';
import ProveedorAutocomplete from '@/components/proveedores/ProveedorAutocomplete.vue';
import { Input } from '@/components/ui/input';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import type { Participante } from '@/types/comercio';
import { Link } from '@inertiajs/vue3';
import { inject } from 'vue';

withDefaults(
  defineProps<{
    participantes: Participante[];
    showCantidad?: boolean;
    showCosto?: boolean;
    wide?: boolean;
  }>(),
  { showCantidad: true, showCosto: true },
);
const form = inject<{
  cantidad: number | string;
  costo: number | string;
  comprador_id: number | '';
  proveedor: string;
  fecha: string;
  tipo: string;
  processing: boolean;
  errors: Record<string, string>;
}>('ingresoProductoForm')!;
</script>
<template>
  <div class="grid gap-5 md:grid-cols-2" :class="{ 'xl:grid-cols-4': wide }">
    <FormField
      v-if="showCantidad"
      id="cantidad"
      label="Cantidad a ingresar"
      :error="form.errors.cantidad"
      required
    >
      <Input
        id="cantidad"
        v-model.number="form.cantidad"
        type="number"
        min="1"
        max="100000"
        step="1"
        :disabled="form.processing"
        required
      />
    </FormField>
    <FormField
      v-if="showCosto"
      id="costo"
      label="Costo unitario"
      :error="form.errors.costo"
      required
    >
      <div class="relative">
        <span
          class="absolute top-1/2 left-3 -translate-y-1/2 text-muted-foreground"
          >$</span
        ><Input
          id="costo"
          v-model.number="form.costo"
          type="number"
          min="0"
          step="0.01"
          class="pl-7"
          :disabled="form.processing"
          required
        />
      </div>
    </FormField>
    <FormField
      id="comprador"
      label="Quién compró"
      :error="form.errors.comprador_id"
      required
    >
      <Select v-model="form.comprador_id" :disabled="form.processing" required>
        <SelectTrigger id="comprador"
          ><SelectValue placeholder="Seleccionar comprador"
        /></SelectTrigger>
        <SelectContent
          ><SelectItem
            v-for="persona in participantes"
            :key="persona.id"
            :value="persona.id"
            >{{ persona.nombre }}</SelectItem
          ></SelectContent
        >
      </Select>
      <p v-if="!participantes.length" class="text-sm text-muted-foreground">
        Agregá quién compra en
        <Link href="/settings/repartos" class="underline"
          >Configuración → Repartos</Link
        >.
      </p>
    </FormField>
    <FormField
      id="proveedor"
      label="Proveedor"
      :error="form.errors.proveedor"
      required
    >
      <ProveedorAutocomplete
        v-model="form.proveedor"
        input-id="proveedor"
        :disabled="form.processing"
        required
      />
    </FormField>
    <FormField
      id="fecha"
      label="Fecha del ingreso"
      :error="form.errors.fecha"
      required
    >
      <Input
        id="fecha"
        v-model="form.fecha"
        type="date"
        :disabled="form.processing"
        required
      />
    </FormField>
    <FormField
      id="tipo-ingreso"
      label="Tipo de ingreso"
      :error="form.errors.tipo"
      required
    >
      <Select v-model="form.tipo" :disabled="form.processing">
        <SelectTrigger id="tipo-ingreso"><SelectValue /></SelectTrigger>
        <SelectContent
          ><SelectItem value="compra">Compra</SelectItem
          ><SelectItem value="inicial">Stock inicial</SelectItem></SelectContent
        >
      </Select>
      <p class="text-xs text-muted-foreground">
        Usá Stock inicial para mercadería que ya tenías; no registra un gasto
        nuevo.
      </p>
    </FormField>
  </div>
</template>
