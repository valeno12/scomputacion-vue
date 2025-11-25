<script setup lang="ts">
import NavMain from '@/components/NavMain.vue';
import {
  Sidebar,
  SidebarContent,
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
  Cpu,
  DollarSign,
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
  </Sidebar>
  <slot />
</template>
