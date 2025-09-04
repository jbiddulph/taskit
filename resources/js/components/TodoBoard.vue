<template>
  <div class="h-full flex flex-col">
    <!-- Header with Search and Filters -->
    <div 
      class="mb-6 p-4 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 relative"
      :style="currentProject ? {
        borderTop: `4px solid ${currentProject.color}`,
        backgroundColor: `${currentProject.color}10`
      } : {}"
    >
      <div class="flex flex-col lg:flex-row gap-4 items-start lg:items-center justify-between">
        <div class="flex-1">
          <div class="flex items-center gap-3 mb-2">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
              {{ currentProject ? currentProject.name : 'Todo Board' }}
            </h1>
            <button
              v-if="currentProject"
              @click="startEditProject"
              class="p-1 text-gray-400 hover:text-blue-500 transition-colors"
              title="Edit project name"
            >
              <Icon name="Edit3" class="w-5 h-5" />
            </button>
          </div>
          <p class="text-gray-600 dark:text-gray-400">
            {{ currentProject ? `Project: ${currentProject.key} - ${currentProject.description || 'No description'}` : 'Select a project to manage todos' }}
          </p>
          
                      <!-- Create Project Button (only shown when no project is selected) -->
            <div v-if="!currentProject" class="mt-4">
              <button
                @click="showCreateProject = true"
                class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors"
              >
                <Icon name="Plus" class="w-4 h-4" />
                Create Project
              </button>
            </div>
        </div>
        
        <div class="flex items-center gap-3">
          <!-- Project Select Dropdown -->
          <div class="relative flex items-center gap-2">
            <!-- Project Color Indicator -->
            <div 
              v-if="currentProject"
              class="w-4 h-4 rounded-full flex-shrink-0"
              :style="{ backgroundColor: currentProject.color }"
            ></div>
            <div v-else class="w-4 h-4 rounded-full flex-shrink-0 bg-gray-300"></div>
            
            <!-- HTML Select Dropdown -->
            <select
              v-model="selectedProjectId"
              @change="onProjectChange"
              class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 min-w-[200px] cursor-pointer"
              :style="currentProject ? { borderLeftColor: currentProject.color, borderLeftWidth: '4px' } : {}"
            >
              <option 
                v-for="project in safeProjects" 
                :key="project.id" 
                :value="project.id"
              >
                {{ project.name }} ({{ project.key }})
              </option>
            </select>
          </div>
          
          <button
            @click="handleShowForm"
            :disabled="!currentProject"
            class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:ring-offset-2 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <Icon name="Plus" class="w-4 h-4" />
            Add Todo
          </button>

          <button
            @click="showCalendar = !showCalendar"
            class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-100 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 border border-gray-300 dark:border-gray-600"
          >
            <Icon name="Calendar" class="w-4 h-4" />
            {{ showCalendar ? 'Hide Calendar' : 'Show Calendar' }}
          </button>
        </div>
      </div>

      <!-- Search and Filters -->
      <div class="mt-4 flex flex-col lg:flex-row gap-4">
        <div class="flex-1 relative">
          <Icon name="Search" class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search todos..."
            class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white text-gray-900"
          />
        </div>
        
        <div class="flex gap-2">
          <select
            v-model="priorityFilter"
            class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white text-gray-900"
          >
            <option value="">All Priorities</option>
            <option value="Low">Low</option>
            <option value="Medium">Medium</option>
            <option value="High">High</option>
            <option value="Critical">Critical</option>
          </select>
          
          <TypeFilter v-model="typeFilter" />
          
          <select
            v-model="assigneeFilter"
            class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white text-gray-900"
          >
            <option value="">All Assignees</option>
            <option v-for="assignee in uniqueAssignees" :key="assignee" :value="assignee">
              {{ assignee }}
            </option>
          </select>

          <!-- Saved Views Controls -->
          <div class="flex items-center gap-2">
            <select
              v-model="selectedSavedViewName"
              @change="onApplySavedView"
              class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
            >
              <option value="">Saved Views</option>
              <option v-for="view in savedViews" :key="view.name" :value="view.name">{{ view.name }}</option>
            </select>

            <input
              v-model="newSavedViewName"
              type="text"
              placeholder="View name"
              class="w-32 px-2 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
            />

            <button
              type="button"
              @click="saveCurrentAsView"
              class="px-3 py-2 bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-100 rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-200 dark:hover:bg-gray-600"
              title="Save current filters as a view"
            >
              Save
            </button>

            <button
              v-if="selectedSavedViewName"
              type="button"
              @click="deleteSavedView"
              class="px-3 py-2 bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded-lg border border-red-200 dark:border-red-700 hover:bg-red-100 dark:hover:bg-red-900/50"
              title="Delete selected view"
            >
              Delete
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Statistics / Calendar -->
    <div v-if="showCalendar" class="mb-4">
      <CalendarView :todos="todos" @edit-todo="handleEditTodoFromCalendar" />
    </div>
    <TodoStats v-else :todos="todos" />

    <!-- Kanban Board -->
    <div class="flex-1 flex gap-6 overflow-x-auto pb-4">
      <TodoColumn
        title="To Do"
        status="todo"
        :todos="filteredTodos.todo"
        :show-add-button="true"
        :current-project-id="currentProject?.id || null"
        @add="handleShowForm"
        @edit="editTodo"
        @delete="deleteTodo"
        @drop="handleDrop"
        @menu="() => {}"
      />
      
      <TodoColumn
        title="In Progress"
        status="in-progress"
        :todos="filteredTodos.inProgress"
        :current-project-id="currentProject?.id || null"
        @edit="editTodo"
        @delete="deleteTodo"
        @drop="handleDrop"
        @menu="() => {}"
      />
      
      <TodoColumn
        title="Done"
        status="done"
        :todos="filteredTodos.done"
        :current-project-id="currentProject?.id || null"
        @edit="editTodo"
        @delete="deleteTodo"
        @drop="handleDrop"
        @menu="() => {}"
      />
    </div>

    <!-- Todo Form Modal -->
    <TodoForm
      v-if="showForm"
      :todo="editingTodo"
      :is-editing="!!editingTodo"
      :current-project="currentProject"
      @close="closeForm"
      @save="saveTodo"
    />

    <!-- Create Project Modal -->
    <div v-if="showCreateProject" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full">
        <div class="p-6">
          <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Create New Project</h2>
            <button
              @click="showCreateProject = false"
              class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
            >
              <Icon name="X" class="w-6 h-6" />
            </button>
          </div>
          
          <form @submit.prevent="createProject" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Project Name *
              </label>
              <input
                v-model="newProject.name"
                type="text"
                required
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                placeholder="Enter project name"
              />
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Description (Optional)
              </label>
              <textarea
                v-model="newProject.description"
                rows="3"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                placeholder="Enter project description"
              ></textarea>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Project Key (Optional)
              </label>
              <input
                v-model="newProject.key"
                type="text"
                maxlength="10"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                placeholder="e.g., PROJ, FEAT"
              />
              <p class="text-sm text-gray-500 mt-1">Leave empty to auto-generate</p>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Color
              </label>
              <div class="flex items-center gap-3">
                <input
                  v-model="newProject.color"
                  type="color"
                  class="w-12 h-10 border border-gray-300 dark:border-gray-600 rounded-md cursor-pointer"
                />
                <span class="text-sm text-gray-600 dark:text-gray-400">{{ newProject.color }}</span>
              </div>
            </div>
            
            <div class="flex gap-3 pt-4">
              <button
                type="submit"
                class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors"
              >
                Create Project
              </button>
              <button
                type="button"
                @click="showCreateProject = false"
                class="flex-1 px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors"
              >
                Cancel
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Edit Project Modal -->
    <div v-if="showEditProject" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full">
        <div class="p-6">
          <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Edit Project</h2>
            <button
              @click="showEditProject = false"
              class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
            >
              <Icon name="X" class="w-6 h-6" />
            </button>
          </div>
          
          <form @submit.prevent="saveEditProject" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Project Name *
              </label>
              <input
                v-model="editingProjectName"
                type="text"
                required
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                placeholder="Enter project name"
              />
            </div>
            
            <div class="flex gap-3 pt-4">
              <button
                type="submit"
                class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors"
              >
                Save Changes
              </button>
              <button
                type="button"
                @click="showEditProject = false"
                class="flex-1 px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors"
              >
                Cancel
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import Icon from '@/components/Icon.vue';
import TodoColumn from './TodoColumn.vue';
import TodoForm from './TodoForm.vue';
import TodoStats from './TodoStats.vue';
import TypeFilter from './TypeFilter.vue';
import CalendarView from './CalendarView.vue';
import { todoApi, type Project, type Todo } from '@/services/todoApi';
import { deleteImagesInHtml } from '@/services/supabaseClient';

