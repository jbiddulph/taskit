<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
      <div class="p-6">
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
            {{ isEditing ? 'Edit Todo' : 'Add New Todo' }}
          </h2>
          <button
            @click="$emit('close')"
            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
          >
            <Icon name="X" class="w-6 h-6" />
          </button>
        </div>

        <form @submit.prevent="handleSubmit" class="space-y-4">
          <!-- Title -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Title *
            </label>
            <input
              v-model="form.title"
              type="text"
              required
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
              placeholder="Enter todo title"
            />
          </div>

          <!-- Description -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Description
            </label>
            <textarea
              v-model="form.description"
              rows="3"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
              placeholder="Enter description (optional)"
            ></textarea>
          </div>

          <!-- Project (Read-only) -->
          <div v-if="currentProject">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Project
            </label>
            <div class="px-3 py-2 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md text-gray-900 dark:text-gray-100">
              {{ currentProject.key }} - {{ currentProject.name }}
            </div>
          </div>

          <!-- Priority and Type -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Priority *
              </label>
              <select
                v-model="form.priority"
                required
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
              >
                <option value="Low">Low</option>
                <option value="Medium">Medium</option>
                <option value="High">High</option>
                <option value="Critical">Critical</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Type
              </label>
              <select
                v-model="form.type"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
              >
                <option value="">Select type</option>
                <option value="Bug">Bug</option>
                <option value="Feature">Feature</option>
                <option value="Task">Task</option>
                <option value="Story">Story</option>
                <option value="Epic">Epic</option>
              </select>
            </div>
          </div>

          <!-- Assignee and Due Date -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Assignee
              </label>
              <input
                v-model="form.assignee"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                placeholder="Enter assignee name"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Due Date
              </label>
              <input
                v-model="form.due_date"
                type="date"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
              />
            </div>
          </div>

          <!-- Story Points and Tags -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Story Points
              </label>
              <input
                v-model.number="form.story_points"
                type="number"
                min="1"
                max="21"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                placeholder="1-21"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Tags
              </label>
              <input
                v-model="tagsInput"
                type="text"
                @keydown.enter.prevent="addTag"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                placeholder="Type and press Enter to add tags"
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

          <!-- Status -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Status *
            </label>
            <select
              v-model="form.status"
              required
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
            >
              <option value="todo">To Do</option>
              <option value="in-progress">In Progress</option>
              <option value="done">Done</option>
            </select>
          </div>

          <!-- Action buttons -->
          <div class="flex justify-end gap-3 pt-4">
            <button
              type="button"
              @click="$emit('close')"
              class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
            >
              Cancel
            </button>
            <button
              type="submit"
              class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
            >
              {{ isEditing ? 'Update' : 'Create' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue';
import Icon from '@/components/Icon.vue';
import { todoApi, type Project } from '@/services/todoApi';
import type { Todo } from '@/services/todoApi';

interface Props {
  todo?: Todo;
  isEditing?: boolean;
  currentProject?: Project | null;
}

const props = withDefaults(defineProps<Props>(), {
  isEditing: false,
});

const emit = defineEmits<{
  close: [];
  save: [todo: Todo];
}>();

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

watch(() => props.todo, (newTodo) => {
  if (newTodo) {
    form.value = { ...newTodo };
  } else {
    resetForm();
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
      due_date: form.value.due_date || null,
      story_points: form.value.story_points || null,
    };
    
    if (props.isEditing) {
      todoData.id = parseInt(form.value.id);
    }
    
    emit('save', todoData);
    resetForm();
  } catch (error) {
    console.error('Failed to submit todo:', error);
  }
};


</script>
