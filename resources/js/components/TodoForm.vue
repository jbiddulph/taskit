<template>
  <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click="$emit('close')">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto" @click.stop>
      <div class="p-6">
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
            {{ modalTitle || (isEditing ? t('todos.edit_todo') : t('todos.create_todo')) }}
          </h2>
          <div class="flex items-center gap-2">
            <!-- Templates button - Hidden for now -->
            <!-- <button
              v-if="!isEditing"
              @click="showTemplateSelector = true"
              type="button"
              class="px-3 py-2 text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
            >
              <Icon name="FileText" class="w-4 h-4 inline mr-2" />
              Templates
            </button> -->
            <button
              @click="$emit('close')"
              class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
            >
              <Icon name="X" class="w-6 h-6" />
            </button>
          </div>
        </div>

        <!-- Tabs (only for Create mode) -->
        <div v-if="!isEditing" class="mb-4 border-b border-gray-200 dark:border-gray-700">
          <div class="flex gap-4">
            <button
              type="button"
              @click="activeTab = 'basic'"
              :class="[
                'px-4 py-2 text-sm font-medium border-b-2 transition-colors',
                activeTab === 'basic'
                  ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                  : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'
              ]"
            >
              Basic
            </button>
            <button
              type="button"
              @click="activeTab = 'advanced'"
              :class="[
                'px-4 py-2 text-sm font-medium border-b-2 transition-colors',
                activeTab === 'advanced'
                  ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                  : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'
              ]"
            >
              Advanced
            </button>
          </div>
        </div>

        <form @submit.prevent="handleSubmit" class="space-y-4">
          <!-- Basic Tab Content (for Create) or All Fields (for Edit) -->
          <template v-if="!isEditing ? activeTab === 'basic' : true">
            <!-- Title -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                {{ t('todos.todo_title') }} *
              </label>
              <input
                v-model="form.title"
                type="text"
                required
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                :placeholder="t('todos.todo_title')"
              />
            </div>

            <!-- Description -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                {{ t('todos.todo_description') }}
              </label>
              <TipTapEditor v-model="form.description" />
            </div>
          </template>

          <!-- Advanced Tab Content -->
          <template v-if="!isEditing ? activeTab === 'advanced' : true">

          <!-- Project (Read-only) - Hidden for now -->
          <!-- <div v-if="currentProject">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Project
            </label>
            <div class="px-3 py-2 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md text-gray-900 dark:text-gray-100">
              {{ currentProject.key }} - {{ currentProject.name }}
            </div>
          </div> -->

          <!-- Priority and Type -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                {{t('todos.priority')}}
              </label>
              <select
                v-model="form.priority"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
              >
                <option value="Low">{{t('todos.priority_low')}}</option>
                <option value="Medium">{{t('todos.priority_medium')}}</option>
                <option value="High">{{t('todos.priority_high')}}</option>
                <option value="Critical">{{t('todos.priority_critical')}}</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                {{t('todos.type')}}
              </label>
              <TypeSelector v-model="form.type" />
            </div>
          </div>

          <!-- Assignee, Due Date, Tags (single row) -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                {{t('todos.assignee')}}
              </label>
              <select
                v-model="form.assignee"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
              >
                <option v-if="currentUserName" :value="currentUserName">{{ currentUserName }} (You)</option>
                <option v-for="u in users" :key="u.id" :value="u.name">{{ u.name }}</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                {{t('todos.due_date')}}
              </label>
              <input
                v-model="form.due_date"
                type="date"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                {{t('todos.tags')}}
              </label>
              <input
                v-model="tagsInput"
                type="text"
                @keydown.enter.prevent="addTag"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                :placeholder="t('todos.add_tag')"
              />
            </div>
          </div>

          <!-- Tags display -->
          <div v-if="form.tags && form.tags.length > 0" class="flex flex-wrap gap-2">
            <span
              v-for="tag in form.tags"
              :key="tag"
              class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200"
            >
              {{ tag }}
              <button
                type="button"
                @click="removeTag(tag)"
                class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-200"
              >
                <Icon name="X" class="w-3 h-3" />
              </button>
            </span>
          </div>

          <!-- Status removed: controlled by board drag/drop -->
          </template>

          <!-- Action buttons -->
          <div class="flex justify-end gap-3 pt-4">
            <button
              type="button"
              @click="$emit('close')"
              class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
            >
              {{ t('common.cancel') }}
            </button>
            <button
              type="submit"
              class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
            >
              {{ isEditing ? t('common.update') : t('common.create') }}
            </button>
          </div>
        </form>

        <!-- Comments (only when editing) -->
        <TodoComments v-if="isEditing && form.id" :todo-id="Number(form.id)" />
      </div>
    </div>

    <!-- Todo Template Selector -->
    <TodoTemplateSelector
      v-if="showTemplateSelector"
      :company-id="currentUser?.company_id || 1"
      :project-id="currentProject?.id || 1"
      @template-selected="handleTemplateSelected"
      @create-template="handleCreateTemplate"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted } from 'vue';
import TipTapEditor from '@/components/TipTapEditor.vue';
import TypeSelector from '@/components/TypeSelector.vue';
import TodoTemplateSelector from '@/components/TodoTemplateSelector.vue';
import { usePage } from '@inertiajs/vue3';
import Icon from '@/components/Icon.vue';
import TodoComments from '@/components/TodoComments.vue';
import { type Project } from '@/services/todoApi';
import type { Todo } from '@/services/todoApi';
// import { uploadImageToTaskitBucket } from '@/services/supabaseClient';
import { useAnalytics } from '../composables/useAnalytics';
import { useI18n } from 'vue-i18n';
const { t } = useI18n();

