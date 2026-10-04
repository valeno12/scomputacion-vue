<script setup lang="ts">
import BuscadorSelector from '@/components/common/BuscadorSelector.vue';
import producto from '@/routes/producto';
withDefaults(
  defineProps<{
    inputId?: string;
    modelValue: string;
    placeholder?: string;
    disabled?: boolean;
  }>(),
  { placeholder: 'Nombre del producto' },
);
defineEmits<{ 'update:modelValue': [value: string] }>();
</script>
<template>
  <BuscadorSelector
    input-mode
    allow-free-text
    :endpoint="producto.buscarNombre().url"
    label="Nombre del producto"
    :input-id="inputId"
    :text-value="modelValue"
    :placeholder="placeholder"
    :disabled="disabled"
    :map-item="(name: string) => ({ id: name, label: name, raw: name })"
    @update:text-value="$emit('update:modelValue', $event)"
  />
</template>
