<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { SidebarTrigger } from '@/components/ui/sidebar';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { getInitials } from '@/composables/useInitials';
import { usePrivacyMode } from '@/composables/usePrivacyMode';
import type { BreadcrumbItemType } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { Eye, EyeOff } from 'lucide-vue-next';
import { computed } from 'vue';

withDefaults(
  defineProps<{
    breadcrumbs?: BreadcrumbItemType[];
  }>(),
  {
    breadcrumbs: () => [],
  },
);

const page = usePage();
const auth = computed(() => page.props.auth);
const isPrivacyMode = usePrivacyMode();
</script>

<template>
  <header
    class="flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4"
  >
    <div class="flex items-center gap-2">
      <SidebarTrigger class="-ml-1" />
      <template v-if="breadcrumbs && breadcrumbs.length > 0">
        <Breadcrumbs :breadcrumbs="breadcrumbs" />
      </template>
    </div>

    <div class="ml-auto flex items-center space-x-2">
      <Button
        variant="ghost"
        size="icon"
        class="group h-9 w-9 cursor-pointer"
        @click="isPrivacyMode = !isPrivacyMode"
      >
        <EyeOff
          v-if="isPrivacyMode"
          class="size-5 opacity-80 group-hover:opacity-100"
        />
        <Eye v-else class="size-5 opacity-80 group-hover:opacity-100" />
      </Button>

      <DropdownMenu>
        <DropdownMenuTrigger :as-child="true">
          <Button
            variant="ghost"
            size="icon"
            class="relative size-10 w-auto rounded-full p-1 focus-within:ring-2 focus-within:ring-primary"
          >
            <Avatar class="size-8 overflow-hidden rounded-full">
              <AvatarImage
                v-if="auth.user.avatar"
                :src="auth.user.avatar"
                :alt="auth.user.name"
              />
              <AvatarFallback
                class="rounded-lg bg-neutral-200 font-semibold text-black dark:bg-neutral-700 dark:text-white"
              >
                {{ getInitials(auth.user?.name) }}
              </AvatarFallback>
            </Avatar>
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-56">
          <UserMenuContent :user="auth.user" />
        </DropdownMenuContent>
      </DropdownMenu>
    </div>
  </header>
</template>
