<template>
  <div
    v-if="hasData"
    class="overflow-hidden rounded-xl border bg-white shadow-sm dark:bg-gray-950"
  >
    <!-- Header -->
    <div
      class="flex items-center justify-between border-b bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 dark:from-blue-950/30 dark:to-indigo-950/30"
    >
      <div class="flex items-center gap-3">
        <div
          class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/50"
        >
          <PieChartIcon class="h-5 w-5 text-blue-600 dark:text-blue-400" />
        </div>
        <div>
          <h3 class="font-semibold text-gray-900 dark:text-gray-100">
            Distribución por Proveedor
          </h3>
          <p class="text-sm text-gray-500 dark:text-gray-400">
            Compras realizadas en el período
          </p>
        </div>
      </div>
    </div>

    <!-- Chart -->
    <div class="p-8">
      <div class="mx-auto grid gap-8 lg:grid-cols-2">
        <!-- Gráfico -->
        <div class="flex items-center justify-center">
          <div style="max-width: 300px; max-height: 300px">
            <Doughnut :data="chartData" :options="chartOptions" />
          </div>
        </div>

        <!-- Lista de proveedores -->
        <div class="space-y-3">
          <div
            v-for="item in groupedData"
            :key="item.id"
            class="flex items-center justify-between rounded-lg border p-3 transition-colors hover:bg-gray-50 dark:hover:bg-gray-900/50"
          >
            <div class="flex items-center gap-3">
              <div
                class="h-3 w-3 rounded-full"
                :style="{ backgroundColor: item.color }"
              />
              <div>
                <span class="font-medium">{{ item.nombre }}</span>
                <span
                  v-if="item.isOtros && item.count > 1"
                  class="ml-2 text-xs text-muted-foreground"
                >
                  ({{ item.count }} proveedores)
                </span>
              </div>
            </div>
            <div class="flex items-center gap-3">
              <span class="text-sm text-muted-foreground">
                {{ item.cantidad_pedidos }} pedidos
              </span>
              <span class="text-sm font-semibold">
                {{ getPercentage(item.cantidad_pedidos) }}%
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ProveedorStats } from '@/types/rendimientos.interfaces';
import {
  ArcElement,
  Chart as ChartJS,
  Legend,
  Tooltip,
  type ChartData,
  type ChartOptions,
} from 'chart.js';
import { PieChart as PieChartIcon } from 'lucide-vue-next';
import { computed } from 'vue';
import { Doughnut } from 'vue-chartjs';

ChartJS.register(ArcElement, Tooltip, Legend);

interface Props {
  proveedores: ProveedorStats[];
}

const props = defineProps<Props>();

const hasData = computed(() => props.proveedores.length > 0);

const colors = [
  'rgba(59, 130, 246, 0.8)', // blue
  'rgba(139, 92, 246, 0.8)', // violet
  'rgba(236, 72, 153, 0.8)', // pink
  'rgba(34, 197, 94, 0.8)', // green
  'rgba(251, 146, 60, 0.8)', // orange
  'rgba(14, 165, 233, 0.8)', // sky
  'rgba(168, 85, 247, 0.8)', // purple
  'rgba(239, 68, 68, 0.8)', // red
  'rgba(99, 102, 241, 0.8)', // indigo
  'rgba(245, 158, 11, 0.8)', // amber
];

const otrosColor = 'rgba(156, 163, 175, 0.8)'; // gray

const sortedProveedores = computed(() => {
  return [...props.proveedores].sort(
    (a, b) => b.cantidad_pedidos - a.cantidad_pedidos,
  );
});

const totalPedidos = computed(() => {
  return props.proveedores.reduce((sum, p) => sum + p.cantidad_pedidos, 0);
});

const getPercentage = (cantidad: number) => {
  return ((cantidad / totalPedidos.value) * 100).toFixed(1);
};

// Agrupar proveedores menores al 5% en "Otros"
const groupedData = computed(() => {
  const threshold = totalPedidos.value * 0.05; // 5%
  const main: any[] = [];
  let otrosTotal = 0;
  let otrosCount = 0;

  sortedProveedores.value.forEach((proveedor, index) => {
    if (proveedor.cantidad_pedidos >= threshold) {
      main.push({
        id: `proveedor-${proveedor.proveedor_id}`,
        nombre: proveedor.proveedor.nombre,
        cantidad_pedidos: proveedor.cantidad_pedidos,
        color: colors[index % colors.length],
        isOtros: false,
        count: 1,
      });
    } else {
      otrosTotal += proveedor.cantidad_pedidos;
      otrosCount++;
    }
  });

  // Agregar "Otros" si hay proveedores agrupados
  if (otrosCount > 0) {
    main.push({
      id: 'otros',
      nombre: 'Otros',
      cantidad_pedidos: otrosTotal,
      color: otrosColor,
      isOtros: true,
      count: otrosCount,
    });
  }

  return main;
});

const chartData = computed<ChartData<'doughnut'>>(() => {
  const labels = groupedData.value.map((item) => item.nombre);
  const data = groupedData.value.map((item) => item.cantidad_pedidos);
  const backgroundColors = groupedData.value.map((item) => item.color);

  return {
    labels,
    datasets: [
      {
        data,
        backgroundColor: backgroundColors,
        borderColor: '#ffffff',
        borderWidth: 2,
        hoverOffset: 8,
      },
    ],
  };
});

const chartOptions = computed<ChartOptions<'doughnut'>>(() => ({
  responsive: true,
  maintainAspectRatio: true,
  cutout: '65%',
  plugins: {
    legend: {
      display: false,
    },
    tooltip: {
      backgroundColor: 'rgba(0, 0, 0, 0.8)',
      padding: 12,
      cornerRadius: 8,
      titleFont: {
        size: 14,
        weight: 'bold',
      },
      bodyFont: {
        size: 13,
      },
      callbacks: {
        label: (context) => {
          const value = context.parsed || 0;
          const percentage = ((value / totalPedidos.value) * 100).toFixed(1);
          return ` ${value} pedidos (${percentage}%)`;
        },
      },
    },
  },
}));
</script>
