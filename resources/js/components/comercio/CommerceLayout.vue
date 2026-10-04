<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
defineProps<{ title: string; description?: string }>();
const page = usePage();
</script>
<template>
  <Head :title="title" />
  <AppLayout
    :breadcrumbs="[
      { title: 'Productos', href: '/productos' },
      { title, href: '#' },
    ]"
  >
    <main class="commerce mx-auto w-full max-w-7xl space-y-6 p-4 md:p-8">
      <div>
        <h1 class="text-3xl font-semibold tracking-tight">{{ title }}</h1>
        <p v-if="description" class="mt-2 text-muted-foreground">
          {{ description }}
        </p>
      </div>
      <div
        v-if="Object.keys(page.props.errors).length"
        role="alert"
        class="rounded-lg border border-destructive p-4 text-destructive"
      >
        <p v-for="(error, key) in page.props.errors" :key="key">{{ error }}</p>
      </div>
      <slot />
    </main>
  </AppLayout>
</template>
