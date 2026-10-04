<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
defineProps<{
  links: { url: string | null; label: string; active: boolean }[];
}>();
const etiqueta = (label: string) =>
  label
    .replace(/&laquo;|&raquo;/g, '')
    .replace('Previous', 'Anterior')
    .replace('Next', 'Siguiente');
</script>
<template>
  <nav class="flex flex-wrap gap-2" aria-label="Paginación">
    <template v-for="(link, i) in links" :key="i"
      ><Link
        v-if="link.url"
        :href="link.url"
        :aria-current="link.active ? 'page' : undefined"
        class="rounded border px-3 py-2"
        :class="{ 'bg-primary text-primary-foreground': link.active }"
        >{{ etiqueta(link.label) }}</Link
      ></template
    >
  </nav>
</template>
