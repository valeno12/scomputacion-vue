<template>
  <div class="flex h-full flex-1 flex-col gap-6 p-6">
    <div class="mx-auto w-full max-w-4xl space-y-6">
      <!-- Header -->
      <div>
        <h2 class="text-3xl font-bold tracking-tight">{{ title }}</h2>
        <p class="text-muted-foreground">{{ description }}</p>
      </div>

      <!-- Slot para el contenido (FormCard, etc) -->
      <slot />
    </div>
  </div>
</template>

<script setup lang="ts">
interface Props {
  title: string;
  description: string;
}

defineProps<Props>();
</script>
