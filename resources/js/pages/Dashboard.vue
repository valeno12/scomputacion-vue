<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import rendimientos from '@/routes/rendimientos';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import {
  CheckCircle,
  Clock,
  Package,
  TrendingDown,
  TrendingUp,
} from 'lucide-vue-next';

interface EstadoConteo {
  count: number;
  nombre: string;
  color: string;
}

interface Props {
  conteoPedidosPorEstado?: Record<string, EstadoConteo>;
  estadisticasMes?: {
    pedidosEsteMes: number;
    pedidosCompletados: number;
    tendencia: number;
    mesNombre: string;
  };
}

const props = withDefaults(defineProps<Props>(), {
  conteoPedidosPorEstado: () => ({}),
  estadisticasMes: () => ({
    pedidosEsteMes: 0,
    pedidosCompletados: 0,
    tendencia: 0,
    mesNombre: 'Sin datos',
  }),
});

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Dashboard',
    href: dashboard().url,
  },
];

// Calcular totales con verificación
const totalPedidos = props.conteoPedidosPorEstado
  ? Object.values(props.conteoPedidosPorEstado).reduce(
      (sum, estado) => sum + estado.count,
      0,
    )
  : 0;

const pedidosEnProceso = props.conteoPedidosPorEstado
  ? Object.values(props.conteoPedidosPorEstado)
      .filter((e) => !['Entregado', 'Cancelado'].includes(e.nombre))
      .reduce((sum, estado) => sum + estado.count, 0)
  : 0;

// Mapeo de colores para estados
const getColorClasses = (nombre: string) => {
  const colorMap: Record<string, { bg: string; text: string; icon: string }> = {
    Pendiente: {
      bg: 'bg-yellow-50 dark:bg-yellow-900/20',
      text: 'text-yellow-700 dark:text-yellow-300',
      icon: 'bg-yellow-100 dark:bg-yellow-800',
    },
    'En Preparación': {
      bg: 'bg-blue-50 dark:bg-blue-900/20',
      text: 'text-blue-700 dark:text-blue-300',
      icon: 'bg-blue-100 dark:bg-blue-800',
    },
    'Listo para Enviar': {
      bg: 'bg-purple-50 dark:bg-purple-900/20',
      text: 'text-purple-700 dark:text-purple-300',
      icon: 'bg-purple-100 dark:bg-purple-800',
    },
    'En Camino': {
      bg: 'bg-orange-50 dark:bg-orange-900/20',
      text: 'text-orange-700 dark:text-orange-300',
      icon: 'bg-orange-100 dark:bg-orange-800',
    },
    Entregado: {
      bg: 'bg-green-50 dark:bg-green-900/20',
      text: 'text-green-700 dark:text-green-300',
      icon: 'bg-green-100 dark:bg-green-800',
    },
    Cancelado: {
      bg: 'bg-red-50 dark:bg-red-900/20',
      text: 'text-red-700 dark:text-red-300',
      icon: 'bg-red-100 dark:bg-red-800',
    },
  };

  return (
    colorMap[nombre] || {
      bg: 'bg-gray-50 dark:bg-gray-900/20',
      text: 'text-gray-700 dark:text-gray-300',
      icon: 'bg-gray-100 dark:bg-gray-800',
    }
  );
};
</script>

