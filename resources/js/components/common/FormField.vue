<template>
  <div class="space-y-2">
    <Label :for="id">
      {{ label }}
      <span v-if="required" class="ml-0.5 text-destructive">*</span>
    </Label>

    <slot :id="id" :error="!!error" :error-class="errorClass" />

    <p v-if="error" class="text-sm text-destructive">
      {{ error }}
    </p>

    <p v-if="hint && !error" class="text-sm text-muted-foreground">
      {{ hint }}
    </p>
  </div>
</template>

<script setup lang="ts">
import { Label } from '@/components/ui/label';
import { computed } from 'vue';

interface Props {
  id: string;
  label: string;
  error?: string;
  hint?: string;
  required?: boolean;
}

const props = defineProps<Props>();

const errorClass = computed(() => (props.error ? 'border-destructive' : ''));
</script>
