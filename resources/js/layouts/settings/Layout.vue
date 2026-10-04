<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { toUrl, urlIsActive } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editProfile } from '@/routes/profile';
import { edit as editRepartos } from '@/routes/repartos';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
withDefaults(defineProps<{ wide?: boolean }>(), { wide: false });

const sidebarNavItems: NavItem[] = [
  {
    title: 'Perfil',
    href: editProfile(),
  },
  {
    title: 'Apariencia',
    href: editAppearance(),
  },
  {
    title: 'Repartos',
    href: editRepartos(),
  },
];
</script>

<template>
  <div class="px-4 py-6">
    <Heading
      title="Configuración"
      description="Modifica tu cuenta y la configuración"
    />

    <div class="flex flex-col lg:flex-row lg:space-x-12">
      <aside class="w-full max-w-xl shrink-0 lg:w-48">
        <nav class="flex flex-col space-y-1 space-x-0">
          <Button
            v-for="item in sidebarNavItems"
            :key="toUrl(item.href)"
            variant="ghost"
            :class="[
              'w-full justify-start',
              { 'bg-muted': urlIsActive(item.href, page.url) },
            ]"
            as-child
          >
            <Link :href="item.href">
              <component :is="item.icon" class="h-4 w-4" />
              {{ item.title }}
            </Link>
          </Button>
        </nav>
      </aside>

      <Separator class="my-6 lg:hidden" />

      <div class="min-w-0 flex-1" :class="wide ? 'max-w-6xl' : 'md:max-w-2xl'">
        <section class="space-y-12" :class="{ 'max-w-xl': !wide }">
          <slot />
        </section>
      </div>
    </div>
  </div>
</template>