const todos = ref<Todo[]>([]);
const projects = ref<Project[]>([]);
const showForm = ref(false);
const showCreateProject = ref(false);
const showEditProject = ref(false);
const showCalendar = ref(false);

const currentProject = ref<Project | null>(null);
const selectedProjectId = ref<string>('');
const editingTodo = ref<Todo | null>(null);
const editingProjectName = ref('');
const searchQuery = ref('');
const priorityFilter = ref('');
const typeFilter = ref('');
const assigneeFilter = ref('');

// Saved Views (local-only for now)
type SavedView = {
  name: string;
  searchQuery: string;
  priority: string;
  type: string;
  assignee: string;
};
const SAVED_VIEWS_KEY = 'taskit_saved_views_v1';
const savedViews = ref<SavedView[]>([]);
const selectedSavedViewName = ref('');
const newSavedViewName = ref('');

const loadSavedViews = () => {
  try {
    const raw = localStorage.getItem(SAVED_VIEWS_KEY);
    savedViews.value = raw ? JSON.parse(raw) : [];
  } catch {
    savedViews.value = [];
  }
};

const persistSavedViews = () => {
  localStorage.setItem(SAVED_VIEWS_KEY, JSON.stringify(savedViews.value));
};

const getCurrentFilters = (): SavedView => ({
  name: '',
  searchQuery: searchQuery.value || '',
  priority: priorityFilter.value || '',
  type: typeFilter.value || '',
  assignee: assigneeFilter.value || '',
});