<template>
  <Head title="Dashboard" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto p-4">
      <!-- Tarjetas Superiores -->
      <div class="grid auto-rows-min gap-4 md:grid-cols-3">
        <!-- Pedidos en Proceso -->
        <div
          class="rounded-xl border border-blue-200 bg-gradient-to-br from-blue-50 to-blue-100/50 p-6 shadow-sm dark:border-blue-800 dark:from-blue-950 dark:to-blue-900/50"
        >
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-blue-700 dark:text-blue-300">
                Pedidos en Proceso
              </p>
              <p
                class="mt-2 text-4xl font-bold text-blue-900 dark:text-blue-100"
              >
                {{ pedidosEnProceso }}
              </p>
              <p class="mt-1 text-xs text-blue-600 dark:text-blue-400">
                En preparación y envío
              </p>
            </div>
            <div class="rounded-full bg-blue-200 p-4 dark:bg-blue-800">
              <Clock class="h-8 w-8 text-blue-600 dark:text-blue-300" />
            </div>
          </div>
        </div>

        <!-- Completados Este Mes -->
        <div
          class="rounded-xl border border-green-200 bg-gradient-to-br from-green-50 to-green-100/50 p-6 shadow-sm dark:border-green-800 dark:from-green-950 dark:to-green-900/50"
        >
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-green-700 dark:text-green-300">
                Completados Este Mes
              </p>
              <p
                class="mt-2 text-4xl font-bold text-green-900 dark:text-green-100"
              >
                {{ estadisticasMes.pedidosCompletados }}
              </p>
              <p class="mt-1 text-xs text-green-600 dark:text-green-400">
                {{ estadisticasMes.mesNombre }}
              </p>
            </div>
            <div class="rounded-full bg-green-200 p-4 dark:bg-green-800">
              <CheckCircle class="h-8 w-8 text-green-600 dark:text-green-300" />
            </div>
          </div>
        </div>

        <!-- Total de Pedidos -->
        <div
          class="rounded-xl border border-purple-200 bg-gradient-to-br from-purple-50 to-purple-100/50 p-6 shadow-sm dark:border-purple-800 dark:from-purple-950 dark:to-purple-900/50"
        >
          <div class="flex items-center justify-between">
            <div>
              <p
                class="text-sm font-medium text-purple-700 dark:text-purple-300"
              >
                Total de Pedidos
              </p>
              <p
                class="mt-2 text-4xl font-bold text-purple-900 dark:text-purple-100"
              >
                {{ totalPedidos }}
              </p>
              <p class="mt-1 text-xs text-purple-600 dark:text-purple-400">
                Todos los estados
              </p>
            </div>
            <div class="rounded-full bg-purple-200 p-4 dark:bg-purple-800">
              <Package class="h-8 w-8 text-purple-600 dark:text-purple-300" />
            </div>
          </div>
        </div>
      </div>

      <!-- MOTIVACIÓN "DO IT FOR HIM" - MÁS COMPACTO, FOTO MÁS GRANDE -->
      <div
        class="relative overflow-hidden rounded-xl border-4 border-yellow-400 bg-gradient-to-br from-yellow-50 via-orange-50 to-red-50 px-4 py-6 shadow-lg dark:border-yellow-500 dark:from-yellow-950/40 dark:via-orange-950/40 dark:to-red-950/40"
      >
        <!-- Decoración de fondo -->
        <div
          class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxnIGZpbGw9IiNmZmYiIGZpbGwtb3BhY2l0eT0iMC4xIj48cGF0aCBkPSJNMzYgMzRjMC0yLjIxLTEuNzktNC00LTRzLTQgMS43OS00IDQgMS43OSA0IDQgNCA0LTEuNzkgNC00em0wLTEwYzAtMi4yMS0xLjc5LTQtNC00cy00IDEuNzktNCA0IDEuNzkgNCA0IDQgNC0xLjc5IDQtNHpNMTIgMjRjMC0yLjIxLTEuNzktNC00LTRzLTQgMS43OS00IDQgMS43OSA0IDQgNCA0LTEuNzkgNC00em0yNCAxMGMwLTIuMjEtMS43OS00LTQtNHMtNCAxLjc5LTQgNCAxLjc5IDQgNCA0IDQtMS43OSA0LTR6Ii8+PC9nPjwvZz48L3N2Zz4=')] opacity-30"
        ></div>

        <div
          class="relative flex flex-col items-center justify-center space-y-3 text-center"
        >
          <!-- Marco de la foto - MÁS GRANDE -->
          <div class="relative">
            <!-- Sombra del marco -->
            <div
              class="absolute -inset-3 rounded-2xl bg-yellow-400/30 blur-xl dark:bg-yellow-500/30"
            ></div>

            <!-- Marco principal - FOTO GRANDE -->
            <div
              class="relative overflow-hidden rounded-2xl border-8 border-yellow-400 bg-white p-2 shadow-2xl dark:border-yellow-500 dark:bg-gray-900"
            >
              <img
                src="/images/motivation.jpg"
                alt="Motivación"
                class="h-auto w-full max-w-2xl rounded-lg object-cover"
              />
            </div>

            <!-- Estrellitas decorativas más chicas -->
            <div class="absolute -top-3 -right-3 animate-pulse text-4xl">
              ⭐
            </div>
            <div
              class="absolute -bottom-3 -left-3 animate-pulse text-4xl"
              style="animation-delay: 0.5s"
            >
              ⭐
            </div>
          </div>

          <!-- Mensaje más compacto -->
          <div class="space-y-1">
            <p
              class="text-lg font-bold text-orange-700 italic dark:text-orange-400"
            >
              "Emm me chupa un huevo"
            </p>
            <p class="text-sm font-medium text-yellow-700 dark:text-yellow-500">
              Cada pedido cuenta ❤️
            </p>
          </div>
        </div>
      </div>

      <!-- Rendimientos del Mes -->
      <Link
        :href="rendimientos.index().url"
        class="group relative rounded-xl border bg-card p-6 shadow-sm transition-all hover:scale-[1.01] hover:shadow-lg"
      >
        <div class="mb-4 flex items-center justify-between">
          <h3 class="text-lg font-semibold">
            Rendimiento de {{ estadisticasMes.mesNombre }}
          </h3>
          <div
            :class="[
              'flex items-center gap-1 rounded-full px-3 py-1.5 text-sm font-medium shadow-sm',
              estadisticasMes.tendencia >= 0
                ? 'bg-gradient-to-r from-green-100 to-green-200 text-green-800 dark:from-green-900 dark:to-green-800 dark:text-green-200'
                : 'bg-gradient-to-r from-red-100 to-red-200 text-red-800 dark:from-red-900 dark:to-red-800 dark:text-red-200',
            ]"
          >
            <TrendingUp v-if="estadisticasMes.tendencia >= 0" class="h-4 w-4" />
            <TrendingDown v-else class="h-4 w-4" />
            {{ Math.abs(estadisticasMes.tendencia) }}%
          </div>
        </div>

        <div class="space-y-4">
          <div
            class="rounded-lg border bg-gradient-to-br from-primary/5 to-primary/10 p-4"
          >
            <p class="text-sm text-muted-foreground">Pedidos este mes</p>
            <p class="text-3xl font-bold">
              {{ estadisticasMes.pedidosEsteMes }}
            </p>
          </div>

          <!-- Pedidos por Estado -->
          <div class="space-y-2">
            <p class="text-sm font-semibold text-muted-foreground">
              Distribución por Estado
            </p>
            <div
              v-if="
                conteoPedidosPorEstado &&
                Object.keys(conteoPedidosPorEstado).length > 0
              "
              class="grid gap-2 md:grid-cols-2"
            >
              <div
                v-for="(estado, id) in conteoPedidosPorEstado"
                :key="id"
                :class="[
                  'flex items-center justify-between rounded-lg border p-3 transition-all hover:scale-[1.02]',
                  getColorClasses(estado.nombre).bg,
                ]"
              >
                <div class="flex items-center gap-2">
                  <div
                    :class="[
                      'h-2 w-2 rounded-full',
                      getColorClasses(estado.nombre).icon,
                    ]"
                  ></div>
                  <span
                    :class="[
                      'text-sm font-medium',
                      getColorClasses(estado.nombre).text,
                    ]"
                  >
                    {{ estado.nombre }}
                  </span>
                </div>
                <span
                  :class="[
                    'rounded-full px-3 py-1 text-sm font-bold shadow-sm',
                    getColorClasses(estado.nombre).icon,
                    getColorClasses(estado.nombre).text,
                  ]"
                >
                  {{ estado.count }}
                </span>
              </div>
            </div>
            <div
              v-else
              class="rounded-lg border-2 border-dashed py-8 text-center text-sm text-muted-foreground"
            >
              <Package class="mx-auto mb-2 h-12 w-12 opacity-30" />
              No hay pedidos registrados
            </div>
          </div>
        </div>

        <div
          class="absolute right-4 bottom-4 text-xs font-medium text-primary opacity-0 transition-opacity group-hover:opacity-100"
        >
          Ver detalles completos →
        </div>
      </Link>
    </div>
  </AppLayout>
</template>
