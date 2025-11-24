<template>
  <div class="flex items-center justify-between">
    <!-- Paso 1 -->
    <div class="flex items-center gap-2">
      <div
        :class="[
          'flex h-8 w-8 items-center justify-center rounded-full border-2',
          currentStepId === 'datos-iniciales'
            ? 'border-primary bg-primary text-primary-foreground'
            : 'border-muted bg-background text-muted-foreground',
        ]"
      >
        <span>1</span>
      </div>
      <span
        :class="
          currentStepId === 'datos-iniciales'
            ? 'font-semibold'
            : 'text-muted-foreground'
        "
      >
        Datos Iniciales
      </span>
    </div>

    <!-- Línea divisora -->
    <div class="mx-4 h-[2px] flex-1 bg-muted" />

    <!-- Paso 2 -->
    <div class="flex items-center gap-2">
      <div
        :class="[
          'flex h-8 w-8 items-center justify-center rounded-full border-2',
          currentStepId === 'presupuesto'
            ? 'border-primary bg-primary text-primary-foreground'
            : 'border-muted bg-background text-muted-foreground',
        ]"
      >
        <span>2</span>
      </div>
      <span
        :class="
          currentStepId === 'presupuesto'
            ? 'font-semibold'
            : 'text-muted-foreground'
        "
      >
        Presupuesto
      </span>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Props {
  currentStepId: string;
}

defineProps<Props>();
</script>
