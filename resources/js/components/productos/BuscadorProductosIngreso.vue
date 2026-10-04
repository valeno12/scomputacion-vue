<script setup lang="ts">
import BuscadorSelector from '@/components/common/BuscadorSelector.vue';
import { Button } from '@/components/ui/button';
import productos from '@/routes/productos_nuevo';
import type { ProductoNuevo } from '@/types/producto-nuevo.interface';
import { Plus } from 'lucide-vue-next';
import { ref } from 'vue';
defineProps<{
  seleccionados: number[];
  disabled?: boolean;
  refreshKey?: number;
}>();
defineEmits<{ agregar: [producto: ProductoNuevo]; crear: [nombre: string] }>();
const query = ref('');
const option = (p: ProductoNuevo) => ({
  id: p.id,
  label: p.nombre,
  description: `${p.marca || 'Sin marca'} · Stock: ${p.cantidad_disponible ?? 0}`,
  raw: p,
});
</script>
<template>
  <div class="flex flex-col gap-2 sm:flex-row">
    <div class="min-w-0 flex-1">
      <BuscadorSelector
        :key="refreshKey"
        :endpoint="productos.buscar().url"
        label="Buscar productos"
        placeholder="Buscar por nombre o marca…"
        :disabled="disabled"
        :disabled-ids="seleccionados"
        :map-item="option"
        @select="$emit('agregar', $event)"
        @query="query = $event"
      />
    </div>
    <Button
      type="button"
      variant="outline"
      :disabled="disabled"
      @click="$emit('crear', query.trim())"
      ><Plus class="size-4" /> Crear producto</Button
    >
  </div>
</template>
