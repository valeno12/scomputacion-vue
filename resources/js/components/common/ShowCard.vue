<template>
  <Card>
    <CardHeader v-if="cardTitle || cardDescription">
      <div class="flex items-center justify-between">
        <div>
          <CardTitle v-if="cardTitle">{{ cardTitle }}</CardTitle>
          <CardDescription v-if="cardDescription">
            {{ cardDescription }}
          </CardDescription>
        </div>
        <slot name="header-actions" />
      </div>
    </CardHeader>

    <CardContent>
      <slot />
    </CardContent>

    <CardFooter v-if="$slots.footer" class="flex justify-end gap-2">
      <slot name="footer" />
    </CardFooter>
  </Card>
</template>

<script setup lang="ts">
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';

interface Props {
  cardTitle?: string;
  cardDescription?: string;
}

defineProps<Props>();
</script>
