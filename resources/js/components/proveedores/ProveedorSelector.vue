<template>
  <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
    <BuscadorSelector
      :endpoint="proveedorRoutes.search().url"
      label="Seleccionar proveedor"
      placeholder="Buscar por nombre…"
      :selected-id="modelValue"
      :selected-label="selectedProveedorText || ''"
      :map-item="(p) => ({ id: p.id, label: p.nombre, raw: p })"
      @select="selectProveedor"
    />

    <Button type="button" variant="outline" @click="showDialog = true">
      <Plus class="mr-2 h-4 w-4" />
      Crear Proveedor
    </Button>

    <Dialog v-model:open="showDialog">
      <DialogContent class="max-h-[90vh] max-w-md overflow-y-auto">
        <DialogHeader>
          <DialogTitle>Crear nuevo proveedor</DialogTitle>
          <DialogDescription
            >Complete los datos del proveedor</DialogDescription
          >
        </DialogHeader>

        <div class="space-y-4 py-4">
          <FormField
            id="nombre"
            label="Nombre"
            :error="proveedorForm.errors.nombre"
            required
          >
            <Input
              id="nombre"
              v-model="proveedorForm.nombre"
              type="text"
              placeholder="Mercado Libre"
              :disabled="creando"
              :class="{ 'border-destructive': proveedorForm.errors.nombre }"
            />
          </FormField>
        </div>

        <DialogFooter>
          <Button type="button" variant="outline" @click="cancelCreate"
            >Cancelar</Button
          >
          <Button type="button" @click="crearProveedor" :disabled="creando">
            Crear Proveedor
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import BuscadorSelector from '@/components/common/BuscadorSelector.vue';
import FormField from '@/components/common/FormField.vue';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import proveedorRoutes from '@/routes/proveedor';
import { Proveedor } from '@/types/proveedor.interface';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { Plus } from 'lucide-vue-next';
import { computed, provide, ref } from 'vue';
import { toast } from 'vue-sonner';

interface Props {
  modelValue: number | null;
}

defineProps<Props>();
const emit = defineEmits<{
  'update:modelValue': [value: number | null];
}>();

const showDialog = ref(false);
const proveedorSeleccionado = ref<Proveedor | null>(null);
const creando = ref(false);

const selectedProveedorText = computed(() => {
  if (!proveedorSeleccionado.value) return null;
  return `${proveedorSeleccionado.value.nombre} `;
});

const selectProveedor = (proveedor: Proveedor) => {
  proveedorSeleccionado.value = proveedor;
  emit('update:modelValue', proveedor.id);
};

const proveedorForm = useForm({
  nombre: '',
  from_modal: true,
});

provide('proveedorForm', proveedorForm);

const crearProveedor = async () => {
  if (creando.value) return;
  creando.value = true;
  proveedorForm.clearErrors();
  try {
    const { data } = await axios.post(
      proveedorRoutes.store().url,
      proveedorForm.data(),
      { headers: { Accept: 'application/json' } },
    );
    selectProveedor(data.proveedor);
    showDialog.value = false;
    proveedorForm.reset();
    toast.success('Proveedor creado y seleccionado');
  } catch (error) {
    if (axios.isAxiosError(error) && error.response?.status === 422)
      proveedorForm.setError('nombre', error.response.data.errors.nombre?.[0]);
    else toast.error('No se pudo crear el proveedor');
  } finally {
    creando.value = false;
  }
};

const cancelCreate = () => {
  showDialog.value = false;
  proveedorForm.reset();
  proveedorForm.clearErrors();
};
</script>
