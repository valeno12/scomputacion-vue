<script setup lang="ts">
import BuscadorSelector from '@/components/common/BuscadorSelector.vue';
import proveedor from '@/routes/proveedor';
withDefaults(
  defineProps<{
    inputId?: string;
    modelValue: string;
    placeholder?: string;
    disabled?: boolean;
    required?: boolean;
  }>(),
  { placeholder: 'Nombre del proveedor' },
);
defineEmits<{ 'update:modelValue': [value: string] }>();
</script>
<template>
  <BuscadorSelector
    input-mode
    allow-free-text
    :endpoint="proveedor.search().url"
    label="Proveedor"
    :input-id="inputId"
    :text-value="modelValue"
    :placeholder="placeholder"
    :disabled="disabled"
    :required="required"
    :map-item="(p) => ({ id: p.id, label: p.nombre, raw: p })"
    @update:text-value="$emit('update:modelValue', $event)"
  />
</template>
