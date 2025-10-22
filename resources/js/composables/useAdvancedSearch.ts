import { ref, computed, watch } from 'vue';

export interface SearchFilter {
  field: string;
  operator: 'equals' | 'contains' | 'starts_with' | 'ends_with' | 'greater_than' | 'less_than' | 'between' | 'in' | 'not_in';
  value: any;
  label: string;
}

export interface SavedSearch {
  id: string;
  name: string;
  filters: SearchFilter[];
  createdAt: Date;
  lastUsed?: Date;
  isDefault?: boolean;
}

export interface SearchHistory {
  id: string;
  query: string;
  filters: SearchFilter[];
  timestamp: Date;
  resultCount: number;
}

export function useAdvancedSearch() {
  const searchQuery = ref('');
  const activeFilters = ref<SearchFilter[]>([]);
  const savedSearches = ref<SavedSearch[]>([]);
  const searchHistory = ref<SearchHistory[]>([]);
  const showAdvancedFilters = ref(false);

  // Available filter fields
  const filterFields = [
    { value: 'title', label: 'Title', type: 'text' },
    { value: 'description', label: 'Description', type: 'text' },
    { value: 'status', label: 'Status', type: 'select', options: ['todo', 'in-progress', 'qa-testing', 'done'] },
    { value: 'priority', label: 'Priority', type: 'select', options: ['Low', 'Medium', 'High', 'Critical'] },
    { value: 'type', label: 'Type', type: 'select', options: ['Bug', 'Feature', 'Task', 'Story', 'Epic'] },
    { value: 'assignee', label: 'Assignee', type: 'text' },
    { value: 'due_date', label: 'Due Date', type: 'date' },
    { value: 'created_at', label: 'Created Date', type: 'date' },
    { value: 'updated_at', label: 'Updated Date', type: 'date' },
    { value: 'tags', label: 'Tags', type: 'text' },
    { value: 'story_points', label: 'Story Points', type: 'number' }
  ];

  const operators = {
    text: [
      { value: 'contains', label: 'Contains' },
      { value: 'equals', label: 'Equals' },
      { value: 'starts_with', label: 'Starts with' },
      { value: 'ends_with', label: 'Ends with' }
    ],
    select: [
      { value: 'equals', label: 'Equals' },
      { value: 'not_equals', label: 'Not equals' },
      { value: 'in', label: 'In' },
      { value: 'not_in', label: 'Not in' }
    ],
    date: [
      { value: 'equals', label: 'Equals' },
      { value: 'greater_than', label: 'After' },
      { value: 'less_than', label: 'Before' },
      { value: 'between', label: 'Between' }
    ],
    number: [
      { value: 'equals', label: 'Equals' },
      { value: 'greater_than', label: 'Greater than' },
      { value: 'less_than', label: 'Less than' },
      { value: 'between', label: 'Between' }
    ]
  };

  const hasActiveFilters = computed(() => activeFilters.value.length > 0);
  const hasSearchQuery = computed(() => searchQuery.value.trim().length > 0);
  const hasActiveSearch = computed(() => hasSearchQuery.value || hasActiveFilters.value);

  const addFilter = (filter: SearchFilter) => {
    activeFilters.value.push(filter);
  };

  const removeFilter = (index: number) => {
    activeFilters.value.splice(index, 1);
  };

  const updateFilter = (index: number, filter: SearchFilter) => {
    activeFilters.value[index] = filter;
  };

  const clearAllFilters = () => {
    activeFilters.value = [];
  };

  const clearSearch = () => {
    searchQuery.value = '';
    clearAllFilters();
  };

  const saveSearch = (name: string): SavedSearch => {
    const savedSearch: SavedSearch = {
      id: Date.now().toString(),
      name,
      filters: [...activeFilters.value],
      createdAt: new Date(),
      isDefault: false
    };
    
    savedSearches.value.push(savedSearch);
    return savedSearch;
  };

  const loadSavedSearch = (savedSearch: SavedSearch) => {
    activeFilters.value = [...savedSearch.filters];
    savedSearch.lastUsed = new Date();
  };

  const deleteSavedSearch = (id: string) => {
    const index = savedSearches.value.findIndex(s => s.id === id);
    if (index !== -1) {
      savedSearches.value.splice(index, 1);
    }
  };

  const addToHistory = (query: string, resultCount: number) => {
    const historyItem: SearchHistory = {
      id: Date.now().toString(),
      query,
      filters: [...activeFilters.value],
      timestamp: new Date(),
      resultCount
    };
    
    // Keep only last 20 searches
    searchHistory.value.unshift(historyItem);
    if (searchHistory.value.length > 20) {
      searchHistory.value = searchHistory.value.slice(0, 20);
    }
  };

  const loadFromHistory = (historyItem: SearchHistory) => {
    searchQuery.value = historyItem.query;
    activeFilters.value = [...historyItem.filters];
  };

  const getFilterField = (fieldValue: string) => {
    return filterFields.find(f => f.value === fieldValue);
  };

  const getOperatorsForField = (fieldValue: string) => {
    const field = getFilterField(fieldValue);
    return field ? operators[field.type as keyof typeof operators] : [];
  };

  const buildSearchParams = () => {
    const params: Record<string, any> = {};
    
    if (searchQuery.value.trim()) {
      params.search = searchQuery.value.trim();
    }
    
    if (activeFilters.value.length > 0) {
      params.filters = activeFilters.value.map(filter => ({
        field: filter.field,
        operator: filter.operator,
        value: filter.value
      }));
    }
    
    return params;
  };

  const exportSearch = () => {
    const searchData = {
      query: searchQuery.value,
      filters: activeFilters.value,
      timestamp: new Date().toISOString()
    };
    
    const blob = new Blob([JSON.stringify(searchData, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `zaptask-search-${Date.now()}.json`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
  };

  const importSearch = (file: File): Promise<void> => {
    return new Promise((resolve, reject) => {
      const reader = new FileReader();
      reader.onload = (e) => {
        try {
          const data = JSON.parse(e.target?.result as string);
          searchQuery.value = data.query || '';
          activeFilters.value = data.filters || [];
          resolve();
        } catch (error) {
          reject(new Error('Invalid search file'));
        }
      };
      reader.onerror = () => reject(new Error('Failed to read file'));
      reader.readAsText(file);
    });
  };

  return {
    // State
    searchQuery,
    activeFilters,
    savedSearches,
    searchHistory,
    showAdvancedFilters,
    filterFields,
    operators,
    
    // Computed
    hasActiveFilters,
    hasSearchQuery,
    hasActiveSearch,
    
    // Actions
    addFilter,
    removeFilter,
    updateFilter,
    clearAllFilters,
    clearSearch,
    saveSearch,
    loadSavedSearch,
    deleteSavedSearch,
    addToHistory,
    loadFromHistory,
    getFilterField,
    getOperatorsForField,
    buildSearchParams,
    exportSearch,
    importSearch
  };
}