const applyView = (view: SavedView) => {
  searchQuery.value = view.searchQuery;
  priorityFilter.value = view.priority;
  typeFilter.value = view.type;
  assigneeFilter.value = view.assignee;
};

const onApplySavedView = () => {
  const view = savedViews.value.find(v => v.name === selectedSavedViewName.value);
  if (view) applyView(view);
};

const saveCurrentAsView = () => {
  const name = newSavedViewName.value.trim();
  if (!name) return;
  const existingIdx = savedViews.value.findIndex(v => v.name === name);
  const payload = { ...getCurrentFilters(), name };
  if (existingIdx >= 0) {
    savedViews.value.splice(existingIdx, 1, payload);
  } else {
    savedViews.value.push(payload);
  }
  persistSavedViews();
  selectedSavedViewName.value = name;
};

const deleteSavedView = () => {
  const name = selectedSavedViewName.value;
  if (!name) return;
  const idx = savedViews.value.findIndex(v => v.name === name);
  if (idx >= 0) {
    savedViews.value.splice(idx, 1);
    persistSavedViews();
    selectedSavedViewName.value = '';
  }
};

const newProject = ref({
  name: '',
  description: '',
  key: '',
  color: '#3B82F6'
});
const isUpdatingProject = ref(false); // Flag to prevent circular events

// Computed properties
const filteredTodos = computed(() => {
  let filtered = todos.value.filter(todo => {
    const matchesSearch = !searchQuery.value || 
      todo.title.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      todo.description?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      todo.tags?.some(tag => tag.toLowerCase().includes(searchQuery.value.toLowerCase()));
    
    const matchesPriority = !priorityFilter.value || todo.priority === priorityFilter.value;
    const matchesType = !typeFilter.value || todo.type === typeFilter.value;
    const matchesAssignee = !assigneeFilter.value || todo.assignee === assigneeFilter.value;
    
    return matchesSearch && matchesPriority && matchesType && matchesAssignee;
  });

  return {
    todo: filtered.filter(t => t.status === 'todo'),
    inProgress: filtered.filter(t => t.status === 'in-progress'),
    done: filtered.filter(t => t.status === 'done')
  };
});

const uniqueAssignees = computed(() => {
  const assignees = todos.value
    .map(todo => todo.assignee)
    .filter(Boolean) as string[];
  return [...new Set(assignees)];
});

// Safe projects array to prevent undefined errors
const safeProjects = computed(() => {
  return projects.value || [];
});

// Methods
const editTodo = (todo: Todo) => {
  editingTodo.value = { ...todo };
  showForm.value = true;
};

const handleEditTodoFromCalendar = (todo: Todo) => {
  editingTodo.value = { ...todo };
  showForm.value = true;
};

