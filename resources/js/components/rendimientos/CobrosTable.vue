<template>
  <div
    class="overflow-hidden rounded-xl border bg-white shadow-sm dark:bg-gray-950"
  >
    <!-- Header -->
    <div
      class="flex items-center justify-between border-b bg-gradient-to-r from-green-50 to-emerald-50 px-6 py-4 dark:from-green-950/30 dark:to-emerald-950/30"
    >
      <div class="flex items-center gap-3">
        <div
          class="flex h-10 w-10 items-center justify-center rounded-lg bg-green-100 dark:bg-green-900/50"
        >
          <DollarSign class="h-5 w-5 text-green-600 dark:text-green-400" />
        </div>
        <div>
          <h3 class="font-semibold text-gray-900 dark:text-gray-100">
            Cobros del Mes
          </h3>
          <p class="text-sm text-gray-500 dark:text-gray-400">
            Pedidos finalizados
          </p>
        </div>
      </div>
      <div class="rounded-lg bg-green-100 px-3 py-1 dark:bg-green-900/50">
        <span class="text-sm font-semibold text-green-700 dark:text-green-300">
          {{ cobros.length }} pedidos
        </span>
      </div>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto">
      <Table>
        <TableHeader>
          <TableRow
            class="bg-gray-50 hover:bg-gray-50 dark:bg-gray-900/50 dark:hover:bg-gray-900/50"
          >
            <TableHead class="font-semibold">Código</TableHead>
            <TableHead class="font-semibold">Monto</TableHead>
            <TableHead class="font-semibold">Fecha Pago</TableHead>
            <TableHead class="text-right font-semibold">Acciones</TableHead>
          </TableRow>
        </TableHeader>

        <TableBody>
          <TableRow v-if="cobros.length === 0">
            <TableCell colspan="4" class="py-12 text-center">
              <div
                class="flex flex-col items-center gap-2 text-muted-foreground"
              >
                <DollarSign class="h-12 w-12 opacity-20" />
                <p>No hay cobros registrados</p>
              </div>
            </TableCell>
          </TableRow>

          <TableRow
            v-else
            v-for="(cobro, index) in cobros"
            :key="cobro.id"
            :class="[
              'transition-colors',
              index % 2 === 0
                ? 'bg-white dark:bg-gray-950'
                : 'bg-gray-50/50 dark:bg-gray-900/30',
              'hover:bg-green-50 dark:hover:bg-green-950/20',
            ]"
          >
            <TableCell>
              <span
                class="font-mono font-semibold text-purple-600 dark:text-purple-400"
              >
                {{ cobro.codigo }}
              </span>
            </TableCell>
            <TableCell class="font-semibold text-green-600 dark:text-green-400">
              {{ montoValue(cobro.presupuesto || 0) }}
            </TableCell>
            <TableCell class="text-sm text-muted-foreground">
              {{ formatDate(cobro.fecha_pago || '', { includeTime: false }) }}
            </TableCell>
            <TableCell class="text-right">
              <Button
                variant="ghost"
                size="sm"
                @click="handleViewPedido(cobro.id)"
                class="hover:bg-green-100 dark:hover:bg-green-900/50"
              >
                <Eye class="mr-1 h-4 w-4" />
                Ver
              </Button>
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>
    </div>

    <!-- Footer con total -->
    <div
      v-if="cobros.length > 0"
      class="border-t bg-gray-50 px-6 py-3 dark:bg-gray-900/30"
    >
      <div class="flex items-center justify-between">
        <span class="text-sm font-medium text-gray-600 dark:text-gray-400"
          >Total Cobros</span
        >
        <span class="text-lg font-bold text-green-600 dark:text-green-400">
          {{ formatMoney(totalCobros) }}
        </span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { usePrivacyMode } from '@/composables/usePrivacyMode';
import pedido from '@/routes/pedido';
import type { Pedido } from '@/types/pedido.interface';
import { formatDate, formatMoney } from '@/utils/formatter';
import { router } from '@inertiajs/vue3';
import { DollarSign, Eye } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
  cobros: Pedido[];
}

const props = defineProps<Props>();

const privacyMode = usePrivacyMode();

const montoValue = (monto: number) => {
  if (privacyMode.value) return '••••••';

  return formatMoney(monto);
};

const totalCobros = computed(() => {
  return props.cobros.reduce((sum, cobro) => {
    return sum + (cobro.presupuesto || 0);
  }, 0);
});

const handleViewPedido = (id: number) => {
  router.visit(pedido.show(id));
};
</script>
