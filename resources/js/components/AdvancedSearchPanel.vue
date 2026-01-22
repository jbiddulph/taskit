<template>
  <div class="advanced-search-panel">
    <!-- Search Bar -->
    <div class="search-bar">
      <div class="search-input-container">
        <Icon name="Search" class="search-icon" />
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Search todos..."
          class="search-input"
          @keyup.enter="performSearch"
        />
        <button
          v-if="hasActiveSearch"
          @click="clearSearch"
          class="clear-button"
        >
          <Icon name="X" class="w-4 h-4" />
        </button>
      </div>
      
      <div class="search-actions">
        <button
          @click="toggleAdvancedFilters"
          class="action-button"
          :class="{ active: showAdvancedFilters }"
        >
          <Icon name="Filter" class="w-4 h-4" />
          Filters
          <span v-if="activeFilters.length > 0" class="filter-count">
            {{ activeFilters.length }}
          </span>
        </button>
        
        <button
          @click="showSavedSearches = !showSavedSearches"
          class="action-button"
        >
          <Icon name="Bookmark" class="w-4 h-4" />
          Saved
        </button>
        
        <button
          @click="showHistory = !showHistory"
          class="action-button"
        >
          <Icon name="Clock" class="w-4 h-4" />
          History
        </button>
      </div>
    </div>

    <!-- Advanced Filters -->
    <div v-if="showAdvancedFilters" class="advanced-filters">
      <div class="filters-header">
        <h3 class="filters-title">Advanced Filters</h3>
        <button @click="addNewFilter" class="add-filter-button">
          <Icon name="Plus" class="w-4 h-4" />
          Add Filter
        </button>
      </div>
      
      <div v-if="activeFilters.length === 0" class="no-filters">
        <Icon name="Filter" class="w-8 h-8 text-gray-400" />
        <p class="text-gray-500">No filters applied</p>
        <p class="text-sm text-gray-400">Add filters to narrow down your search</p>
      </div>
      
      <div v-else class="filters-list">
        <div
          v-for="(filter, index) in activeFilters"
          :key="index"
          class="filter-item"
        >
          <div class="filter-field">
            <select
              v-model="filter.field"
              @change="updateFilterField(index)"
              class="filter-select"
            >
              <option value="">Select field...</option>
              <option
                v-for="field in filterFields"
                :key="field.value"
                :value="field.value"
              >
                {{ field.label }}
              </option>
            </select>
          </div>
          
          <div class="filter-operator">
            <select
              v-model="filter.operator"
              class="filter-select"
            >
              <option
                v-for="op in getOperatorsForField(filter.field)"
                :key="op.value"
                :value="op.value"
              >
                {{ op.label }}
              </option>
            </select>
          </div>
          
          <div class="filter-value">
            <input
              v-if="getFilterField(filter.field)?.type === 'text'"
              v-model="filter.value"
              type="text"
              class="filter-input"
              placeholder="Enter value..."
            />
            <input
              v-else-if="getFilterField(filter.field)?.type === 'number'"
              v-model.number="filter.value"
              type="number"
              class="filter-input"
              placeholder="Enter number..."
            />
            <input
              v-else-if="getFilterField(filter.field)?.type === 'date'"
              v-model="filter.value"
              type="date"
              class="filter-input"
            />
            <select
              v-else-if="getFilterField(filter.field)?.type === 'select'"
              v-model="filter.value"
              class="filter-select"
            >
              <option value="">Select value...</option>
              <option
                v-for="option in getFilterField(filter.field)?.options"
                :key="option"
                :value="option"
              >
                {{ option }}
              </option>
            </select>
          </div>
          
          <button
            @click="removeFilter(index)"
            class="remove-filter-button"
          >
            <Icon name="X" class="w-4 h-4" />
          </button>
        </div>
      </div>
    </div>

    <!-- Saved Searches -->
    <div v-if="showSavedSearches" class="saved-searches">
      <div class="saved-header">
        <h3 class="saved-title">Saved Searches</h3>
        <button
          v-if="hasActiveSearch"
          @click="saveCurrentSearch"
          class="save-button"
        >
          <Icon name="BookmarkPlus" class="w-4 h-4" />
          Save Current
        </button>
      </div>
      
      <div v-if="savedSearches.length === 0" class="no-saved">
        <Icon name="Bookmark" class="w-8 h-8 text-gray-400" />
        <p class="text-gray-500">No saved searches</p>
        <p class="text-sm text-gray-400">Save your current search for quick access</p>
      </div>
      
      <div v-else class="saved-list">
        <div
          v-for="search in savedSearches"
          :key="search.id"
          class="saved-item"
        >
          <div class="saved-info">
            <h4 class="saved-name">{{ search.name }}</h4>
            <p class="saved-details">
              {{ search.filters.length }} filter{{ search.filters.length !== 1 ? 's' : '' }}
              • {{ formatDate(search.createdAt) }}
            </p>
          </div>
          <div class="saved-actions">
            <button
              @click="loadSavedSearch(search)"
              class="load-button"
            >
              Load
            </button>
            <button
              @click="deleteSavedSearch(search.id)"
              class="delete-button"
            >
              <Icon name="Trash2" class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Search History -->
    <div v-if="showHistory" class="search-history">
      <div class="history-header">
        <h3 class="history-title">Recent Searches</h3>
        <button
          @click="clearHistory"
          class="clear-history-button"
        >
          Clear History
        </button>
      </div>
      
      <div v-if="searchHistory.length === 0" class="no-history">
        <Icon name="Clock" class="w-8 h-8 text-gray-400" />
        <p class="text-gray-500">No search history</p>
        <p class="text-sm text-gray-400">Your recent searches will appear here</p>
      </div>
      
      <div v-else class="history-list">
        <div
          v-for="item in searchHistory"
          :key="item.id"
          class="history-item"
          @click="loadFromHistory(item)"
        >
          <div class="history-query">
            <Icon name="Search" class="w-4 h-4 text-gray-400" />
            <span class="query-text">{{ item.query || 'No query' }}</span>
          </div>
          <div class="history-meta">
            <span class="result-count">{{ item.resultCount }} results</span>
            <span class="timestamp">{{ formatRelativeTime(item.timestamp) }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import Icon from '@/components/Icon.vue';
import { useAdvancedSearch } from '@/composables/useAdvancedSearch';

const emit = defineEmits<{
  search: [params: any];
}>();

const {
  searchQuery,
  activeFilters,
  savedSearches,
  searchHistory,
  showAdvancedFilters,
  filterFields,
  hasActiveSearch,
  addFilter,
  removeFilter,
  clearSearch,
  saveSearch,
  loadSavedSearch,
  deleteSavedSearch,
  addToHistory,
  loadFromHistory,
  getFilterField,
  getOperatorsForField
} = useAdvancedSearch();

const showSavedSearches = ref(false);
const showHistory = ref(false);

const toggleAdvancedFilters = () => {
  showAdvancedFilters.value = !showAdvancedFilters.value;
};

const addNewFilter = () => {
  const newFilter = {
    field: '',
    operator: 'contains' as const,
    value: '',
    label: ''
  };
  addFilter(newFilter);
};

const updateFilterField = (index: number) => {
  const filter = activeFilters.value[index];
  const field = getFilterField(filter.field);
  if (field) {
    filter.operator = operators[field.type as keyof typeof operators][0]?.value || 'contains';
  }
};

const performSearch = () => {
  const params = {
    search: searchQuery.value,
    filters: activeFilters.value
  };
  emit('search', params);
  addToHistory(searchQuery.value, 0); // Result count will be updated by parent
};

const saveCurrentSearch = () => {
  const name = prompt('Enter a name for this search:');
  if (name) {
    saveSearch(name);
  }
};

const clearHistory = () => {
  searchHistory.value = [];
};

const formatDate = (date: Date) => {
  return new Intl.DateTimeFormat('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric'
  }).format(date);
};

