import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';

export function usePaginatedResource<T>(
  routeHelper: any,
  resourceKey: string, 
  initialFilters: { search?: string } = {}
) {
  const search = ref(initialFilters.search ?? '');
  const loading = ref(false);

  const data = ref<T[]>([]);
  const meta = ref({});
  const links = ref({});

  function fetch(page = 1) {
    loading.value = true;
    router.get(
      routeHelper.index({ query: { page, search: search.value || undefined } }).url,
      {},
      {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: [resourceKey, 'filters'], // 👈 usa el recurso dinámico
        onSuccess: (page) => {
          const resource = (page.props as any)[resourceKey];
          data.value = resource.data;
          meta.value = resource.meta;
          links.value = resource.links;
          loading.value = false;
        },
      },
    );
  }

  let timer: number;
  watch(search, () => {
    clearTimeout(timer);
    timer = window.setTimeout(() => fetch(1), 400);
  });

  return { search, data, meta, links, fetch, loading };
}
