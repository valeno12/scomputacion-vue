<template>
  <div
    class="relative overflow-hidden rounded-2xl border bg-gradient-to-br from-indigo-500 via-purple-500 to-pink-500 p-8 text-white shadow-xl"
  >
    <!-- Pattern decorativo -->
    <div class="absolute inset-0 opacity-10">
      <div
        class="absolute h-full w-full bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxnIGZpbGw9IiNmZmYiIGZpbGwtb3BhY2l0eT0iMSI+PHBhdGggZD0iTTM2IDE0YzAtNi42MjctNS4zNzMtMTItMTItMTJzLTEyIDUuMzczLTEyIDEyIDUuMzczIDEyIDEyIDEyIDEyLTUuMzczIDEyLTEyem0wIDM2YzAtNi42MjctNS4zNzMtMTItMTItMTJzLTEyIDUuMzczLTEyIDEyIDUuMzczIDEyIDEyIDEyIDEyLTUuMzczIDEyLTEyem0zNi0zNmMwLTYuNjI3LTUuMzczLTEyLTEyLTEycy0xMiA1LjM3My0xMiAxMiA1LjM3MyAxMiAxMiAxMiAxMi01LjM3MyAxMi0xMnptMCAzNmMwLTYuNjI3LTUuMzczLTEyLTEyLTEycy0xMiA1LjM3My0xMiAxMiA1LjM3MyAxMiAxMiAxMiAxMi01LjM3MyAxMi0xMnoiLz48L2c+PC9nPjwvc3ZnPg==')] opacity-20"
      />
    </div>

    <div class="relative">
      <div class="grid gap-8 lg:grid-cols-2">
        <!-- Left: Título y Ganancia destacada -->
        <div class="space-y-6">
          <div>
            <h1 class="text-4xl font-bold tracking-tight">Rendimientos</h1>
            <p class="mt-2 text-lg text-white/80">
              {{ getMonthName(selectedMonth) }} {{ selectedYear }}
            </p>
          </div>

          <div class="space-y-2">
            <p
              class="text-sm font-medium tracking-wider text-white/70 uppercase"
            >
              Ganancia Neta del Mes
            </p>
            <p class="text-5xl font-bold tracking-tight">
              {{ formatMoney(gananciaMes) }}
            </p>
            <p class="text-sm text-white/70">Cobros menos gastos del período</p>
          </div>
        </div>

        <!-- Right: Filtros -->
        <div class="flex items-end">
          <div
            class="w-full space-y-4 rounded-xl border border-white/30 bg-white/20 p-6 shadow-lg backdrop-blur-md"
          >
            <div class="grid gap-4 sm:grid-cols-2">
              <!-- Año -->
              <div class="space-y-2">
                <label class="text-sm font-semibold text-white">Año</label>
                <Select v-model="localYear">
                  <SelectTrigger
                    class="border-white/40 bg-white/90 font-medium text-gray-900 shadow-md backdrop-blur-sm transition-colors hover:bg-white"
                  >
                    <SelectValue />
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

              <!-- Mes -->
              <div class="space-y-2">
                <label class="text-sm font-semibold text-white">Mes</label>
                <Select v-model="localMonth">
                  <SelectTrigger
                    class="border-white/40 bg-white/90 font-medium text-gray-900 shadow-md backdrop-blur-sm transition-colors hover:bg-white"
                  >
                    <SelectValue />
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
            </div>

            <Button
              @click="handleApply"
              class="w-full bg-white font-semibold text-purple-600 shadow-lg transition-all hover:bg-white/95 hover:shadow-xl"
              size="lg"
            >
              <Search class="mr-2 h-4 w-4" />
              Actualizar Rendimientos
            </Button>
          </div>
        </div>
      </div>
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
import { formatMoney } from '@/utils/formatter';
import { router } from '@inertiajs/vue3';
import { Search } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface Props {
  selectedYear: number;
  selectedMonth: number;
  gananciaMes: number;
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

const getMonthName = (month: number) => months[month - 1];

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
