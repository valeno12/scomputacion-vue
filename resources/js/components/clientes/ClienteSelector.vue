<template>
  <div class="grid grid-cols-2 gap-2">
    <Popover v-model:open="open">
      <PopoverTrigger as-child>
        <Button
          variant="outline"
          role="combobox"
          :aria-expanded="open"
          class="justify-between"
        >
          <span class="truncate">
            {{ selectedClienteText || 'Seleccione un cliente...' }}
          </span>
          <ChevronsUpDown class="ml-2 h-4 w-4 shrink-0 opacity-50" />
        </Button>
      </PopoverTrigger>
      <PopoverContent class="w-[500px] p-0" align="start">
        <div class="flex flex-col">
          <!-- Input de búsqueda -->
          <div class="flex items-center border-b px-3">
            <Search class="mr-2 h-4 w-4 shrink-0 opacity-50" />
            <input
              v-model="searchQuery"
              @input="buscarClientes"
              class="flex h-10 w-full rounded-md bg-transparent py-3 text-sm outline-none placeholder:text-muted-foreground disabled:cursor-not-allowed disabled:opacity-50"
              placeholder="Buscar por nombre, apellido o DNI..."
            />
          </div>

          <!-- Lista de resultados -->
          <div class="max-h-[300px] overflow-y-auto p-1">
            <div
              v-if="searching"
              class="py-6 text-center text-sm text-muted-foreground"
            >
              Buscando...
            </div>
            <div
              v-else-if="clientesBusqueda.length === 0"
              class="py-6 text-center text-sm text-muted-foreground"
            >
              No se encontraron clientes.
            </div>
            <div v-else>
              <button
                v-for="cliente in clientesBusqueda"
                :key="cliente.id"
                @click="selectCliente(cliente)"
                class="relative flex w-full cursor-pointer items-center rounded-sm px-2 py-2 text-sm outline-none select-none hover:bg-accent hover:text-accent-foreground"
              >
                <Check
                  :class="[
                    'mr-2 h-4 w-4',
                    modelValue === cliente.id ? 'opacity-100' : 'opacity-0',
                  ]"
                />
                <div class="flex flex-col text-left">
                  <span class="font-medium">
                    {{ cliente.nombre }} {{ cliente.apellido }}
                  </span>
                  <span class="text-xs text-muted-foreground">
                    DNI: {{ cliente.dni }}
                  </span>
                </div>
              </button>
            </div>
          </div>
        </div>
      </PopoverContent>
    </Popover>

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
          <Button variant="outline" @click="cancelCreate">Cancelar</Button>
          <Button @click="crearCliente" :disabled="clienteForm.processing">
            Crear Cliente
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from '@/components/ui/popover';
import clienteRoutes from '@/routes/cliente';
import type { Cliente } from '@/types/cliente.interface';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { Check, ChevronsUpDown, Plus, Search } from 'lucide-vue-next';
import { computed, inject, provide, ref, watch } from 'vue';
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

const open = ref(false);
const showDialog = ref(false);
const searchQuery = ref('');
const clientesBusqueda = ref<Cliente[]>([]);
const clienteSeleccionado = ref<Cliente | null>(null);
const searching = ref(false);
let searchTimeout: ReturnType<typeof setTimeout>;

if (clienteInicial) {
  clienteSeleccionado.value = clienteInicial;
  // También emitir el ID
  emit('update:modelValue', clienteInicial.id);
}

const selectedClienteText = computed(() => {
  if (!clienteSeleccionado.value) return null;
  return `${clienteSeleccionado.value.nombre} ${clienteSeleccionado.value.apellido} - ${clienteSeleccionado.value.dni}`;
});

const buscarClientes = () => {
  clearTimeout(searchTimeout);
  searching.value = true;

  searchTimeout = setTimeout(async () => {
    try {
      const response = await axios.get(clienteRoutes.search().url, {
        params: { q: searchQuery.value },
      });
      clientesBusqueda.value = response.data;
    } catch (error) {
      console.error('Error:', error);
    } finally {
      searching.value = false;
    }
  }, 300);
};

// Cargar clientes al abrir
watch(open, (isOpen) => {
  if (isOpen && clientesBusqueda.value.length === 0) {
    buscarClientes();
  }
});

const selectCliente = (cliente: Cliente) => {
  clienteSeleccionado.value = cliente;
  emit('update:modelValue', cliente.id);
  open.value = false;
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

const crearCliente = async () => {
  clienteForm.post(clienteRoutes.store().url, {
    preserveScroll: true,
    onSuccess: async () => {
      const dniCreado = clienteForm.dni;

      toast.success(
        `¡Cliente ${clienteForm.nombre} ${clienteForm.apellido} creado!`,
      );
      showDialog.value = false;

      // Buscar el cliente recién creado
      try {
        const response = await axios.get(clienteRoutes.search().url, {
          params: { q: dniCreado },
        });

        const nuevoCliente = response.data[0];

        if (nuevoCliente) {
          selectCliente(nuevoCliente);
        }
      } catch (error) {
        console.error('Error:', error);
      }

      clienteForm.reset();
    },
    onError: () => {
      toast.error('Error al crear cliente');
    },
  });
};

const cancelCreate = () => {
  showDialog.value = false;
  clienteForm.reset();
  clienteForm.clearErrors();
};
</script>