const saveTodo = async (todo: Todo) => {
  try {
    if (!currentProject.value) {
      if ((window as any).$notify) {
        (window as any).$notify({
          type: 'error',
          title: 'Project Required',
          message: 'Please select a project first before creating a todo.'
        });
      }
      return;
    }
    
    if (editingTodo.value) {
      // Update existing todo
      const updatedTodo = await todoApi.updateTodo(todo.id, todo);
      const index = todos.value.findIndex(t => t.id === todo.id);
      if (index !== -1) {
        todos.value[index] = updatedTodo;
      }
      editingTodo.value = null;
      
      if ((window as any).$notify) {
        (window as any).$notify({
          type: 'success',
          title: 'Todo Updated',
          message: `Todo "${updatedTodo.title}" has been updated successfully.`
        });
      }
    } else {
      // Add new todo
      const newTodo = await todoApi.createTodo({
        ...todo,
        project_id: currentProject.value.id
      });
      todos.value.push(newTodo);
      
      if ((window as any).$notify) {
        (window as any).$notify({
          type: 'success',
          title: 'Todo Created',
          message: `Todo "${newTodo.title}" has been created successfully.`
        });
      }
    }
    showForm.value = false;
    
    // Dispatch event to refresh sidebar projects
    window.dispatchEvent(new CustomEvent('projectCreated'));
    
    // Dispatch event to refresh sidebar project stats
    window.dispatchEvent(new CustomEvent('todoChanged'));
  } catch (error) {
    console.error('Failed to save todo:', error);
    if ((window as any).$notify) {
      (window as any).$notify({
        type: 'error',
        title: 'Save Failed',
        message: 'Failed to save todo. Please try again.'
      });
    }
  }
};

const deleteTodo = async (id: string) => {
  const todo = todos.value.find(t => t.id === id);
  if (!todo) return;
  
  if (!confirm(`Are you sure you want to delete "${todo.title}"?\n\nThis action cannot be undone.`)) {
    return;
  }
  
  try {
    const toDelete = todos.value.find(t => t.id === id);
    await todoApi.deleteTodo(id);
    // Attempt to cleanup images from Supabase storage
    if (toDelete?.description) {
      deleteImagesInHtml(toDelete.description).catch(() => {});
    }
    todos.value = todos.value.filter(t => t.id !== id);
    
    // Dispatch event to refresh sidebar project stats
    window.dispatchEvent(new CustomEvent('todoChanged'));
    
    if ((window as any).$notify) {
      (window as any).$notify({
        type: 'success',
        title: 'Todo Deleted',
        message: `Todo "${todo.title}" has been deleted successfully.`
      });
    }
  } catch (error) {
    console.error('Failed to delete todo:', error);
    if ((window as any).$notify) {
      (window as any).$notify({
        type: 'error',
        title: 'Delete Failed',
        message: 'Failed to delete todo. Please try again.'
      });
    }
  }
};

const handleDrop = async (todo: Todo, newStatus: string) => {
  console.log('TodoBoard received drop event:', todo.title, '->', newStatus);
  
  // Validate that the todo belongs to the current project
  if (!currentProject.value) {
    if ((window as any).$notify) {
      (window as any).$notify({
        type: 'error',
        title: 'Project Required',
        message: 'Please select a project before moving todos.'
      });
    }
    return;
  }
  
  if (todo.project_id !== currentProject.value.id) {
    if ((window as any).$notify) {
      (window as any).$notify({
        type: 'error',
        title: 'Invalid Move',
        message: `Cannot move todo "${todo.title}" - it belongs to a different project.`
      });
    }
    return;
  }
  
  try {
    const updatedTodo = await todoApi.updateTodoStatus(todo.id, newStatus as 'todo' | 'in-progress' | 'done');
    console.log('API response:', updatedTodo);
    
    // Find the todo by ID, ensuring type consistency
    const index = todos.value.findIndex(t => String(t.id) === String(todo.id));
    console.log('Looking for todo with ID:', todo.id, 'Found at index:', index);
    
    if (index !== -1) {
      todos.value[index] = updatedTodo;
      console.log('Todo updated in local state:', updatedTodo);
      
      // Force reactivity update
      todos.value = [...todos.value];
      
      // Dispatch event to refresh sidebar project stats
      window.dispatchEvent(new CustomEvent('todoChanged'));
      
      if ((window as any).$notify) {
        (window as any).$notify({
          type: 'success',
          title: 'Status Updated',
          message: `Todo "${updatedTodo.title}" moved to ${newStatus.replace('-', ' ')}.`
        });
      }
    } else {
      console.error('Todo not found in local state for ID:', todo.id);
      // Reload todos from API as fallback
      await loadTodos();
    }
  } catch (error) {
    console.error('Failed to update todo status:', error);
    if ((window as any).$notify) {
      (window as any).$notify({
        type: 'error',
        title: 'Update Failed',
        message: 'Failed to update todo status. Please try again.'
      });
    }
  }
};