const formatRelativeTime = (date: Date) => {
  const now = new Date();
  const diff = now.getTime() - date.getTime();
  const minutes = Math.floor(diff / 60000);
  const hours = Math.floor(diff / 3600000);
  const days = Math.floor(diff / 86400000);
  
  if (minutes < 1) return 'Just now';
  if (minutes < 60) return `${minutes}m ago`;
  if (hours < 24) return `${hours}h ago`;
  return `${days}d ago`;
};
</script>

<style scoped>
.advanced-search-panel {
  background: white;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.search-bar {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  border-bottom: 1px solid #e5e7eb;
}

.search-input-container {
  position: relative;
  flex: 1;
  max-width: 400px;
}

.search-icon {
  position: absolute;
  left: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  width: 1rem;
  height: 1rem;
  color: #9ca3af;
}

.search-input {
  width: 100%;
  padding: 0.75rem 0.75rem 0.75rem 2.5rem;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 0.875rem;
}

.search-input:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.clear-button {
  position: absolute;
  right: 0.5rem;
  top: 50%;
  transform: translateY(-50%);
  padding: 0.25rem;
  border-radius: 4px;
  background: #f3f4f6;
  color: #6b7280;
}

.search-actions {
  display: flex;
  gap: 0.5rem;
}

.action-button {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  background: white;
  color: #374151;
  font-size: 0.875rem;
  font-weight: 500;
  transition: all 0.2s;
  cursor: pointer;
}

.action-button:hover {
  background: #f9fafb;
  border-color: #9ca3af;
}

.action-button.active {
  background: #3b82f6;
  color: white;
  border-color: #3b82f6;
}

.filter-count {
  background: #ef4444;
  color: white;
  border-radius: 50%;
  width: 1.25rem;
  height: 1.25rem;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
  font-weight: 600;
}

.advanced-filters {
  padding: 1rem;
  border-bottom: 1px solid #e5e7eb;
}

.filters-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.filters-title {
  font-size: 1rem;
  font-weight: 600;
  color: #374151;
}

.add-filter-button {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  background: #3b82f6;
  color: white;
  border: none;
  border-radius: 6px;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
}

.no-filters {
  text-align: center;
  padding: 2rem;
  color: #6b7280;
}

.filters-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.filter-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem;
  background: #f9fafb;
  border-radius: 6px;
  border: 1px solid #e5e7eb;
}

