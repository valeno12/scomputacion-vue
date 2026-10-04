<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from '@/components/ui/popover';
import axios from 'axios';
import { Check, ChevronsUpDown, Loader2, Search } from 'lucide-vue-next';
import { PopoverAnchor } from 'reka-ui';
import { nextTick, onBeforeUnmount, ref, watch } from 'vue';

export interface SearchOption {
  id: number | string;
  label: string;
  description?: string;
  raw: any;
}
const props = withDefaults(
  defineProps<{
    endpoint: string;
    label: string;
    placeholder?: string;
    selectedId?: number | string | null;
    selectedLabel?: string;
    disabled?: boolean;
    disabledIds?: (number | string)[];
    params?: Record<string, unknown>;
    mapItem: (item: any) => SearchOption;
    inputMode?: boolean;
    allowFreeText?: boolean;
    textValue?: string;
    inputId?: string;
    required?: boolean;
  }>(),
  { placeholder: 'Escribí para buscar…', disabledIds: () => [] },
);
const emit = defineEmits<{
  select: [item: any];
  'update:textValue': [value: string];
  query: [value: string];
}>();
const open = ref(false);
const searchInput = ref<InstanceType<typeof Input>>();
let restoringFocus = false;
const search = ref('');
const results = ref<SearchOption[]>([]);
const loading = ref(false);
const error = ref('');
const more = ref(false);
const root = ref<HTMLElement>();
let timeout: ReturnType<typeof setTimeout>;
let controller: AbortController | undefined;
let generation = 0;
function reset() {
  ++generation;
  controller?.abort();
  clearTimeout(timeout);
  results.value = [];
  more.value = false;
  loading.value = false;
  error.value = '';
}
async function fetchResults() {
  if (!open.value || !search.value.trim()) return;
  const current = ++generation;
  controller?.abort();
  controller = new AbortController();
  loading.value = true;
  try {
    const { data } = await axios.get(props.endpoint, {
      params: { ...props.params, q: search.value.trim() },
      signal: controller.signal,
    });
    if (current !== generation) return;
    results.value = (Array.isArray(data) ? data : data.data).map(props.mapItem);
    more.value = !!data.has_more;
  } catch (err) {
    if (current === generation && !axios.isCancel(err))
      error.value = 'No se pudo buscar. Volvé a intentarlo.';
  } finally {
    if (current === generation) loading.value = false;
  }
}
watch(search, (value) => {
  reset();
  emit('query', value);
  if (open.value && value.trim()) {
    loading.value = true;
    timeout = setTimeout(fetchResults, 250);
  }
});
watch(open, (value) => {
  if (!value) reset();
  else {
    search.value = props.inputMode ? (props.textValue ?? '') : '';
    if (search.value.trim()) fetchResults();
  }
});
watch(
  () => props.textValue,
  (value) => {
    if (props.inputMode && !value?.trim()) {
      search.value = '';
      open.value = false;
    }
  },
);
function select(option: SearchOption) {
  if (props.disabledIds.includes(option.id)) return;
  emit('select', option.raw);
  if (props.inputMode) emit('update:textValue', option.label);
  open.value = false;
  if (props.inputMode)
    nextTick(() => {
      restoringFocus = true;
      searchInput.value?.$el?.focus();
      restoringFocus = false;
    });
}
function input(value: string | number) {
  open.value = !!String(value).trim();
  search.value = String(value);
  emit('update:textValue', String(value));
}
function move(event: KeyboardEvent, direction: number) {
  const buttons = [
    ...(root.value?.querySelectorAll<HTMLButtonElement>(
      '[data-result]:not(:disabled)',
    ) ?? []),
  ];
  const index = buttons.indexOf(event.target as HTMLButtonElement);
  buttons[(index + direction + buttons.length) % buttons.length]?.focus();
}
function down(event: KeyboardEvent) {
  nextTick(() => move(event, 1));
}
onBeforeUnmount(reset);
</script>
<template>
  <Popover v-model:open="open">
    <PopoverAnchor v-if="inputMode" as-child>
      <Input
        ref="searchInput"
        :id="inputId"
        :model-value="textValue || ''"
        :placeholder="placeholder"
        :disabled="disabled"
        :required="required"
        :aria-label="label"
        role="combobox"
        :aria-expanded="open"
        autocomplete="off"
        @update:model-value="input"
        @focus="!restoringFocus && (open = !!textValue?.trim())"
        @keydown.down.prevent="down"
        @keydown.enter.prevent="open = false"
      />
    </PopoverAnchor>
    <PopoverTrigger v-else as-child>
      <Button
        type="button"
        variant="outline"
        role="combobox"
        :aria-label="label"
        :aria-expanded="open"
        :disabled="disabled"
        class="w-full min-w-0 justify-between font-normal"
      >
        <span class="truncate">{{ selectedLabel || label }}</span
        ><ChevronsUpDown class="ml-2 size-4 shrink-0 opacity-50" />
      </Button>
    </PopoverTrigger>
    <PopoverContent
      align="start"
      class="w-[500px] max-w-[calc(100vw-2rem)] p-0"
      @close-auto-focus="inputMode && $event.preventDefault()"
      @open-auto-focus="
        (event) => {
          if (inputMode) event.preventDefault();
        }
      "
    >
      <div ref="root">
        <div v-if="!inputMode" class="flex items-center border-b px-3">
          <Search class="mr-2 size-4 shrink-0 opacity-50" /><input
            v-model="search"
            :aria-label="`Buscar en ${label}`"
            :placeholder="placeholder"
            maxlength="200"
            class="h-10 w-full bg-transparent py-3 text-sm outline-none placeholder:text-muted-foreground"
            @keydown.down.prevent="down"
            @keydown.enter.prevent
          />
        </div>
        <div
          v-if="!search.trim()"
          class="p-5 text-center text-sm text-muted-foreground"
        >
          Escribí para comenzar la búsqueda.
        </div>
        <div
          v-else-if="loading"
          role="status"
          class="flex items-center justify-center gap-2 p-5 text-sm text-muted-foreground"
        >
          <Loader2 class="size-4 animate-spin" /> Buscando…
        </div>
        <div v-else-if="error" role="alert" class="space-y-2 p-4 text-sm">
          <p>{{ error }}</p>
          <Button
            type="button"
            variant="outline"
            size="sm"
            @click="fetchResults"
            >Reintentar</Button
          >
        </div>
        <div
          v-else-if="!results.length"
          class="p-5 text-center text-sm text-muted-foreground"
        >
          {{
            allowFreeText
              ? 'Sin coincidencias. Se usará el nombre escrito.'
              : 'No se encontraron resultados.'
          }}
        </div>
        <div
          v-else
          role="listbox"
          :aria-label="`Resultados de ${label}`"
          class="max-h-[300px] overflow-y-auto p-1"
        >
          <button
            v-for="option in results"
            :key="option.id"
            type="button"
            role="option"
            :aria-selected="
              selectedId === option.id || disabledIds.includes(option.id)
            "
            :disabled="disabledIds.includes(option.id)"
            data-result
            class="relative flex w-full items-center gap-2 rounded-sm px-2 py-2 text-left text-sm outline-none hover:bg-accent hover:text-accent-foreground focus:bg-accent disabled:opacity-60"
            @click="select(option)"
            @keydown.down.prevent="move($event, 1)"
            @keydown.up.prevent="move($event, -1)"
          >
            <Check
              class="size-4 shrink-0"
              :class="
                selectedId === option.id || disabledIds.includes(option.id)
                  ? 'opacity-100'
                  : 'opacity-0'
              "
            />
            <span class="min-w-0 flex-1"
              ><span class="block font-medium">{{ option.label }}</span
              ><span
                v-if="option.description"
                class="block text-xs text-muted-foreground"
                >{{ option.description }}</span
              ></span
            >
            <span v-if="disabledIds.includes(option.id)" class="text-xs"
              >Agregado</span
            >
          </button>
        </div>
        <p
          v-if="more && !loading"
          class="border-t p-3 text-xs text-muted-foreground"
        >
          Hay más resultados. Completá el nombre o la marca para acotar la
          búsqueda.
        </p>
      </div>
    </PopoverContent>
  </Popover>
</template>
