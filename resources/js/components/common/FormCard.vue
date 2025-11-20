<!-- components/common/FormCard.vue -->
<template>
  <form @submit.prevent="onSubmit" class="space-y-6">
    <Card>
      <CardHeader>
        <CardTitle>{{ cardTitle }}</CardTitle>
        <CardDescription>{{ cardDescription }}</CardDescription>
      </CardHeader>

      <CardContent class="space-y-4">
        <slot />
      </CardContent>

      <CardFooter class="flex justify-end gap-2">
        <Button
          type="button"
          variant="outline"
          :disabled="isSubmitting"
          @click="onCancel"
        >
          Cancelar
        </Button>

        <Button type="submit" :disabled="isSubmitting || !isDirty">
          <Loader2 v-if="isSubmitting" class="mr-2 h-4 w-4 animate-spin" />
          {{ isSubmitting ? 'Guardando...' : `Guardar ${entityName}` }}
        </Button>
      </CardFooter>
    </Card>
  </form>
</template>

<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { Loader2 } from 'lucide-vue-next';

interface Props {
  cardTitle: string;
  cardDescription: string;
  entityName: string;
  isSubmitting: boolean;
  isDirty?: boolean; // Nuevo prop
}

interface Emits {
  (e: 'submit'): void;
  (e: 'cancel'): void;
}

withDefaults(defineProps<Props>(), {
  isDirty: true, // Por defecto habilitado (para create)
});

const emit = defineEmits<Emits>();

const onSubmit = () => emit('submit');
const onCancel = () => emit('cancel');
</script>
