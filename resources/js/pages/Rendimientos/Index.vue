<template>
  <AppLayout :breadcrumbs="breadcrumbs" title="Rendimientos por Mes">
    <div class="space-y-8 py-8">
      <!-- Hero Section con Filtros -->
      <HeroSection
        :selected-year="selectedYear"
        :selected-month="selectedMonth"
        :ganancia-mes="gananciaMes"
      />

      <!-- Stats Cards Grid -->
      <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
        <StatsCard
          label="Gastos del Mes"
          :value="totalGastosMes"
          variant="danger"
          :icon="ShoppingCart"
        />

        <StatsCard
          label="Cobros del Mes"
          :value="totalCobrosMes"
          variant="success"
          :icon="DollarSign"
        />

        <StatsCard
          label="Trabajos Entregados"
          :value="pedidosEntregadosMes"
          variant="info"
          :icon="Package"
          :format-as-currency="false"
        />

        <StatsCard
          label="Promedio por Trabajo"
          :value="promedioGananciaPorPedido"
          variant="primary"
          :icon="TrendingUp"
        />
      </div>

      <!-- Tablas Grid -->
      <div class="grid gap-6 lg:grid-cols-2">
        <GastosTable :gastos="gastosPorMesDetalles" />
        <CobrosTable :cobros="cobrosPorMesDetalles" />
      </div>

      <!-- Gráfico de Proveedores -->
      <ProveedoresChart :proveedores="proveedores" />
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import CobrosTable from '@/components/rendimientos/CobrosTable.vue';
import GastosTable from '@/components/rendimientos/GastosTable.vue';
import HeroSection from '@/components/rendimientos/HeroSection.vue';
import ProveedoresChart from '@/components/rendimientos/ProveedoresChart.vue';
import StatsCard from '@/components/rendimientos/StatsCard.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import rendimientos from '@/routes/rendimientos';
import { BreadcrumbItem } from '@/types';
import { RendimientosPageProps } from '@/types/rendimientos.interfaces';
import { DollarSign, Package, ShoppingCart, TrendingUp } from 'lucide-vue-next';
import { computed } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Rendimientos',
    href: rendimientos.index().url,
  },
];
const props = defineProps<RendimientosPageProps>();

const totalGastosMes = computed(() => {
  return props.gastosPorMes.reduce((sum, item) => sum + item.total_gastos, 0);
});

const totalCobrosMes = computed(() => {
  return props.gananciasPorMes.reduce(
    (sum, item) => sum + item.total_ganancias,
    0,
  );
});
</script>
