<script setup lang="ts">
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import RepartoEditor from '@/components/comercio/RepartoEditor.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { edit as editProfile } from '@/routes/profile';
import { edit, update } from '@/routes/repartos';
import type { BreadcrumbItem } from '@/types';
import type { OpcionesComercio, Participante } from '@/types/comercio';
import { Head, useForm } from '@inertiajs/vue3';
import { Check, Loader2, Pencil, Percent, Plus, Users } from 'lucide-vue-next';
import { ref } from 'vue';
import { toast } from 'vue-sonner';

const props = defineProps<
  Omit<OpcionesComercio, 'lotes' | 'proveedores'> & {
    todosParticipantes: Participante[];
  }
>();
const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Configuración', href: editProfile().url },
  { title: 'Repartos', href: edit().url },
];
const persona = useForm({ nombre: '', activo: true });
const editing = ref<number | null>(null);
const showParticipant = ref(false);
const reparto = useForm({
  reparto_mercaderia: props.repartoMercaderia.map((row) => ({ ...row })),
});
function openParticipant(person?: Participante) {
  persona.reset();
  persona.clearErrors();
  editing.value = person?.id ?? null;
  if (person) {
    persona.nombre = person.nombre;
    persona.activo = person.activo;
  }
  showParticipant.value = true;
}
function saveParticipant() {
  const options = {
    preserveScroll: true,
    onSuccess: () => {
      showParticipant.value = false;
      toast.success(
        editing.value ? 'Participante actualizado' : 'Participante agregado',
      );
    },
  };
  if (editing.value)
    persona.put(`/comercio/participantes/${editing.value}`, options);
  else persona.post('/comercio/participantes', options);
}
function saveDistribution() {
  reparto.put(update().url, {
    preserveScroll: true,
    onSuccess: () => {
      reparto.defaults();
      toast.success('Repartos guardados');
    },
  });
}
function errorsFor(field: string) {
  return [
    ...new Set(
      Object.entries(reparto.errors)
        .filter(([key]) => key === field || key.startsWith(`${field}.`))
        .map(([, message]) => message),
    ),
  ];
}
</script>