interface Props {
  todo?: Todo;
  isEditing?: boolean;
  currentProject?: Project | null;
  modalTitle?: string;
}

const props = withDefaults(defineProps<Props>(), {
  isEditing: false,
});

const emit = defineEmits<{
  close: [];
  save: [todo: Todo];
}>();

const page = usePage();
const currentUser = (page.props as any)?.auth?.user || null;
const currentUserName = currentUser?.name || '';
const { trackTodoEvent } = useAnalytics();

// Template selector state
const showTemplateSelector = ref(false);

// Active tab state (for Basic/Advanced tabs in Create mode)
const activeTab = ref<'basic' | 'advanced'>('basic');


type SimpleUser = { id: number; name: string; email: string };
const users = ref<SimpleUser[]>([]);

const loadUsers = async () => {
  try {
    const response = await fetch('/api/users', {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      credentials: 'same-origin',
    });
    
    if (response.ok) {
      const data = await response.json();
      users.value = data;
    } else {
      // Fallback to current user if API fails
      if (currentUser) {
        users.value = [{ id: currentUser.id, name: currentUser.name, email: currentUser.email }];
      } else {
        users.value = [];
      }
    }
  } catch (error) {
    console.error('Failed to load users:', error);
    // Fallback to current user
    if (currentUser) {
      users.value = [{ id: currentUser.id, name: currentUser.name, email: currentUser.email }];
    } else {
      users.value = [];
    }
  }
};

const form = ref({
  id: '',
  title: '',
  description: '',
  priority: 'Medium' as const,
  type: '',
  tags: [] as string[],
  assignee: '',
  due_date: '',
  story_points: undefined as number | undefined,
  status: 'todo' as const,
});

const tagsInput = ref('');



const resetForm = () => {
  form.value = {
    id: '',
    title: '',
    description: '',
    priority: 'Medium',
    type: '',
    tags: [],
    assignee: '',
    due_date: '',
    story_points: undefined,
    status: 'todo',
  };
  tagsInput.value = '';
};

// Initialize form when editing
onMounted(() => {
  loadUsers();
});

watch(() => props.todo, (newTodo) => {
  if (newTodo) {
    form.value = { ...newTodo };
    
    // Fix date format for HTML date input (YYYY-MM-DD)
    if (newTodo.due_date) {
      // Convert from datetime to date format
      const date = new Date(newTodo.due_date);
      if (!isNaN(date.getTime())) {
        form.value.due_date = date.toISOString().split('T')[0];
      }
    }
  } else {
    resetForm();
    // Auto-assign current user on create
    if (currentUserName) {
      form.value.assignee = currentUserName;
    }
  }
}, { immediate: true });

const addTag = () => {
  const tag = tagsInput.value.trim();
  if (tag && !form.value.tags?.includes(tag)) {
    if (!form.value.tags) form.value.tags = [];
    form.value.tags.push(tag);
    tagsInput.value = '';
  }
};

const removeTag = (tagToRemove: string) => {
  if (form.value.tags) {
    form.value.tags = form.value.tags.filter(tag => tag !== tagToRemove);
  }
};

const handleSubmit = async () => {
  try {
    if (!props.currentProject) {
      console.error('No current project selected');
      return;
    }
    
    const todoData: any = {
      ...form.value,
      project_id: props.currentProject.id,
      // Normalize optional fields to null instead of empty strings for backend validation
      type: form.value.type ? form.value.type : null,
      assignee: form.value.assignee ? form.value.assignee : null,
      due_date: form.value.due_date ? form.value.due_date : null,
      story_points: form.value.story_points ? form.value.story_points : null,
    };
    
    if (props.isEditing) {
      todoData.id = parseInt(form.value.id);
      // Track todo update event
      trackTodoEvent('updated', {
        todo_id: todoData.id,
        project_id: props.currentProject.id,
        project_name: props.currentProject.name,
        priority: todoData.priority,
        type: todoData.type,
        has_due_date: !!todoData.due_date,
        has_assignee: !!todoData.assignee,
      });
    } else {
      // Track todo creation event
      trackTodoEvent('created', {
        project_id: props.currentProject.id,
        project_name: props.currentProject.name,
        priority: todoData.priority,
        type: todoData.type,
        has_due_date: !!todoData.due_date,
        has_assignee: !!todoData.assignee,
      });
    }
    
    emit('save', todoData);
    resetForm();
  } catch (error) {
    console.error('Failed to submit todo:', error);
  }
};

// Template handlers
const handleTemplateSelected = (todos: any[]) => {
  // Close the template selector
  showTemplateSelector.value = false;
  
  // For now, just create the first todo from the template
  // In a real app, you might want to create multiple todos
  if (todos.length > 0) {
    const templateTodo = todos[0];
    form.title = templateTodo.title;
    form.description = templateTodo.description;
    form.priority = templateTodo.priority;
    form.type = templateTodo.type;
    form.assignee = templateTodo.assignee;
    form.tags = templateTodo.tags;
  }
};

const handleCreateTemplate = () => {
  // Close the template selector
  showTemplateSelector.value = false;
  // In a real app, this would open a template creation modal
  console.log('Create new template');
};

// TipTap editor used; no extra config here

</script>
