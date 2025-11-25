<template>
  <div
    class="group relative overflow-hidden rounded-xl border bg-gradient-to-br p-6 shadow-sm transition-all duration-300 hover:shadow-lg"
    :class="gradientClass"
  >
    <!-- Decorative element -->
    <div
      class="absolute top-0 right-0 h-32 w-32 translate-x-8 -translate-y-8 rounded-full opacity-20 blur-2xl"
      :class="decorClass"
    />

    <div class="relative space-y-3">
      <!-- Icon y valor -->
      <div class="flex items-start justify-between">
        <div
          class="flex h-12 w-12 items-center justify-center rounded-lg transition-transform duration-300 group-hover:scale-110"
          :class="iconBgClass"
        >
          <component :is="icon" class="h-6 w-6" :class="iconColorClass" />
        </div>

        <div
          v-if="showTrend"
          class="flex items-center gap-1 text-xs font-medium"
          :class="trendColorClass"
        >
          <TrendingUp v-if="trend === 'up'" class="h-3 w-3" />
          <TrendingDown v-else class="h-3 w-3" />
          <span>{{ trendValue }}%</span>
        </div>
      </div>

      <!-- Label -->
      <p class="text-sm font-medium text-muted-foreground">
        {{ label }}
      </p>

      <!-- Value -->
      <p class="text-3xl font-bold tracking-tight" :class="valueColorClass">
        {{ formattedValue }}
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { usePrivacyMode } from '@/composables/usePrivacyMode';
import { formatMoney } from '@/utils/formatter';
import { TrendingDown, TrendingUp } from 'lucide-vue-next';
import { computed, type Component } from 'vue';

interface Props {
  label: string;
  value: number;
  variant?: 'success' | 'danger' | 'warning' | 'info' | 'primary';
  icon: Component;
  formatAsCurrency?: boolean;
  trend?: 'up' | 'down';
  trendValue?: number;
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'primary',
  formatAsCurrency: true,
  trendValue: 0,
});

const showTrend = computed(() => props.trend && props.trendValue !== 0);

const isPrivacyMode = usePrivacyMode();

const formattedValue = computed(() => {
  if (isPrivacyMode.value) {
    return '••••••';
  }

  if (props.formatAsCurrency) {
    return formatMoney(props.value);
  }
  return props.value.toLocaleString('es-AR');
});

const gradientClass = computed(() => {
  const classes = {
    success:
      'from-green-50 to-emerald-50 dark:from-green-950/20 dark:to-emerald-950/20 border-green-200 dark:border-green-800',
    danger:
      'from-red-50 to-rose-50 dark:from-red-950/20 dark:to-rose-950/20 border-red-200 dark:border-red-800',
    warning:
      'from-yellow-50 to-amber-50 dark:from-yellow-950/20 dark:to-amber-950/20 border-yellow-200 dark:border-yellow-800',
    info: 'from-blue-50 to-cyan-50 dark:from-blue-950/20 dark:to-cyan-950/20 border-blue-200 dark:border-blue-800',
    primary:
      'from-purple-50 to-fuchsia-50 dark:from-purple-950/20 dark:to-fuchsia-950/20 border-purple-200 dark:border-purple-800',
  };
  return classes[props.variant];
});

const decorClass = computed(() => {
  const classes = {
    success: 'bg-green-400',
    danger: 'bg-red-400',
    warning: 'bg-yellow-400',
    info: 'bg-blue-400',
    primary: 'bg-purple-400',
  };
  return classes[props.variant];
});

const iconBgClass = computed(() => {
  const classes = {
    success: 'bg-green-100 dark:bg-green-900/50',
    danger: 'bg-red-100 dark:bg-red-900/50',
    warning: 'bg-yellow-100 dark:bg-yellow-900/50',
    info: 'bg-blue-100 dark:bg-blue-900/50',
    primary: 'bg-purple-100 dark:bg-purple-900/50',
  };
  return classes[props.variant];
});

const iconColorClass = computed(() => {
  const classes = {
    success: 'text-green-600 dark:text-green-400',
    danger: 'text-red-600 dark:text-red-400',
    warning: 'text-yellow-600 dark:text-yellow-400',
    info: 'text-blue-600 dark:text-blue-400',
    primary: 'text-purple-600 dark:text-purple-400',
  };
  return classes[props.variant];
});

const valueColorClass = computed(() => {
  const classes = {
    success: 'text-green-900 dark:text-green-100',
    danger: 'text-red-900 dark:text-red-100',
    warning: 'text-yellow-900 dark:text-yellow-100',
    info: 'text-blue-900 dark:text-blue-100',
    primary: 'text-purple-900 dark:text-purple-100',
  };
  return classes[props.variant];
});

const trendColorClass = computed(() => {
  return props.trend === 'up'
    ? 'text-green-600 dark:text-green-400'
    : 'text-red-600 dark:text-red-400';
});
</script>