const closeForm = () => {
  showForm.value = false;
  editingTodo.value = null;
};

const handleShowForm = () => {
  if (!currentProject.value) {
    if ((window as any).$notify) {
      (window as any).$notify({
        type: 'warning',
        title: 'Project Required',
        message: 'Please select a project first before creating a todo.'
      });
    }
    return;
  }
  showForm.value = true;
};

const startEditProject = () => {
  if (!currentProject.value) return;
  editingProjectName.value = currentProject.value.name;
  showEditProject.value = true;
};

const saveEditProject = async () => {
  if (!currentProject.value || !editingProjectName.value.trim()) return;
  
  try {
    const updatedProject = await todoApi.updateProject(currentProject.value.id, {
      name: editingProjectName.value.trim()
    });
    
    // Update current project
    currentProject.value = { ...currentProject.value, ...updatedProject };
    
    // Show success notification
    if ((window as any).$notify) {
      (window as any).$notify({
        type: 'success',
        title: 'Project Updated',
        message: `Project "${updatedProject.name}" has been updated successfully.`
      });
    }
    
    // Close modal
    showEditProject.value = false;
    editingProjectName.value = '';
    
    // Dispatch event to refresh sidebar projects
    window.dispatchEvent(new CustomEvent('todoChanged'));
    
    console.log('Project updated successfully:', updatedProject.name);
  } catch (error) {
    console.error('Failed to update project:', error);
    if ((window as any).$notify) {
      (window as any).$notify({
        type: 'error',
        title: 'Update Failed',
        message: 'Failed to update project. Please try again.'
      });
    }
  }
};



const resetNewProject = () => {
  newProject.value = {
    name: '',
    description: '',
    key: '',
    color: '#3B82F6'
  };
};

const createProject = async () => {
  try {
    const projectData = {
      name: newProject.value.name,
      description: newProject.value.description || undefined,
      key: newProject.value.key || undefined,
      color: newProject.value.color
    };
    
    const newProjectCreated = await todoApi.createProject(projectData);
    
    // Set as current project
    currentProject.value = newProjectCreated;
    localStorage.setItem('currentProjectId', newProjectCreated.id.toString());
    
    // Close modal and reset form
    showCreateProject.value = false;
    resetNewProject();
    
    // Refresh todos
    await loadTodos();
    
    // Dispatch event to refresh sidebar projects
    window.dispatchEvent(new CustomEvent('projectCreated'));
    
    // Dispatch event to refresh sidebar project stats
    window.dispatchEvent(new CustomEvent('todoChanged'));
    
    if ((window as any).$notify) {
      (window as any).$notify({
        type: 'success',
        title: 'Project Created',
        message: `Project "${newProjectCreated.name}" has been created successfully.`
      });
    }
    
    console.log('Project created successfully:', newProjectCreated.name);
  } catch (error) {
    console.error('Failed to create project:', error);
    if ((window as any).$notify) {
      (window as any).$notify({
        type: 'error',
        title: 'Creation Failed',
        message: 'Failed to create project. Please try again.'
      });
    }
  }
};

// Load projects from API
const loadProjects = async () => {
  try {
    console.log('Loading projects...');
    
    // Try to load projects with stats first
    try {
      const response = await todoApi.getProjectsWithStats();
      console.log('Projects with stats response:', response);
      
      // Check if response has the expected structure
      if (response && Array.isArray(response)) {
        projects.value = response;
        console.log('Projects loaded with stats:', projects.value.length, 'projects');
      } else if (response && response.data && Array.isArray(response.data)) {
        projects.value = response.data;
        console.log('Projects loaded with stats:', projects.value.length, 'projects');
      } else {
        console.error('Unexpected response structure from getProjectsWithStats:', response);
        throw new Error('Invalid response structure');
      }
    } catch (statsError) {
      console.log('Failed to load projects with stats, trying regular getProjects:', statsError);
      
      // Fallback to regular getProjects
      const response = await todoApi.getProjects();
      console.log('Regular projects response:', response);
      
      if (response && Array.isArray(response)) {
        projects.value = response;
        console.log('Projects loaded with regular method:', projects.value.length, 'projects');
      } else if (response && response.data && Array.isArray(response.data)) {
        projects.value = response.data;
        console.log('Projects loaded with regular method:', projects.value.length, 'projects');
      } else {
        console.error('Unexpected response structure from getProjects:', response);
        projects.value = [];
      }
    }
    
    // Set selected project ID if current project exists
    if (currentProject.value) {
      selectedProjectId.value = currentProject.value.id.toString();
      console.log('Set selected project ID to:', selectedProjectId.value);
    }
  } catch (error) {
    console.error('Failed to load projects completely:', error);
    projects.value = [];
  }
};



