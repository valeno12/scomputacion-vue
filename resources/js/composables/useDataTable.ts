import { router } from '@inertiajs/vue3';
import { debounce } from 'lodash-es';
import { onMounted, ref, watch } from 'vue';

export interface DataTableFilters {
  search?: string;
  page?: number;
  per_page?: number;
  sort_by?: string;
  sort_order?: 'asc' | 'desc';
  estado?: string;
}

export function useDataTable(
  routeUrl: string,
  initialFilters: DataTableFilters = {},
) {
  const search = ref(initialFilters.search || '');
  const page = ref(Number(initialFilters.page) || 1);
  const debouncedSearch = ref(initialFilters.search || '');
  const perPage = ref(Number(initialFilters.per_page) || 10);
  const sortBy = ref(initialFilters.sort_by || '');
  const sortOrder = ref<'asc' | 'desc'>(initialFilters.sort_order || 'asc');
  const isLoading = ref(false);

  const updateDebouncedSearch = debounce((value: string) => {
    debouncedSearch.value = value;
    page.value = 1;
    navigate(1);
  }, 300);

  const navigate = (newPage: number) => {
    isLoading.value = true;

    const params = {
      search: debouncedSearch.value,
      page: Number(newPage),
      per_page: Number(perPage.value),
      sort_by: sortBy.value,
      sort_order: sortOrder.value,
      estado: initialFilters.estado,
    };

    const visitOptions = {
      method: 'get' as const,
      data: params,
      only: ['data', 'filters'],
      onFinish: () => {
        isLoading.value = false;
        // Si estamos saliendo de página 1, cachearla
        if (page.value === 1 && newPage !== 1) {
          router.prefetch(
            routeUrl,
            {
              method: 'get',
              data: {
                search: debouncedSearch.value,
                page: 1,
                per_page: Number(perPage.value),
                sort_by: sortBy.value,
                sort_order: sortOrder.value,
                estado: initialFilters.estado,
              },
              only: ['data', 'filters'],
            },
            { cacheFor: '1m' },
          );
        }
        page.value = newPage;

        const nextParams = {
          search: debouncedSearch.value,
          page: Number(newPage + 1),
          per_page: Number(perPage.value),
          sort_by: sortBy.value,
          sort_order: sortOrder.value,
          estado: initialFilters.estado,
        };

        const prefetchOptions = {
          method: 'get' as const,
          data: nextParams,
          only: ['data', 'filters'],
        };

        router.prefetch(routeUrl, prefetchOptions, { cacheFor: '1m' });
      },
    };

    router.visit(routeUrl, visitOptions);
  };

  watch(search, (newValue) => {
    updateDebouncedSearch(newValue);
  });

  watch(sortBy, () => {
    navigate(1);
  });

  watch(sortOrder, () => {
    navigate(1);
  });

  const goToPage = (newPage: number): void => {
    navigate(newPage);
  };

  onMounted(() => {
    const nextParams = {
      search: debouncedSearch.value,
      page: Number(page.value + 1),
      per_page: Number(perPage.value),
      sort_by: sortBy.value,
      sort_order: sortOrder.value,
      estado: initialFilters.estado,
    };

    router.prefetch(
      routeUrl,
      {
        method: 'get',
        data: nextParams,
        only: ['data', 'filters'],
      },
      {
        cacheFor: '1m',
      },
    );
  });

  return {
    search,
    page,
    perPage,
    sortBy,
    sortOrder,
    isLoading,
    goToPage,
  };
}
