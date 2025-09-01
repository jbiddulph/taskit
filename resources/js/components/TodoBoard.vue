<template>
  <div class="h-full flex flex-col">
    <!-- Header with Search and Filters -->
    <div 
      class="mb-6 p-4 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 relative overflow-hidden"
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
          <!-- Clickable Project Display with Dropdown -->
          <div class="relative project-dropdown-container">
            <button
              @click="toggleProjectDropdown"
              class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors cursor-pointer min-w-[200px]"
              :style="currentProject ? { borderLeftColor: currentProject.color, borderLeftWidth: '4px' } : {}"
            >
              <!-- Project Color Indicator -->
              <div 
                v-if="currentProject"
                class="w-4 h-4 rounded-full flex-shrink-0"
                :style="{ backgroundColor: currentProject.color }"
              ></div>
              <div v-else class="w-4 h-4 rounded-full flex-shrink-0 bg-gray-300"></div>
              
              <!-- Project Info -->
              <div class="text-left flex-1">
                <div class="font-medium text-gray-900 dark:text-gray-100">
                  {{ currentProject?.name || 'Select a Project' }}
                </div>
                <div v-if="currentProject" class="text-xs text-gray-500">
                  {{ currentProject.key }}
                </div>
              </div>
              
              <!-- Dropdown Arrow -->
              <Icon 
                name="ChevronDown" 
                class="w-4 h-4 text-gray-400 transition-transform duration-200"
                :class="{ 'rotate-180': showProjectDropdown }"
              />
            </button>
            
            <!-- Project Dropdown Menu -->
            <div 
              v-if="showProjectDropdown"
              class="absolute top-full left-0 mt-1 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg z-[9999] max-h-60 overflow-y-auto"
              style="min-width: 250px;"
            >
              <div class="p-2">
                <div class="text-xs font-medium text-gray-500 dark:text-gray-400 px-2 py-1 mb-2">
                  Select Project ({{ projects.length }} available)
                </div>
                <button
                  @click="selectProject(null)"
                  class="w-full text-left px-3 py-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 transition-colors"
                >
                  No Project Selected
                </button>
                <div class="border-t border-gray-200 dark:border-gray-600 my-2"></div>
                <div v-if="projects.length === 0" class="px-3 py-2 text-sm text-gray-500">
                  No projects available
                </div>
                <button
                  v-for="project in projects"
                  :key="project.id"
                  @click="selectProject(project)"
                  class="w-full text-left px-3 py-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors flex items-center gap-3"
                >
                  <div 
                    class="w-3 h-3 rounded-full flex-shrink-0"
                    :style="{ backgroundColor: project.color }"
                  ></div>
                  <div>
                    <div class="font-medium text-gray-900 dark:text-gray-100">
                      {{ project.name }}
                    </div>
                    <div class="text-xs text-gray-500">
                      {{ project.key }}
                    </div>
                  </div>
                </button>
              </div>
            </div>
          </div>
          
          <button
            @click="handleShowForm"
            :disabled="!currentProject"
            class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:ring-offset-2 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <Icon name="Plus" class="w-4 h-4" />
            Add Todo
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
          
          <select
            v-model="typeFilter"
            class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white text-gray-900"
          >
            <option value="">All Types</option>
            <option value="Bug">Bug</option>
            <option value="Feature">Feature</option>
            <option value="Task">Task</option>
            <option value="Story">Story</option>
            <option value="Epic">Epic</option>
          </select>
          
          <select
            v-model="assigneeFilter"
            class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white text-gray-900"
          >
            <option value="">All Assignees</option>
            <option v-for="assignee in uniqueAssignees" :key="assignee" :value="assignee">
              {{ assignee }}
            </option>
          </select>
        </div>
      </div>
    </div>

    <!-- Statistics -->
    <TodoStats :todos="todos" />

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
    <div v-if="showCreateProject" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
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
    <div v-if="showEditProject" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
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
import { todoApi, type Project, type Todo } from '@/services/todoApi';

