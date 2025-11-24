<template>
  <div class="rounded-lg border bg-white p-6 shadow-sm dark:bg-gray-950">
    <h3 class="mb-4 text-lg font-semibold">Filtros</h3>

    <div class="space-y-4">
      <!-- Selector de Año -->
      <div>
        <label for="year" class="mb-2 block text-sm font-medium">
          Selecciona el año
        </label>
        <Select v-model="localYear">
          <SelectTrigger id="year">
            <SelectValue placeholder="Selecciona un año" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem
              v-for="year in availableYears"
              :key="year"
              :value="year.toString()"
            >
              {{ year }}
            </SelectItem>
          </SelectContent>
        </Select>
      </div>

      <!-- Selector de Mes -->
      <div>
        <label for="month" class="mb-2 block text-sm font-medium">
          Selecciona el mes
        </label>
        <Select v-model="localMonth">
          <SelectTrigger id="month">
            <SelectValue placeholder="Selecciona un mes" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem
              v-for="(month, index) in months"
              :key="index"
              :value="(index + 1).toString()"
            >
              {{ month }}
            </SelectItem>
          </SelectContent>
        </Select>
      </div>

      <!-- Botón Aplicar -->
      <Button @click="handleApply" class="w-full" size="lg">
        <Search class="mr-2 h-4 w-4" />
        Mostrar Rendimientos
      </Button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import rendimientos from '@/routes/rendimientos';
import { router } from '@inertiajs/vue3';
import { Search } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface Props {
  selectedYear: number;
  selectedMonth: number;
}

const props = defineProps<Props>();

const months = [
  'Enero',
  'Febrero',
  'Marzo',
  'Abril',
  'Mayo',
  'Junio',
  'Julio',
  'Agosto',
  'Septiembre',
  'Octubre',
  'Noviembre',
  'Diciembre',
];

// Generar años desde 2020 hasta el año actual
const availableYears = computed(() => {
  const currentYear = new Date().getFullYear();
  const years: number[] = [];
  for (let year = currentYear; year >= 2020; year--) {
    years.push(year);
  }
  return years;
});

const localYear = ref(props.selectedYear.toString());
const localMonth = ref(props.selectedMonth.toString());

// Sincronizar con props cuando cambien
watch(
  () => props.selectedYear,
  (newVal) => {
    localYear.value = newVal.toString();
  },
);

watch(
  () => props.selectedMonth,
  (newVal) => {
    localMonth.value = newVal.toString();
  },
);

const handleApply = () => {
  router.get(
    rendimientos.index(),
    {
      selectedYear: localYear.value,
      selectedMonth: localMonth.value,
    },
    {
      preserveState: true,
      preserveScroll: true,
    },
  );
};
</script>