// Handle project selection change (keeping for backward compatibility)
const onProjectChange = async () => {
  console.log('Project selection changed to:', selectedProjectId.value);
  
  if (!selectedProjectId.value) {
    console.log('No project selected, clearing current project');
    currentProject.value = null;
    localStorage.removeItem('currentProjectId');
    todos.value = [];
    return;
  }
  
  // Prevent circular events
  if (isUpdatingProject.value) {
    console.log('Skipping project change - already updating');
    return;
  }
  
  try {
    isUpdatingProject.value = true;
    
    const projectId = parseInt(selectedProjectId.value);
    console.log('Loading project with ID:', projectId);
    const project = await todoApi.getProject(projectId);
    console.log('Project loaded:', project);
    
    currentProject.value = project;
    localStorage.setItem('currentProjectId', projectId.toString());
    
    // Dispatch event to update sidebar selection
    window.dispatchEvent(new CustomEvent('projectSelected', {
      detail: { projectId: projectId }
    }));
    
    // Load todos for the selected project
    await loadTodos();
    
    if ((window as any).$notify) {
      (window as any).$notify({
        type: 'success',
        title: 'Project Selected',
        message: `Switched to project "${project.name}".`
      });
    }
  } catch (error) {
    console.error('Failed to load project:', error);
    if ((window as any).$notify) {
      (window as any).$notify({
        type: 'error',
        title: 'Project Load Failed',
        message: 'Failed to load the selected project. Please try again.'
      });
    }
  } finally {
    isUpdatingProject.value = false;
  }
};

// Load current project from localStorage
const loadCurrentProject = async () => {
  try {
    const projectId = localStorage.getItem('currentProjectId');
    if (projectId) {
      const project = await todoApi.getProject(parseInt(projectId));
      currentProject.value = project;
      selectedProjectId.value = projectId;
      
      // Don't dispatch event here to prevent circular loop
      // The sidebar will be updated when the component mounts
    }
  } catch (error) {
    console.error('Failed to load current project:', error);
    localStorage.removeItem('currentProjectId');
  }
};

// Load todos from API
const loadTodos = async () => {
  try {
    const filters: any = {};
    if (currentProject.value) {
      filters.project_id = currentProject.value.id;
    }
    const response = await todoApi.getTodos(filters);
    todos.value = response.data.todo.concat(response.data['in-progress']).concat(response.data.done);
    

  } catch (error) {
    console.error('Failed to load todos:', error);
  }
};

// Watch for project changes and reload todos
watch(currentProject, async (newProject) => {
  if (newProject) {
    selectedProjectId.value = newProject.id.toString();
  }
  await loadTodos();
});

// Watch for project creation modal and reset form when opened
watch(showCreateProject, (newValue) => {
  if (newValue) {
    resetNewProject();
  }
});

// Single onMounted hook to handle all initialization
onMounted(async () => {
  // Load projects first (so dropdown has options)
  await loadProjects();
  
  // Then load current project from localStorage
  await loadCurrentProject();
  
  // Finally load todos for the current project
  await loadTodos();
  
  // Set up event listeners
  const handleStorageChange = (e: StorageEvent) => {
    if (e.key === 'currentProjectId') {
      loadCurrentProject();
    }
  };
  
  window.addEventListener('storage', handleStorageChange);
  
  // Also listen for custom events from the sidebar
  window.addEventListener('projectSelected', async (e: any) => {
    if (e.detail?.projectId && !isUpdatingProject.value) {
      isUpdatingProject.value = true;
      try {
        localStorage.setItem('currentProjectId', e.detail.projectId.toString());
        await loadCurrentProject();
      } finally {
        isUpdatingProject.value = false;
      }
    }
  });
  
  // Listen for project creation modal open request from sidebar
  window.addEventListener('openCreateProjectModal', () => {
    showCreateProject.value = true;
    resetNewProject();
  });

  // Load saved views
  loadSavedViews();
});


</script>
