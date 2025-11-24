<template>
  <div class="relative">
    <Input
      v-model="inputValue"
      @input="handleInput"
      @focus="handleFocus"
      @blur="handleBlur"
      :placeholder="placeholder"
      :disabled="disabled"
    />
    <!-- Dropdown de sugerencias -->
    <div
      v-if="showSuggestions && suggestions.length > 0"
      class="absolute z-50 mt-1 w-full rounded-md border bg-popover p-1 shadow-md"
    >
      <button
        v-for="(nombre, index) in suggestions"
        :key="index"
        @mousedown="selectSuggestion(nombre)"
        class="w-full cursor-pointer rounded-sm px-2 py-1.5 text-left text-sm outline-none hover:bg-accent hover:text-accent-foreground"
      >
        {{ nombre }}
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Input } from '@/components/ui/input';
import producto from '@/routes/producto';
import axios from 'axios';
import { ref, watch } from 'vue';

interface Props {
  modelValue: string;
  placeholder?: string;
  disabled?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  placeholder: 'nombre del producto',
  disabled: false,
});

const emit = defineEmits<{
  'update:modelValue': [value: string];
}>();

const inputValue = ref(props.modelValue);
const suggestions = ref<string[]>([]); // Ahora es un array de strings
const showSuggestions = ref(false);
const searching = ref(false);

let searchTimeout: ReturnType<typeof setTimeout>;

watch(
  () => props.modelValue,
  (newValue) => {
    inputValue.value = newValue;
  },
);

watch(inputValue, (newValue) => {
  emit('update:modelValue', newValue);
});

const handleInput = () => {
  clearTimeout(searchTimeout);

  if (inputValue.value.length === 0) {
    suggestions.value = [];
    showSuggestions.value = false;
    return;
  }

  searching.value = true;

  searchTimeout = setTimeout(async () => {
    try {
      const response = await axios.get(producto.buscarNombre().url, {
        params: { q: inputValue.value },
      });
      suggestions.value = response.data;
      showSuggestions.value = true;
    } catch (error) {
      console.error('Error buscando productos:', error);
    } finally {
      searching.value = false;
    }
  }, 300);
};

const handleFocus = () => {
  if (suggestions.value.length > 0) {
    showSuggestions.value = true;
  }
};

const handleBlur = () => {
  setTimeout(() => {
    showSuggestions.value = false;
  }, 200);
};

const selectSuggestion = (nombre: string) => {
  inputValue.value = nombre;
  showSuggestions.value = false;
};
</script>
