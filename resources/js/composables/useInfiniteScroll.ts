import { ref, computed } from 'vue';

export interface InfiniteScrollOptions {
  pageSize?: number;
  initialPage?: number;
  loadFunction: (page: number, pageSize: number) => Promise<{
    data: any[];
    hasMore: boolean;
    total?: number;
  }>;
}

export function useInfiniteScroll(options: InfiniteScrollOptions) {
  const {
    pageSize = 20,
    initialPage = 1,
    loadFunction
  } = options;

  const items = ref<any[]>([]);
  const loading = ref(false);
  const hasMore = ref(true);
  const currentPage = ref(initialPage);
  const total = ref(0);
  const error = ref<string | null>(null);

  const isEmpty = computed(() => !loading.value && items.value.length === 0);
  const isFirstLoad = computed(() => currentPage.value === initialPage);

  const loadMore = async () => {
    if (loading.value || !hasMore.value) return;

    try {
      loading.value = true;
      error.value = null;

      const result = await loadFunction(currentPage.value, pageSize);
      
      if (isFirstLoad.value) {
        items.value = result.data;
      } else {
        items.value = [...items.value, ...result.data];
      }

      hasMore.value = result.hasMore;
      total.value = result.total || items.value.length;
      currentPage.value++;

    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Failed to load items';
      console.error('Infinite scroll error:', err);
    } finally {
      loading.value = false;
    }
  };

  const reset = () => {
    items.value = [];
    loading.value = false;
    hasMore.value = true;
    currentPage.value = initialPage;
    total.value = 0;
    error.value = null;
  };

  const refresh = async () => {
    reset();
    await loadMore();
  };

  const prependItem = (item: any) => {
    items.value.unshift(item);
  };

  const appendItem = (item: any) => {
    items.value.push(item);
  };

  const removeItem = (predicate: (item: any) => boolean) => {
    const index = items.value.findIndex(predicate);
    if (index !== -1) {
      items.value.splice(index, 1);
    }
  };

  const updateItem = (predicate: (item: any) => boolean, updates: Partial<any>) => {
    const index = items.value.findIndex(predicate);
    if (index !== -1) {
      items.value[index] = { ...items.value[index], ...updates };
    }
  };

  return {
    // State
    items,
    loading,
    hasMore,
    currentPage,
    total,
    error,
    isEmpty,
    isFirstLoad,

    // Actions
    loadMore,
    reset,
    refresh,
    prependItem,
    appendItem,
    removeItem,
    updateItem
  };
}
