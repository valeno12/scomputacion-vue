<template>
  <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
    <BuscadorSelector
      :endpoint="clienteRoutes.search().url"
      label="Seleccionar cliente"
      placeholder="Buscar por nombre, apellido o DNI…"
      :selected-id="modelValue"
      :selected-label="(modelValue ? selectedClienteText : null) || ''"
      :map-item="
        (c) => ({
          id: c.id,
          label: `${c.nombre} ${c.apellido}`,
          description: c.dni ? `DNI: ${c.dni}` : '',
          raw: c,
        })
      "
      @select="selectCliente"
    />

    <Button type="button" variant="outline" @click="showDialog = true">
      <Plus class="mr-2 h-4 w-4" />
      Crear Cliente
    </Button>

    <Dialog v-model:open="showDialog">
      <DialogContent class="max-h-[90vh] max-w-md overflow-y-auto">
        <DialogHeader>
          <DialogTitle>Crear nuevo cliente</DialogTitle>
          <DialogDescription>Complete los datos del cliente</DialogDescription>
        </DialogHeader>

        <div class="space-y-4 py-4">
          <ClienteFormFields />
        </div>

        <DialogFooter>
          <Button type="button" variant="outline" @click="cancelCreate"
            >Cancelar</Button
          >
          <Button type="button" @click="crearCliente" :disabled="creando">
            Crear Cliente
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import BuscadorSelector from '@/components/common/BuscadorSelector.vue';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import clienteRoutes from '@/routes/cliente';
import type { Cliente } from '@/types/cliente.interface';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { Plus } from 'lucide-vue-next';
import { computed, inject, provide, ref } from 'vue';
import { toast } from 'vue-sonner';
import ClienteFormFields from './ClienteFormFields.vue';

interface Props {
  modelValue: number | null;
}

defineProps<Props>();
const emit = defineEmits<{
  'update:modelValue': [value: number | null];
}>();

const clienteInicial = inject<any>('clienteInicial', null);

const showDialog = ref(false);
const clienteSeleccionado = ref<Cliente | null>(null);

if (clienteInicial) {
  clienteSeleccionado.value = clienteInicial;
  // También emitir el ID
  emit('update:modelValue', clienteInicial.id);
}

const selectedClienteText = computed(() => {
  if (!clienteSeleccionado.value) return null;
  return `${clienteSeleccionado.value.nombre} ${clienteSeleccionado.value.apellido}${clienteSeleccionado.value.dni ? ` · ${clienteSeleccionado.value.dni}` : ''}`;
});

const selectCliente = (cliente: Cliente) => {
  clienteSeleccionado.value = cliente;
  emit('update:modelValue', cliente.id);
};

const clienteForm = useForm({
  dni: '',
  nombre: '',
  apellido: '',
  mail: '',
  telefono: '',
  direccion: '',
  from_modal: true,
});

provide('clienteForm', clienteForm);

const creando = ref(false);
const crearCliente = async () => {
  if (creando.value) return;
  creando.value = true;
  clienteForm.clearErrors();
  try {
    const { data } = await axios.post(
      clienteRoutes.store().url,
      clienteForm.data(),
      { headers: { Accept: 'application/json' } },
    );
    selectCliente(data.cliente);
    showDialog.value = false;
    clienteForm.reset();
    toast.success('Cliente creado y seleccionado');
  } catch (error) {
    if (axios.isAxiosError(error) && error.response?.status === 422) {
      for (const [field, messages] of Object.entries(
        error.response.data.errors,
      ))
        clienteForm.setError(
          field as keyof typeof clienteForm.errors,
          (messages as string[])[0],
        );
    } else toast.error('No se pudo crear el cliente');
  } finally {
    creando.value = false;
  }
};

const cancelCreate = () => {
  showDialog.value = false;
  clienteForm.reset();
  clienteForm.clearErrors();
};
</script>