const todos = ref<Todo[]>([]);
const projects = ref<Project[]>([]);
const showForm = ref(false);
const showCreateProject = ref(false);
const showEditProject = ref(false);
const showProjectDropdown = ref(false);
const currentProject = ref<Project | null>(null);
const selectedProjectId = ref<string>('');
const editingTodo = ref<Todo | null>(null);
const editingProjectName = ref('');
const searchQuery = ref('');
const priorityFilter = ref('');
const typeFilter = ref('');
const assigneeFilter = ref('');

const newProject = ref({
  name: '',
  description: '',
  key: '',
  color: '#3B82F6'
});

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

// Methods
const editTodo = (todo: Todo) => {
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
    await todoApi.deleteTodo(id);
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
    const index = todos.value.findIndex(t => t.id === todo.id);
    if (index !== -1) {
      todos.value[index] = updatedTodo;
      console.log('Todo updated in local state');
      
      // Dispatch event to refresh sidebar project stats
      window.dispatchEvent(new CustomEvent('todoChanged'));
      
      if ((window as any).$notify) {
        (window as any).$notify({
          type: 'success',
          title: 'Status Updated',
          message: `Todo "${updatedTodo.title}" moved to ${newStatus.replace('-', ' ')}.`
        });
      }
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
    const response = await todoApi.getProjectsWithStats();
    console.log('Projects response:', response);
    projects.value = response.data;
    console.log('Projects loaded:', projects.value.length, 'projects');
    
    // Set selected project ID if current project exists
    if (currentProject.value) {
      selectedProjectId.value = currentProject.value.id.toString();
      console.log('Set selected project ID to:', selectedProjectId.value);
    }
  } catch (error) {
    console.error('Failed to load projects:', error);
  }
};

// Toggle project dropdown
const toggleProjectDropdown = () => {
  showProjectDropdown.value = !showProjectDropdown.value;
};

// Select a project from the dropdown
const selectProject = async (project: Project | null) => {
  showProjectDropdown.value = false;
  
  if (!project) {
    console.log('No project selected, clearing current project');
    currentProject.value = null;
    localStorage.removeItem('currentProjectId');
    todos.value = [];
    return;
  }
  
  try {
    console.log('Loading project:', project.name);
    const loadedProject = await todoApi.getProject(project.id);
    console.log('Project loaded:', loadedProject);
    
    currentProject.value = loadedProject;
    localStorage.setItem('currentProjectId', loadedProject.id.toString());
    
    // Load todos for the selected project
    await loadTodos();
    
    if ((window as any).$notify) {
      (window as any).$notify({
        type: 'success',
        title: 'Project Selected',
        message: `Switched to project "${loadedProject.name}".`
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
  
  try {
    const projectId = parseInt(selectedProjectId.value);
    console.log('Loading project with ID:', projectId);
    const project = await todoApi.getProject(projectId);
    console.log('Project loaded:', project);
    
    currentProject.value = project;
    localStorage.setItem('currentProjectId', projectId.toString());
    
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
  
  // Handle clicks outside the project dropdown
  const handleClickOutside = (event: Event) => {
    const target = event.target as Element;
    if (!target.closest('.project-dropdown-container')) {
      showProjectDropdown.value = false;
    }
  };
  
  window.addEventListener('storage', handleStorageChange);
  window.addEventListener('click', handleClickOutside);
  
  // Also listen for custom events from the sidebar
  window.addEventListener('projectSelected', async (e: any) => {
    if (e.detail?.projectId) {
      localStorage.setItem('currentProjectId', e.detail.projectId.toString());
      await loadCurrentProject();
    }
  });
  
  // Listen for project creation modal open request from sidebar
  window.addEventListener('openCreateProjectModal', () => {
    showCreateProject.value = true;
    resetNewProject();
  });
});


</script>
