<script setup lang="ts">
import { Toaster } from '@/components/ui/sonner';
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItemType } from '@/types';

interface Props {
  breadcrumbs?: BreadcrumbItemType[];
  stickyActions?: boolean;
}

withDefaults(defineProps<Props>(), {
  breadcrumbs: () => [],
});
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs" :sticky-actions="stickyActions">
    <slot />
  </AppLayout>
  <Toaster position="top-right" richColors />
</template>
