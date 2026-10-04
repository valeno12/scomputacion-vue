<script setup lang="ts">
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
import productos from '@/routes/productos_nuevo';
import type { ProductoNuevo } from '@/types/producto-nuevo.interface';
import axios from 'axios';
import { computed, reactive, ref, watch } from 'vue';
const open = defineModel<boolean>('open', { required: true });
const props = defineProps<{ nombreInicial?: string }>();
const emit = defineEmits<{ creado: [producto: ProductoNuevo] }>();
const form = reactive({ nombre: '', marca: '' });
const errors = ref<Record<string, string>>({});
const processing = ref(false);
const visible = computed({
  get: () => open.value,
  set: (value) => {
    if (!processing.value) open.value = value;
  },
});
watch(open, (value) => {
  if (value) {
    form.nombre = props.nombreInicial ?? '';
    form.marca = '';
    errors.value = {};
  }
});
async function guardar() {
  if (processing.value) return;
  processing.value = true;
  errors.value = {};
  try {
    const { data } = await axios.post<{ producto: ProductoNuevo }>(
      productos.store().url,
      form,
      { headers: { Accept: 'application/json' } },
    );
    emit('creado', data.producto);
    open.value = false;
  } catch (error) {
    if (axios.isAxiosError(error) && error.response?.status === 422) {
      for (const [field, messages] of Object.entries(
        error.response.data.errors as Record<string, string[]>,
      ))
        errors.value[field] = messages[0];
    } else
      errors.value.general =
        'No se pudo crear el producto. Tus datos siguen acá; podés volver a intentar.';
  } finally {
    processing.value = false;
  }
}
</script>
<template>
  <Dialog v-model:open="visible">
    <DialogContent>
      <DialogHeader
        ><DialogTitle>Crear producto</DialogTitle
        ><DialogDescription
          >Se guardará en el catálogo y se agregará a este ingreso. Después
          completá cantidad, costo y porcentaje.</DialogDescription
        ></DialogHeader
      >
      <form class="space-y-5" @submit.prevent="guardar">
        <FormField
          id="nuevo-producto-nombre"
          label="Nombre"
          :error="errors.nombre"
          required
          ><Input
            id="nuevo-producto-nombre"
            v-model="form.nombre"
            maxlength="200"
            :disabled="processing"
            required
        /></FormField>
        <FormField
          id="nuevo-producto-marca"
          label="Marca"
          :error="errors.marca"
          required
          ><Input
            id="nuevo-producto-marca"
            v-model="form.marca"
            maxlength="100"
            :disabled="processing"
            required
        /></FormField>
        <p v-if="errors.general" role="alert" class="text-sm text-destructive">
          {{ errors.general }}
        </p>
        <DialogFooter
          ><Button
            type="button"
            variant="outline"
            :disabled="processing"
            @click="open = false"
            >Cancelar</Button
          ><Button type="submit" :disabled="processing">{{
            processing ? 'Creando…' : 'Crear y agregar al ingreso'
          }}</Button></DialogFooter
        >
      </form>
    </DialogContent>
  </Dialog>
</template>
