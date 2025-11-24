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
            {{ selectedProveedorText || 'Seleccione un proveedor...' }}
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
              @input="buscarProveedores"
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
              v-else-if="proveedorBusqueda.length === 0"
              class="py-6 text-center text-sm text-muted-foreground"
            >
              No se encontraron proveedores.
            </div>
            <div v-else>
              <button
                v-for="proveedor in proveedorBusqueda"
                :key="proveedor.id"
                @click="selectProveedor(proveedor)"
                class="relative flex w-full cursor-pointer items-center rounded-sm px-2 py-2 text-sm outline-none select-none hover:bg-accent hover:text-accent-foreground"
              >
                <Check
                  :class="[
                    'mr-2 h-4 w-4',
                    modelValue === proveedor.id ? 'opacity-100' : 'opacity-0',
                  ]"
                />
                <div class="flex flex-col text-left">
                  <span class="font-medium">
                    {{ proveedor.nombre }}
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
            :error="form.errors.nombre"
            required
          >
            <Input
              id="nombre"
              v-model="form.nombre"
              type="text"
              placeholder="Mercado Libre"
              :disabled="form.processing"
              :class="{ 'border-destructive': form.errors.nombre }"
            />
          </FormField>
        </div>

        <DialogFooter>
          <Button variant="outline" @click="cancelCreate">Cancelar</Button>
          <Button @click="crearProveedor" :disabled="proveedorForm.processing">
            Crear Proveedor
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
import proveedorRoutes from '@/routes/proveedor';
import { Proveedor } from '@/types/proveedor.interface';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { Check, ChevronsUpDown, Plus, Search } from 'lucide-vue-next';
import { computed, provide, ref, watch } from 'vue';
import { toast } from 'vue-sonner';

interface Props {
  modelValue: number | null;
}

defineProps<Props>();
const emit = defineEmits<{
  'update:modelValue': [value: number | null];
}>();

const open = ref(false);
const showDialog = ref(false);
const searchQuery = ref('');
const proveedorBusqueda = ref<Proveedor[]>([]);
const proveedorSeleccionado = ref<Proveedor | null>(null);
const searching = ref(false);
let searchTimeout: ReturnType<typeof setTimeout>;

const selectedProveedorText = computed(() => {
  if (!proveedorSeleccionado.value) return null;
  return `${proveedorSeleccionado.value.nombre} `;
});

const buscarProveedores = () => {
  clearTimeout(searchTimeout);
  searching.value = true;

  searchTimeout = setTimeout(async () => {
    try {
      const response = await axios.get(proveedorRoutes.search().url, {
        params: { q: searchQuery.value },
      });
      proveedorBusqueda.value = response.data;
    } catch (error) {
      console.error('Error:', error);
    } finally {
      searching.value = false;
    }
  }, 300);
};

watch(open, (isOpen) => {
  if (isOpen && proveedorBusqueda.value.length === 0) {
    buscarProveedores();
  }
});

const selectProveedor = (proveedor: Proveedor) => {
  proveedorSeleccionado.value = proveedor;
  emit('update:modelValue', proveedor.id);
  open.value = false;
};

const proveedorForm = useForm({
  nombre: '',
  from_modal: true,
});

provide('proveedorForm', proveedorForm);

const crearProveedor = async () => {
  proveedorForm.post(proveedorRoutes.store().url, {
    preserveScroll: true,
    onSuccess: async () => {
      const proveedorCreado = proveedorForm.nombre;

      toast.success(`Proveedor ${proveedorForm.nombre} creado!`);
      showDialog.value = false;

      try {
        const response = await axios.get(proveedorRoutes.search().url, {
          params: { q: proveedorCreado },
        });

        const nuevoProveedor = response.data[0];

        if (nuevoProveedor) {
          selectProveedor(nuevoProveedor);
        }
      } catch (error) {
        console.error('Error:', error);
      }

      proveedorForm.reset();
    },
    onError: () => {
      toast.error('Error al crear proveedor');
    },
  });
};

const cancelCreate = () => {
  showDialog.value = false;
  proveedorForm.reset();
  proveedorForm.clearErrors();
};
</script>
