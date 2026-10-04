<script setup lang="ts">
import FormCard from '@/components/common/FormCard.vue';
import IngresoStockFields from '@/components/productos/IngresoStockFields.vue';
import PrecioProductoFields from '@/components/productos/PrecioProductoFields.vue';
import RevisarIngreso from '@/components/productos/RevisarIngreso.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import FormLayout from '@/layouts/FormLayout.vue';
import productos from '@/routes/productos_nuevo';
import { hoy, nuevaClave, type Participante } from '@/types/comercio';
import type { ProductoNuevo } from '@/types/producto-nuevo.interface';
import { porcentajeInicial } from '@/utils/precioProducto';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, provide, ref } from 'vue';
import { toast } from 'vue-sonner';
const props = defineProps<{
  producto: ProductoNuevo;
  participantes: Participante[];
  ultimoCosto: number | null;
}>();
const form = useForm({
  cantidad: 1,
  costo: props.ultimoCosto === null ? '' : props.ultimoCosto / 100,
  comprador_id: '' as number | '',
  proveedor: '',
  tipo: 'compra',
  fecha: hoy(),
  clave: nuevaClave(),
  porcentaje_ganancia: porcentajeInicial(
    props.producto.porcentaje_ganancia,
    props.ultimoCosto,
    props.producto.precio_venta_centavos,
  ),
});
provide('ingresoProductoForm', form);
const reviewing = ref(false);
const itemsResumen = computed(() => [
  {
    nombre: props.producto.nombre,
    marca: props.producto.marca,
    cantidad: form.cantidad,
    costo: form.costo,
    porcentaje_ganancia: form.porcentaje_ganancia,
  },
]);
const comprador = computed(
  () =>
    props.participantes.find((p) => p.id === form.comprador_id)?.nombre ?? '',
);
const submit = () =>
  form.post(productos.ingresos.store({ producto: props.producto.id }).url, {
    onSuccess: () => toast.success('Ingreso de stock registrado'),
    onError: () => {
      reviewing.value = false;
    },
  });
</script>
<template>
  <Head title="Ingresar stock" />
  <AppLayout
    :breadcrumbs="[
      { title: 'Productos', href: productos.index().url },
      {
        title: 'Ingresar stock',
        href: productos.ingreso({ producto: producto.id }).url,
      },
    ]"
  >
    <FormLayout
      title="Ingresar stock"
      :description="`${producto.nombre}${producto.marca ? ' · ' + producto.marca : ''}`"
    >
      <FormCard
        card-title="Datos del ingreso"
        card-description="Las unidades se suman al stock de este producto."
        entity-name="Ingreso"
        submit-label="Revisar ingreso"
        :is-submitting="form.processing"
        @submit="reviewing = true"
        @cancel="router.visit(productos.index().url)"
      >
        <IngresoStockFields
          :participantes="participantes"
          :show-costo="false"
        />
        <PrecioProductoFields
          v-model:costo="form.costo"
          v-model:porcentaje="form.porcentaje_ganancia"
          :disabled="form.processing"
          :errors="form.errors"
        />
      </FormCard>
    </FormLayout>
  </AppLayout>
  <RevisarIngreso
    v-model:open="reviewing"
    :items="itemsResumen"
    :comprador="comprador"
    :proveedor="form.proveedor"
    :fecha="form.fecha"
    :tipo="form.tipo"
    :processing="form.processing"
    @confirmar="submit"
  />
</template>
