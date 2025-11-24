<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarHeader,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import cliente from '@/routes/cliente';
import movimientosStock from '@/routes/movimientos-stock';
import pedido from '@/routes/pedido';
import producto from '@/routes/producto';
import proveedor from '@/routes/proveedor';
import rendimientos from '@/routes/rendimientos';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import {
  BookOpen,
  Cpu,
  DollarSign,
  Folder,
  LayoutGrid,
  Package,
  PcCase,
  Truck,
  User,
} from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

const mainNavItems: NavItem[] = [
  {
    title: 'Dashboard',
    href: dashboard(),
    icon: LayoutGrid,
  },
  {
    title: 'Pedidos',
    href: pedido.index(),
    icon: PcCase,
  },
  {
    title: 'Productos',
    href: producto.index(),
    icon: Cpu,
  },
  {
    title: 'Clientes',
    href: cliente.index(),
    icon: User,
  },
  {
    title: 'Proveedores',
    href: proveedor.index(),
    icon: Truck,
  },
  {
    title: 'Movimientos de Stock',
    href: movimientosStock.index(),
    icon: Package,
  },
  {
    title: 'Rendimientos',
    href: rendimientos.index(),
    icon: DollarSign,
  },
];

const footerNavItems: NavItem[] = [
  {
    title: 'Github Repo',
    href: 'https://github.com/laravel/vue-starter-kit',
    icon: Folder,
  },
  {
    title: 'Documentation',
    href: 'https://laravel.com/docs/starter-kits#vue',
    icon: BookOpen,
  },
];
</script>

<template>
  <Sidebar collapsible="icon" variant="inset">
    <SidebarHeader>
      <SidebarMenu>
        <SidebarMenuItem>
          <SidebarMenuButton size="lg" as-child>
            <Link :href="dashboard()">
              <AppLogo />
            </Link>
          </SidebarMenuButton>
        </SidebarMenuItem>
      </SidebarMenu>
    </SidebarHeader>

    <SidebarContent>
      <NavMain :items="mainNavItems" />
    </SidebarContent>

    <SidebarFooter>
      <NavFooter :items="footerNavItems" />
      <NavUser />
    </SidebarFooter>
  </Sidebar>
  <slot />
</template>
