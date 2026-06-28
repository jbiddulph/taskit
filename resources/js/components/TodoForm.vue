<template>
    <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click="$emit('close')">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto overflow-x-hidden" @click.stop>
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
              <TypeSelector v-model="todoType" />
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

          <LocationPicker v-model="location" />

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                {{ t('todos.card_icon') }}
              </label>
              <IconSelector
                v-model="form.card_icon"
                :clear-label="t('todos.use_type_icon_default')"
              />
              <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                {{ t('todos.card_icon_hint') }}
              </p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                {{ t('todos.outline_color') }}
              </label>
              <div class="flex items-center gap-3">
                <input
                  v-model="outlineColorPicker"
                  type="color"
                  class="w-12 h-10 border border-gray-300 dark:border-gray-600 rounded-md cursor-pointer"
                />
                <span class="text-sm text-gray-600 dark:text-gray-400">{{ outlineColorPicker }}</span>
              </div>
              <button
                type="button"
                class="mt-2 text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                @click="resetOutlineColor"
              >
                {{ t('todos.use_project_color') }}
              </button>
              <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                {{ t('todos.outline_color_hint') }}
              </p>
            </div>
          </div>

        <div
          v-if="!isEditing"
          class="flex items-start gap-3 rounded-md border border-gray-200 dark:border-gray-700 p-3 bg-gray-50 dark:bg-gray-800/50"
        >
          <input
            id="email-assignee"
            v-model="form.email_assignee"
            type="checkbox"
            class="mt-1 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
          />
          <div>
            <label
              for="email-assignee"
              class="text-sm font-medium text-gray-700 dark:text-gray-200"
            >
              Email assignee
            </label>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
              Send an email notification to the selected assignee when the todo is created.
            </p>
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
                class="text-black hover:text-gray-900 dark:text-white dark:hover:text-gray-100"
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
              class="inline-flex items-center justify-center gap-2 rounded-md px-4 py-2 text-sm font-medium border transition-colors cursor-pointer bg-black/30 text-black dark:bg-white/30 dark:text-white"
            >
              {{ t('common.cancel') }}
            </button>
            <button
              type="submit"
              class="inline-flex items-center justify-center gap-2 rounded-md px-4 py-2 text-sm font-medium border transition-colors cursor-pointer bg-black text-white hover:bg-gray-900 hover:border-gray-900 dark:bg-white dark:text-black dark:hover:bg-gray-100 dark:hover:border-gray-300"
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
import { ref, watch, onMounted, computed } from 'vue';
import TipTapEditor from '@/components/TipTapEditor.vue';
import TypeSelector from '@/components/TypeSelector.vue';
import IconSelector from '@/components/IconSelector.vue';
import TodoTemplateSelector from '@/components/TodoTemplateSelector.vue';
import { usePage } from '@inertiajs/vue3';
import Icon from '@/components/Icon.vue';
import TodoComments from '@/components/TodoComments.vue';
import LocationPicker, { type LocationValue } from '@/components/LocationPicker.vue';
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
  } catch {
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
  email_assignee: false,
  card_icon: null as string | null,
  outline_color: null as string | null,
});

const todoType = ref('');

const useCustomOutlineColor = ref(false);

const defaultProjectColor = computed(() => props.currentProject?.color || '#3b82f6');

const outlineColorPicker = computed({
  get: () => form.value.outline_color || defaultProjectColor.value,
  set: (value: string) => {
    form.value.outline_color = value;
    useCustomOutlineColor.value = true;
  },
});

const resetOutlineColor = () => {
  form.value.outline_color = null;
  useCustomOutlineColor.value = false;
};

const location = ref<LocationValue>({
  location_name: null,
  location_address: null,
  latitude: null,
  longitude: null,
});

const tagsInput = ref('');

const selectedAssignee = computed<SimpleUser | null>(() => {
  if (!form.value.assignee) {
    return null;
  }

  const match = users.value.find((u) => u.name === form.value.assignee);
  if (match) {
    return match;
  }

  if (currentUser && currentUser.name === form.value.assignee) {
    return {
      id: currentUser.id,
      name: currentUser.name,
      email: currentUser.email,
    };
  }

  return null;
});

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
    email_assignee: false,
    card_icon: null,
    outline_color: null,
  };
  todoType.value = '';
  useCustomOutlineColor.value = false;
  tagsInput.value = '';
  location.value = {
    location_name: null,
    location_address: null,
    latitude: null,
    longitude: null,
  };
};

// Initialize form when editing
onMounted(() => {
  loadUsers();
});

watch(() => props.todo, (newTodo) => {
  if (newTodo) {
    form.value = { ...newTodo, type: newTodo.type ?? '' };
    todoType.value = newTodo.type ?? '';
    form.value.email_assignee = false;
    useCustomOutlineColor.value = !!newTodo.outline_color;
    form.value.card_icon = newTodo.card_icon ?? null;
    form.value.outline_color = newTodo.outline_color ?? null;
    
    // Fix date format for HTML date input (YYYY-MM-DD)
    if (newTodo.due_date) {
      // Convert from datetime to date format
      const date = new Date(newTodo.due_date);
      if (!isNaN(date.getTime())) {
        form.value.due_date = date.toISOString().split('T')[0];
      }
    }

    location.value = {
      location_name: newTodo.location_name ?? null,
      location_address: newTodo.location_address ?? null,
      latitude: newTodo.latitude ?? null,
      longitude: newTodo.longitude ?? null,
    };
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
      return;
    }
    
    const resolvedType = todoType.value.trim();

    const todoData: any = {
      title: form.value.title,
      description: form.value.description,
      priority: form.value.priority,
      type: resolvedType.length > 0 ? resolvedType : null,
      tags: form.value.tags,
      assignee: form.value.assignee ? form.value.assignee : null,
      due_date: form.value.due_date ? form.value.due_date : null,
      story_points: form.value.story_points ? form.value.story_points : null,
      status: form.value.status,
      project_id: props.currentProject.id,
      location_name: location.value.location_name,
      location_address: location.value.location_address,
      latitude: location.value.latitude,
      longitude: location.value.longitude,
      card_icon: form.value.card_icon || null,
      outline_color: useCustomOutlineColor.value ? form.value.outline_color : null,
    };
    
    if (props.isEditing) {
      todoData.id = parseInt(form.value.id);
      delete todoData.email_assignee;
      delete todoData.assignee_email;
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
      todoData.email_assignee = !!form.value.email_assignee;
      todoData.assignee_email = selectedAssignee.value?.email || null;
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
  } catch {}
};

// Template handlers
const handleTemplateSelected = (todos: any[]) => {
  // Close the template selector
  showTemplateSelector.value = false;
  
  // For now, just create the first todo from the template
  // In a real app, you might want to create multiple todos
  if (todos.length > 0) {
    const templateTodo = todos[0];
    form.value.title = templateTodo.title;
    form.value.description = templateTodo.description;
    form.value.priority = templateTodo.priority;
    form.value.type = templateTodo.type;
    todoType.value = templateTodo.type ?? '';
    form.value.assignee = templateTodo.assignee;
    form.value.tags = templateTodo.tags;
  }
};

const handleCreateTemplate = () => {
  // Close the template selector
  showTemplateSelector.value = false;
  // In a real app, this would open a template creation modal
};

// TipTap editor used; no extra config here

</script>