<template>
  <Head title="Repartos · Configuración" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <SettingsLayout wide>
      <div class="space-y-8">
        <div class="flex items-center gap-3">
          <span
            class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-blue-100 text-blue-600 dark:bg-blue-900/40 dark:text-blue-300"
            ><Percent class="size-5"
          /></span>
          <HeadingSmall
            title="Repartos"
            description="Definí quiénes participan y cómo se distribuye la ganancia."
          />
        </div>
        <section aria-labelledby="participants-title" class="space-y-4">
          <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="space-y-1">
              <h3 id="participants-title" class="text-sm font-medium">
                Participantes
              </h3>
              <p class="text-sm text-muted-foreground">
                El participante principal es fijo: sus importes son los que se
                muestran en Rendimientos.
              </p>
            </div>
            <Button variant="outline" size="sm" @click="openParticipant()"
              ><Plus class="size-4" /> Agregar</Button
            >
          </div>
          <ul
            v-if="todosParticipantes.length"
            class="grid gap-3 sm:grid-cols-2"
          >
            <li
              v-for="person in todosParticipantes"
              :key="person.id"
              class="flex min-w-0 items-center gap-3 rounded-lg border bg-muted/30 px-4 py-3"
            >
              <span
                class="min-w-0 flex-1 truncate text-sm font-medium"
                :title="person.nombre"
                >{{ person.nombre }}</span
              >
              <Badge
                variant="outline"
                :class="
                  person.activo
                    ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950/70 dark:text-emerald-300'
                    : ''
                "
                >{{
                  person.id === titular?.id
                    ? 'Principal · fijo'
                    : person.activo
                      ? 'Activo'
                      : 'Inactivo'
                }}</Badge
              >
              <Button
                variant="ghost"
                size="icon"
                class="size-8 text-muted-foreground"
                :aria-label="`Editar a ${person.nombre}`"
                @click="openParticipant(person)"
                ><Pencil class="size-4"
              /></Button>
            </li>
          </ul>
          <div
            v-else
            class="flex items-start gap-3 rounded-lg border border-dashed p-4"
          >
            <Users class="mt-0.5 size-5 shrink-0 text-muted-foreground" />
            <div class="space-y-1">
              <p class="text-sm font-medium">Todavía no hay participantes</p>
              <p class="text-sm text-muted-foreground">
                Agregá la primera persona para configurar los repartos.
              </p>
            </div>
          </div>
        </section>
        <Separator />
        <form class="space-y-5" @submit.prevent="saveDistribution">
          <HeadingSmall
            title="Repartos predeterminados"
            description="Se aplican a nuevas ventas y productos agregados a pedidos."
          />
          <div class="grid items-stretch gap-5 lg:grid-cols-2">
            <RepartoEditor
              v-model="reparto.reparto_mercaderia"
              :participantes="participantes"
              label="Productos"
              tone="blue"
              :errors="errorsFor('reparto_mercaderia')"
              :disabled="reparto.processing"
            />
            <div
              class="rounded-xl border border-amber-200 bg-amber-50/60 p-5 dark:border-amber-900 dark:bg-amber-950/20"
            >
              <h3 class="font-semibold">Repuestos y mano de obra</h3>
              <p
                class="mt-3 text-2xl font-semibold text-amber-700 dark:text-amber-300"
              >
                100 % para {{ titular?.nombre }}
              </p>
              <p class="mt-3 text-sm text-muted-foreground">
                El titular compra los repuestos y recibe toda la ganancia de la
                reparación. Sólo se reparte la ganancia de los productos del
                inventario.
              </p>
            </div>
          </div>
          <p class="text-sm text-muted-foreground">
            Los cambios no modifican el reparto de las operaciones ya
            registradas.
          </p>
          <div class="flex flex-wrap items-center gap-3">
            <Button
              type="submit"
              class="bg-blue-600 text-white hover:bg-blue-700"
              :disabled="reparto.processing || !participantes.length"
            >
              <Loader2 v-if="reparto.processing" class="size-4 animate-spin" />
              {{ reparto.processing ? 'Guardando…' : 'Guardar repartos' }}
            </Button>
            <span
              v-if="reparto.recentlySuccessful"
              role="status"
              class="flex items-center gap-1.5 text-sm text-muted-foreground"
              ><Check class="size-4" /> Cambios guardados</span
            >
          </div>
        </form>
      </div>
    </SettingsLayout>
  </AppLayout>

  <Dialog v-model:open="showParticipant">
    <DialogContent>
      <DialogHeader>
        <DialogTitle>{{
          editing ? 'Editar participante' : 'Agregar participante'
        }}</DialogTitle>
        <DialogDescription
          >Podrá figurar como comprador o recibir una parte de la
          ganancia.</DialogDescription
        >
      </DialogHeader>
      <form class="space-y-5" @submit.prevent="saveParticipant">
        <div class="space-y-2">
          <Label for="participant-name">Nombre</Label>
          <Input
            id="participant-name"
            v-model="persona.nombre"
            maxlength="100"
            placeholder="Nombre del participante"
            :disabled="persona.processing"
            :aria-invalid="!!persona.errors.nombre"
            required
          />
          <InputError :message="persona.errors.nombre" />
        </div>
        <div
          v-if="editing && editing !== titular?.id"
          class="flex items-start gap-3"
        >
          <Checkbox
            id="participant-active"
            v-model="persona.activo"
            class="mt-0.5"
            :disabled="persona.processing"
          />
          <div class="space-y-2">
            <Label for="participant-active">Participante activo</Label>
            <p class="text-sm text-muted-foreground">
              Al desactivarlo, deja de estar disponible para nuevos repartos. Su
              historial se conserva.
            </p>
            <InputError :message="persona.errors.activo" />
          </div>
        </div>
        <DialogFooter>
          <Button
            type="button"
            variant="outline"
            :disabled="persona.processing"
            @click="showParticipant = false"
            >Cancelar</Button
          >
          <Button type="submit" :disabled="persona.processing">
            <Loader2 v-if="persona.processing" class="size-4 animate-spin" />
            {{
              persona.processing
                ? 'Guardando…'
                : editing
                  ? 'Guardar participante'
                  : 'Agregar participante'
            }}
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>
