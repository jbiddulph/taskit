<template>
  <div class="todo-template-selector">
    <!-- Template Selection Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="closeModal">
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-hidden">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
          <div class="flex justify-between items-center">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Choose a Template</h2>
            <button @click="closeModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
              <Icon name="X" class="w-6 h-6" />
            </button>
          </div>
        </div>

        <div class="p-6 overflow-y-auto max-h-[60vh]">
          <!-- Search and Filters -->
          <div class="mb-6 space-y-4">
            <div class="flex gap-4">
              <div class="flex-1">
                <input
                  v-model="searchQuery"
                  type="text"
                  placeholder="Search templates..."
                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                />
              </div>
              <select
                v-model="selectedCategory"
                class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
              >
                <option value="">All Categories</option>
                <option v-for="category in categories" :key="category" :value="category">
                  {{ category }}
                </option>
              </select>
            </div>
          </div>

          <!-- Templates Grid -->
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div
              v-for="template in filteredTemplates"
              :key="template.id"
              @click="selectTemplate(template)"
              class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:border-blue-300 dark:hover:border-blue-600 cursor-pointer transition-colors bg-gray-50 dark:bg-gray-900/50 hover:bg-blue-50 dark:hover:bg-blue-900/20"
            >
              <div class="flex justify-between items-start mb-2">
                <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ template.name }}</h3>
                <span class="text-xs text-gray-500 dark:text-gray-400">{{ template.usage_count }} uses</span>
              </div>
              
              <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">{{ template.description }}</p>
              
              <div class="flex items-center justify-between">
                <span class="text-xs px-2 py-1 bg-blue-100 dark:bg-blue-900/20 text-blue-800 dark:text-blue-200 rounded-full">
                  {{ template.category }}
                </span>
                <span class="text-xs text-gray-500 dark:text-gray-400">
                  {{ template.todos.length }} tasks
                </span>
              </div>
            </div>
          </div>

          <!-- No templates message -->
          <div v-if="filteredTemplates.length === 0" class="text-center py-8">
            <Icon name="FileText" class="w-12 h-12 text-gray-400 mx-auto mb-4" />
            <p class="text-gray-500 dark:text-gray-400">No templates found</p>
          </div>
        </div>

        <div class="p-6 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3">
          <button
            @click="closeModal"
            class="px-4 py-2 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
          >
            Cancel
          </button>
          <button
            @click="createNewTemplate"
            class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors"
          >
            Create New Template
          </button>
        </div>
      </div>
    </div>

    <!-- Template Preview Modal -->
    <div v-if="showPreview" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="closePreview">
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-hidden">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
          <div class="flex justify-between items-center">
            <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ selectedTemplate?.name }}</h2>
            <button @click="closePreview" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
              <Icon name="X" class="w-6 h-6" />
            </button>
          </div>
        </div>

        <div class="p-6 overflow-y-auto max-h-[60vh]">
          <p class="text-gray-600 dark:text-gray-400 mb-4">{{ selectedTemplate?.description }}</p>
          
          <div class="space-y-3">
            <div
              v-for="(todo, index) in selectedTemplate?.todos"
              :key="index"
              class="p-3 border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-900/50"
            >
              <div class="flex items-center justify-between mb-2">
                <h4 class="font-medium text-gray-900 dark:text-gray-100">{{ todo.title }}</h4>
                <div class="flex items-center gap-2">
                  <span class="text-xs px-2 py-1 bg-blue-100 dark:bg-blue-900/20 text-blue-800 dark:text-blue-200 rounded">
                    {{ todo.priority }}
                  </span>
                  <span class="text-xs px-2 py-1 bg-green-100 dark:bg-green-900/20 text-green-800 dark:text-green-200 rounded">
                    {{ todo.type }}
                  </span>
                </div>
              </div>
              
              <p v-if="todo.description" class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                {{ todo.description }}
              </p>
              
              <div v-if="todo.tags.length > 0" class="flex flex-wrap gap-1">
                <span
                  v-for="tag in todo.tags"
                  :key="tag"
                  class="text-xs px-2 py-1 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded"
                >
                  {{ tag }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <div class="p-6 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3">
          <button
            @click="closePreview"
            class="px-4 py-2 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
          >
            Back
          </button>
          <button
            @click="applyTemplate"
            class="inline-flex items-center justify-center gap-2 rounded-md px-4 py-2 text-sm font-medium border transition-colors cursor-pointer bg-black text-white hover:bg-gray-900 hover:border-gray-900 dark:bg-white dark:text-black dark:hover:bg-gray-100 dark:hover:border-gray-300"
          >
            Apply Template
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import Icon from '@/components/Icon.vue';
import { todoTemplateService, type TodoTemplate } from '@/services/todoTemplateService';

interface Props {
  companyId: number;
  projectId: number;
}

const props = defineProps<Props>();

const emit = defineEmits<{
  'template-selected': [todos: any[]];
  'create-template': [];
}>();

const showModal = ref(false);
const showPreview = ref(false);
const searchQuery = ref('');
const selectedCategory = ref('');
const selectedTemplate = ref<TodoTemplate | null>(null);

const templates = computed(() => todoTemplateService.getTemplates(props.companyId));
const categories = computed(() => todoTemplateService.getCategories(props.companyId));

const filteredTemplates = computed(() => {
  let filtered = templates.value;

  if (searchQuery.value) {
    filtered = todoTemplateService.searchTemplates(props.companyId, searchQuery.value);
  }

  if (selectedCategory.value) {
    filtered = filtered.filter(t => t.category === selectedCategory.value);
  }

  return filtered;
});

const openModal = () => {
  showModal.value = true;
  searchQuery.value = '';
  selectedCategory.value = '';
};

const closeModal = () => {
  showModal.value = false;
  selectedTemplate.value = null;
};

const closePreview = () => {
  showPreview.value = false;
  selectedTemplate.value = null;
};

const selectTemplate = (template: TodoTemplate) => {
  selectedTemplate.value = template;
  showModal.value = false;
  showPreview.value = true;
};

const applyTemplate = () => {
  if (!selectedTemplate.value) return;

  const todos = todoTemplateService.convertTemplateToTodos(
    selectedTemplate.value,
    props.projectId
  );

  emit('template-selected', todos);
  closePreview();
};

const createNewTemplate = () => {
  emit('create-template');
  closeModal();
};

// Expose methods for parent component
defineExpose({
  openModal
});
</script>
