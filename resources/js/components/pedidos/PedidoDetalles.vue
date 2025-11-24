<template>
  <div
    class="overflow-hidden rounded-xl border bg-white shadow-sm dark:bg-gray-950"
  >
    <div
      class="border-b bg-gradient-to-r from-blue-50 to-cyan-50 px-6 py-4 dark:from-blue-950/30 dark:to-cyan-950/30"
    >
      <div class="flex items-center gap-3">
        <div
          class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/50"
        >
          <FileText class="h-5 w-5 text-blue-600 dark:text-blue-400" />
        </div>
        <div>
          <h3 class="font-semibold">Información del Pedido</h3>
          <p class="text-xs text-muted-foreground">
            Datos del equipo y presupuesto
          </p>
        </div>
      </div>
    </div>

    <div class="p-6">
      <div class="grid gap-6 md:grid-cols-2">
        <!-- Columna izquierda: Datos básicos -->
        <dl class="space-y-4">
          <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-900/50">
            <dt
              class="text-xs font-medium tracking-wider text-muted-foreground uppercase"
            >
              Código
            </dt>
            <dd
              class="mt-1 font-mono text-lg font-bold text-blue-600 dark:text-blue-400"
            >
              {{ pedido.codigo }}
            </dd>
          </div>

          <div>
            <dt class="text-sm font-medium text-muted-foreground">Cliente</dt>
            <dd class="mt-1 text-sm font-medium">
              {{ pedido.cliente.nombre }} {{ pedido.cliente.apellido }}
            </dd>
          </div>

          <div>
            <dt class="text-sm font-medium text-muted-foreground">DNI</dt>
            <dd class="mt-1 text-sm">{{ pedido.cliente.dni }}</dd>
          </div>

          <div>
            <dt class="text-sm font-medium text-muted-foreground">Equipo</dt>
            <dd class="mt-1 text-sm font-medium">{{ pedido.equipo }}</dd>
          </div>

          <div>
            <dt class="text-sm font-medium text-muted-foreground">
              Estado de Ingreso
            </dt>
            <dd class="mt-1 text-sm">{{ pedido.estado_ingreso }}</dd>
          </div>

          <div>
            <dt class="text-sm font-medium text-muted-foreground">
              Fecha de Ingreso
            </dt>
            <dd class="mt-1 text-sm">
              {{ formatDate(pedido.created_at) }}
            </dd>
          </div>

          <div>
            <dt class="text-sm font-medium text-muted-foreground">Cargador</dt>
            <dd class="mt-1">
              <Badge
                :variant="pedido.cargador ? 'default' : 'outline'"
                :class="
                  pedido.cargador
                    ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
                    : ''
                "
              >
                {{ pedido.cargador ? 'Sí' : 'No' }}
              </Badge>
            </dd>
          </div>
        </dl>

        <!-- Columna derecha: Presupuesto (solo si existe) -->
        <dl v-if="pedido.trabajo_realizar" class="space-y-4">
          <div
            class="rounded-lg bg-gradient-to-br from-purple-50 to-pink-50 p-4 dark:from-purple-950/20 dark:to-pink-950/20"
          >
            <dt class="text-sm font-medium text-muted-foreground">
              Trabajo a Realizar
            </dt>
            <dd class="mt-1 text-sm font-medium">
              {{ pedido.trabajo_realizar }}
            </dd>
          </div>

          <div
            v-if="pedido.componente_problematico"
            class="rounded-lg bg-orange-50 p-3 dark:bg-orange-950/20"
          >
            <dt
              class="text-sm font-medium text-orange-700 dark:text-orange-400"
            >
              Componente Problemático
            </dt>
            <dd class="mt-1 text-sm font-medium">
              {{ pedido.componente_problematico }}
            </dd>
          </div>

          <div
            class="rounded-lg border-2 border-green-200 bg-gradient-to-br from-green-50 to-emerald-50 p-4 shadow-sm dark:border-green-800 dark:from-green-950/20 dark:to-emerald-950/20"
          >
            <div class="space-y-3 text-sm">
              <div class="flex justify-between">
                <span class="font-medium text-muted-foreground"
                  >Mano de Obra:</span
                >
                <span class="font-semibold">
                  {{ formatMoney(pedido.costo_mano_obra || 0) }}
                </span>
              </div>
              <div class="flex justify-between">
                <span class="font-medium text-muted-foreground"
                  >Costo Productos:</span
                >
                <span class="font-semibold">
                  {{ formatMoney(pedido.costo || 0) }}
                </span>
              </div>
              <div class="flex justify-between">
                <span class="font-medium text-muted-foreground">Ganancia:</span>
                <span class="font-semibold text-green-600 dark:text-green-400">
                  {{ formatMoney(pedido.ganancia || 0) }}
                </span>
              </div>
              <div
                class="flex justify-between border-t-2 border-green-300 pt-3 dark:border-green-700"
              >
                <span class="text-base font-bold">Presupuesto Total:</span>
                <span
                  class="text-xl font-bold text-green-600 dark:text-green-400"
                >
                  {{ formatMoney(pedido.presupuesto || 0) }}
                </span>
              </div>
            </div>
          </div>

          <div
            v-if="pedido.fecha_pago"
            class="rounded-lg bg-blue-50 p-3 dark:bg-blue-950/20"
          >
            <dt class="text-sm font-medium text-blue-700 dark:text-blue-400">
              Fecha de Pago
            </dt>
            <dd class="mt-1 text-sm font-medium">
              {{ formatDate(pedido.fecha_pago) }}
            </dd>
          </div>
        </dl>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import type { Pedido } from '@/types/pedido.interface';
import { formatDate, formatMoney } from '@/utils/formatter';
import { FileText } from 'lucide-vue-next';

interface Props {
  pedido: Pedido & {
    cliente: {
      nombre: string;
      apellido: string;
      dni: string;
    };
  };
}

defineProps<Props>();
</script>
