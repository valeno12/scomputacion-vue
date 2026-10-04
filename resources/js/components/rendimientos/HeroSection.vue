<template>
  <div
    class="relative overflow-hidden rounded-2xl border bg-gradient-to-br from-indigo-600 via-violet-600 to-fuchsia-600 p-5 text-white shadow-md md:p-7"
  >
    <!-- Pattern decorativo -->
    <div class="absolute inset-0 opacity-10">
      <div
        class="absolute h-full w-full bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxnIGZpbGw9IiNmZmYiIGZpbGwtb3BhY2l0eT0iMSI+PHBhdGggZD0iTTM2IDE0YzAtNi42MjctNS4zNzMtMTItMTItMTJzLTEyIDUuMzczLTEyIDEyIDUuMzczIDEyIDEyIDEyIDEyLTUuMzczIDEyLTEyem0wIDM2YzAtNi42MjctNS4zNzMtMTItMTItMTJzLTEyIDUuMzczLTEyIDEyIDUuMzczIDEyIDEyIDEyIDEyLTUuMzczIDEyLTEyem0zNi0zNmMwLTYuNjI3LTUuMzczLTEyLTEyLTEycy0xMiA1LjM3My0xMiAxMiA1LjM3MyAxMiAxMiAxMiAxMi01LjM3MyAxMi0xMnptMCAzNmMwLTYuNjI3LTUuMzczLTEyLTEyLTEycy0xMiA1LjM3My0xMiAxMiA1LjM3MyAxMiAxMiAxMiAxMi01LjM3MyAxMi0xMnoiLz48L2c+PC9nPjwvc3ZnPg==')] opacity-20"
      />
    </div>

    <div class="relative">
      <div class="grid items-center gap-6 lg:grid-cols-[1.15fr_1fr]">
        <!-- Left: Título y Ganancia destacada -->
        <div class="space-y-4">
          <div>
            <p
              v-if="titular"
              class="mb-2 text-xs font-medium tracking-wide text-white/80"
            >
              Finanzas de {{ titular }}
            </p>
            <h1 class="text-3xl font-bold tracking-tight">Rendimientos</h1>
            <p class="mt-1 text-sm text-white/80">
              {{ getMonthName(selectedMonth) }} {{ selectedYear }}
            </p>
          </div>

          <div class="space-y-2">
            <p
              class="text-sm font-medium tracking-wider text-white/70 uppercase"
            >
              Resultado del mes
            </p>
            <p class="text-4xl font-bold tracking-tight md:text-5xl">
              {{ gananciaMensual }}
            </p>
            <p class="text-sm text-white/70">
              Pedidos: ganancia cobrada. Productos: tu parte cobrada menos tus
              compras.
            </p>
          </div>
        </div>

        <!-- Right: Filtros -->
        <div class="flex items-end">
          <div
            class="w-full space-y-3 rounded-xl border border-white/30 bg-white/10 p-4 shadow-lg backdrop-blur-md md:p-5"
          >
            <div class="grid gap-4 sm:grid-cols-2">
              <!-- Año -->
              <div class="space-y-2">
                <label
                  for="rendimientos-year"
                  class="text-sm font-semibold text-white"
                  >Año</label
                >
                <Select v-model="localYear">
                  <SelectTrigger
                    id="rendimientos-year"
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
                <label
                  for="rendimientos-month"
                  class="text-sm font-semibold text-white"
                  >Mes</label
                >
                <Select v-model="localMonth">
                  <SelectTrigger
                    id="rendimientos-month"
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
              class="w-full bg-white font-semibold text-purple-600 shadow-lg transition-all hover:bg-white/95 hover:shadow-md"
              size="lg"
            >
              <Search class="mr-2 h-4 w-4" />
              Ver rendimientos
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
import { usePrivacyMode } from '@/composables/usePrivacyMode';
import rendimientos from '@/routes/rendimientos';
import { formatMoney } from '@/utils/formatter';
import { router } from '@inertiajs/vue3';
import { Search } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface Props {
  selectedYear: number;
  selectedMonth: number;
  gananciaMes: number;
  titular?: string;
}

const props = defineProps<Props>();
const privacyMode = usePrivacyMode();

const gananciaMensual = computed(() => {
  if (privacyMode.value) return '••••••';

  return formatMoney(props.gananciaMes);
});

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