.filter-field,
.filter-operator {
  min-width: 120px;
}

.filter-value {
  flex: 1;
  min-width: 150px;
}

.filter-select,
.filter-input {
  width: 100%;
  padding: 0.5rem;
  border: 1px solid #d1d5db;
  border-radius: 4px;
  font-size: 0.875rem;
}

.remove-filter-button {
  padding: 0.5rem;
  background: #ef4444;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

.saved-searches,
.search-history {
  padding: 1rem;
  border-bottom: 1px solid #e5e7eb;
}

.saved-header,
.history-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.saved-title,
.history-title {
  font-size: 1rem;
  font-weight: 600;
  color: #374151;
}

.save-button,
.clear-history-button {
  padding: 0.5rem 1rem;
  background: #10b981;
  color: white;
  border: none;
  border-radius: 6px;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
}

.no-saved,
.no-history {
  text-align: center;
  padding: 2rem;
  color: #6b7280;
}

.saved-list,
.history-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.saved-item,
.history-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem;
  background: #f9fafb;
  border-radius: 6px;
  border: 1px solid #e5e7eb;
  cursor: pointer;
  transition: all 0.2s;
}

.saved-item:hover,
.history-item:hover {
  background: #f3f4f6;
  border-color: #d1d5db;
}

.saved-info {
  flex: 1;
}

.saved-name {
  font-size: 0.875rem;
  font-weight: 500;
  color: #374151;
  margin-bottom: 0.25rem;
}

.saved-details {
  font-size: 0.75rem;
  color: #6b7280;
}

.saved-actions {
  display: flex;
  gap: 0.5rem;
}

.load-button {
  padding: 0.375rem 0.75rem;
  background: #3b82f6;
  color: white;
  border: none;
  border-radius: 4px;
  font-size: 0.75rem;
  cursor: pointer;
}

.delete-button {
  padding: 0.375rem;
  background: #ef4444;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

.history-query {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex: 1;
}

.query-text {
  font-size: 0.875rem;
  color: #374151;
}

.history-meta {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 0.25rem;
}

.result-count {
  font-size: 0.75rem;
  color: #6b7280;
}

.timestamp {
  font-size: 0.75rem;
  color: #9ca3af;
}
</style>
