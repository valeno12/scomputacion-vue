<template>
  <!-- DNI -->
  <FormField id="dni" label="DNI" :error="form.errors.dni" required>
    <Input
      id="dni"
      v-model="form.dni"
      type="text"
      placeholder="12345678"
      :disabled="form.processing"
      :class="{ 'border-destructive': form.errors.dni }"
    />
  </FormField>

  <!-- Nombre y Apellido en 2 columnas -->
  <div class="grid gap-4 sm:grid-cols-2">
    <FormField id="nombre" label="Nombre" :error="form.errors.nombre" required>
      <Input
        id="nombre"
        v-model="form.nombre"
        type="text"
        placeholder="Juan"
        :disabled="form.processing"
        :class="{ 'border-destructive': form.errors.nombre }"
      />
    </FormField>

    <FormField
      id="apellido"
      label="Apellido"
      :error="form.errors.apellido"
      required
    >
      <Input
        id="apellido"
        v-model="form.apellido"
        type="text"
        placeholder="Pérez"
        :disabled="form.processing"
        :class="{ 'border-destructive': form.errors.apellido }"
      />
    </FormField>
  </div>

  <!-- Email -->
  <FormField
    id="mail"
    label="Correo Electrónico"
    :error="form.errors.mail"
    required
  >
    <Input
      id="mail"
      v-model="form.mail"
      type="email"
      placeholder="cliente@ejemplo.com"
      :disabled="form.processing"
      :class="{ 'border-destructive': form.errors.mail }"
    />
  </FormField>

  <!-- Teléfono -->
  <FormField
    id="telefono"
    label="Teléfono"
    :error="form.errors.telefono"
    required
  >
    <Input
      id="telefono"
      v-model="form.telefono"
      type="text"
      placeholder="+54 343 123 4567"
      :disabled="form.processing"
      :class="{ 'border-destructive': form.errors.telefono }"
    />
  </FormField>

  <!-- Dirección -->
  <FormField
    id="direccion"
    label="Dirección"
    :error="form.errors.direccion"
    required
  >
    <Input
      id="direccion"
      v-model="form.direccion"
      type="text"
      placeholder="Calle 123, Ciudad"
      :disabled="form.processing"
      :class="{ 'border-destructive': form.errors.direccion }"
    />
  </FormField>
</template>

<script setup lang="ts">
import FormField from '@/components/common/FormField.vue';
import { Input } from '@/components/ui/input';
import { inject } from 'vue';

const form = inject<any>('clienteForm');
</script>
