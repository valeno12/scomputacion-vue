import { LaravelPagination } from '@/types/pagination';
import { router } from '@inertiajs/vue3';
import { useQuery, useQueryClient } from '@tanstack/vue-query';
import { debounce } from 'lodash-es';
import { computed, ref, watch } from 'vue';

export interface DataTableFilters {
  search?: string;
  page?: number;
  per_page?: number;
  sort_by?: string;
  sort_order?: 'asc' | 'desc';
}

export function useDataTable<T = Record<string, any>>(
  routeUrl: string,
  initialFilters: DataTableFilters = {},
) {
  // Estados reactivos
  const search = ref(initialFilters.search || '');
  const page = ref(initialFilters.page || 1);
  const debouncedSearch = ref(initialFilters.search || '');
  const perPage = ref(initialFilters.per_page || 10);
  const sortBy = ref(initialFilters.sort_by || '');
  const sortOrder = ref<'asc' | 'desc'>(initialFilters.sort_order || 'asc');

  // Query Key - cuando esto cambia, TanStack Query recarga automáticamente
  const queryKey = computed(() => [
    'datatable',
    routeUrl,
    debouncedSearch.value,
    page.value,
    perPage.value,
    sortBy.value,
    sortOrder.value,
  ]);

  const updateDebouncedSearch = debounce((value: string) => {
    debouncedSearch.value = value;
    page.value = 1; // Resetear a página 1
  }, 300);

  // Función que trae los datos
  const fetchData = (): Promise<LaravelPagination<T>> => {
    return new Promise((resolve, reject) => {
      router.get(
        routeUrl,
        {
          search: debouncedSearch.value,
          page: page.value,
          per_page: perPage.value,
          sort_by: sortBy.value,
          sort_order: sortOrder.value,
        },
        {
          preserveState: true,
          preserveScroll: true,
          only: ['data'],
          onSuccess: (response) => {
            resolve(response.props.data as LaravelPagination<T>);
          },
          onError: (errors) => {
            reject(errors);
          },
        },
      );
    });
  };

  // useQuery maneja TODO automáticamente
  const { data, isLoading, isFetching, error } = useQuery<LaravelPagination<T>>(
    {
      queryKey,
      queryFn: fetchData,
    },
  );

  watch(search, (newValue) => {
    updateDebouncedSearch(newValue);
  });
  // Función para cambiar ordenamiento
  const toggleSort = (column: string): void => {
    if (sortBy.value === column) {
      sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
    } else {
      sortBy.value = column;
      sortOrder.value = 'asc';
    }
  };

  // Función para cambiar página
  const goToPage = (newPage: number): void => {
    page.value = newPage;
  };

  const queryClient = useQueryClient();

  const prefetchNextPage = () => {
    const nextPage = page.value + 1;

    queryClient.prefetchQuery({
      queryKey: [
        'datatable',
        routeUrl,
        debouncedSearch.value,
        nextPage,
        perPage.value,
        sortBy.value,
        sortOrder.value,
      ],
      queryFn: () => {
        return new Promise((resolve, reject) => {
          router.get(
            routeUrl,
            {
              search: debouncedSearch.value,
              page: nextPage,
              per_page: perPage.value,
              sort_by: sortBy.value,
              sort_order: sortOrder.value,
            },
            {
              preserveState: true,
              preserveScroll: true,
              only: ['data'],
              onSuccess: (response) => {
                resolve(response.props.data as LaravelPagination<T>);
              },
              onError: (errors) => {
                reject(errors);
              },
            },
          );
        });
      },
    });
  };

  watch(page, () => {
    setTimeout(prefetchNextPage, 100);
  });
  return {
    // State
    search,
    page,
    perPage,
    sortBy,
    sortOrder,

    // TanStack Query states
    data,
    isLoading, // Primera carga
    isFetching, // Recargando (muestra los datos viejos mientras carga nuevos)
    error,

    // Methods
    toggleSort,
    goToPage,
  };
}
