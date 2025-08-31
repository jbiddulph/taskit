<template>
  <div class="project-selector">
    <!-- Current Project Display -->
    <div class="current-project" @click="showProjectDropdown = !showProjectDropdown">
      <div class="project-info">
        <div 
          class="project-color" 
          :style="{ backgroundColor: currentProject?.color || '#3B82F6' }"
        ></div>
        <div class="project-details">
          <div class="project-name">{{ currentProject?.name || 'Select Project' }}</div>
          <div class="project-key">{{ currentProject?.key || '' }}</div>
        </div>
      </div>
      <Icon name="ChevronDown" class="w-4 h-4 text-gray-500" />
    </div>

    <!-- Project Dropdown -->
    <div v-if="showProjectDropdown" class="project-dropdown">
      <div class="dropdown-header">
        <h3 class="text-sm font-medium text-gray-900">Projects</h3>
        <button 
          @click="showCreateProject = true"
          class="text-blue-600 hover:text-blue-700 text-sm font-medium"
        >
          + New Project
        </button>
      </div>
      
      <div class="project-list">
        <div 
          v-for="project in projects" 
          :key="project.id"
          @click="selectProject(project)"
          class="project-item"
          :class="{ 'active': currentProject?.id === project.id }"
        >
          <div 
            class="project-color" 
            :style="{ backgroundColor: project.color }"
          ></div>
          <div class="project-details">
            <div class="project-name">{{ project.name }}</div>
            <div class="project-key">{{ project.key }}</div>
            <div class="project-stats">
              {{ project.total_todos || 0 }} todos
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Create Project Modal -->
    <div v-if="showCreateProject" class="modal-overlay" @click="showCreateProject = false">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h2 class="text-lg font-semibold">Create New Project</h2>
          <button @click="showCreateProject = false" class="text-gray-400 hover:text-gray-600">
            <Icon name="X" class="w-5 h-5" />
          </button>
        </div>
        
        <form @submit.prevent="createProject" class="modal-body">
          <div class="form-group">
            <label for="project-name">Project Name</label>
            <input
              id="project-name"
              v-model="newProject.name"
              type="text"
              placeholder="Enter project name"
              class="form-input"
              required
            />
          </div>
          
          <div class="form-group">
            <label for="project-description">Description (Optional)</label>
            <textarea
              id="project-description"
              v-model="newProject.description"
              placeholder="Enter project description"
              class="form-textarea"
              rows="3"
            ></textarea>
          </div>
          
          <div class="form-group">
            <label for="project-key">Project Key (Optional)</label>
            <input
              id="project-key"
              v-model="newProject.key"
              type="text"
              placeholder="e.g., PROJ, FEAT"
              class="form-input"
              maxlength="10"
            />
            <p class="text-sm text-gray-500">Leave empty to auto-generate</p>
          </div>
          
          <div class="form-group">
            <label for="project-color">Color</label>
            <div class="color-picker">
              <input
                id="project-color"
                v-model="newProject.color"
                type="color"
                class="color-input"
              />
              <span class="color-value">{{ newProject.color }}</span>
            </div>
          </div>
          
          <div class="modal-actions">
            <button 
              type="button" 
              @click="showCreateProject = false"
              class="btn-secondary"
            >
              Cancel
            </button>
            <button type="submit" class="btn-primary" :disabled="isCreating">
              {{ isCreating ? 'Creating...' : 'Create Project' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch } from 'vue'
import Icon from '@/components/Icon.vue'
import { todoApi, type Project } from '@/services/todoApi'

interface Props {
  modelValue?: Project | null
}

interface Emits {
  (e: 'update:modelValue', project: Project | null): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const projects = ref<Project[]>([])
const currentProject = ref<Project | null>(props.modelValue || null)
const showProjectDropdown = ref(false)
const showCreateProject = ref(false)
const isCreating = ref(false)

const newProject = ref({
  name: '',
  description: '',
  key: '',
  color: '#3B82F6'
})

// Load projects on mount
onMounted(async () => {
  await loadProjects()
})

// Watch for external changes
watch(() => props.modelValue, (newValue) => {
  currentProject.value = newValue || null
})

// Load projects from API
const loadProjects = async () => {
  try {
    projects.value = await todoApi.getProjects()
    
    // If no current project is selected, select the first one
    if (!currentProject.value && projects.value.length > 0) {
      selectProject(projects.value[0])
    }
  } catch (error) {
    console.error('Failed to load projects:', error)
  }
}

// Select a project
const selectProject = (project: Project) => {
  currentProject.value = project
  emit('update:modelValue', project)
  showProjectDropdown.value = false
}

// Create a new project
const createProject = async () => {
  if (!newProject.value.name.trim()) return
  
  isCreating.value = true
  
  try {
    const project = await todoApi.createProject({
      name: newProject.value.name.trim(),
      description: newProject.value.description.trim() || undefined,
      key: newProject.value.key.trim() || undefined,
      color: newProject.value.color
    })
    
    // Add to projects list and select it
    projects.value.push(project)
    selectProject(project)
    
    // Reset form and close modal
    newProject.value = {
      name: '',
      description: '',
      key: '',
      color: '#3B82F6'
    }
    showCreateProject.value = false
    
  } catch (error) {
    console.error('Failed to create project:', error)
  } finally {
    isCreating.value = false
  }
}

// Close dropdown when clicking outside
const handleClickOutside = (event: Event) => {
  if (!(event.target as Element).closest('.project-selector')) {
    showProjectDropdown.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})

// Expose methods to parent component
defineExpose({
  loadProjects
})
</script>

<style scoped>
.project-selector {
  position: relative;
  display: inline-block;
}

.current-project {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.5rem 0.75rem;
  background-color: white;
  border: 1px solid #d1d5db;
  border-radius: 0.5rem;
  cursor: pointer;
  transition: all 0.2s;
  min-width: 200px;
}

.current-project:hover {
  border-color: #9ca3af;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
}

.project-info {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex: 1;
}

.project-color {
  width: 1rem;
  height: 1rem;
  border-radius: 50%;
  flex-shrink: 0;
}

.project-details {
  flex: 1;
  min-width: 0;
}

.project-name {
  font-weight: 500;
  color: #111827;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.project-key {
  font-size: 0.75rem;
  color: #6b7280;
  font-family: monospace;
}

.project-dropdown {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  margin-top: 0.25rem;
  background-color: white;
  border: 1px solid #d1d5db;
  border-radius: 0.5rem;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
  z-index: 50;
  min-width: 300px;
}

.dropdown-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem 1rem;
  border-bottom: 1px solid #e5e7eb;
}

.project-list {
  max-height: 300px;
  overflow-y: auto;
}

.project-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  cursor: pointer;
  transition: background-color 0.2s;
}

.project-item:hover {
  background-color: #f9fafb;
}

.project-item.active {
  background-color: #eff6ff;
}

.project-item .project-details {
  flex: 1;
  min-width: 0;
}

.project-item .project-stats {
  font-size: 0.75rem;
  color: #6b7280;
  margin-top: 0.125rem;
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 100;
}

.modal-content {
  background-color: white;
  border-radius: 0.5rem;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
  width: 100%;
  max-width: 500px;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem 1.5rem 1rem;
  border-bottom: 1px solid #e5e7eb;
}

.modal-body {
  padding: 1rem 1.5rem 1.5rem;
}

.form-group {
  margin-bottom: 1rem;
}

.form-group label {
  display: block;
  font-weight: 500;
  color: #374151;
  margin-bottom: 0.5rem;
}

.form-input,
.form-textarea {
  width: 100%;
  padding: 0.5rem 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  font-size: 0.875rem;
  transition: border-color 0.2s;
}

.form-input:focus,
.form-textarea:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.color-picker {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.color-input {
  width: 3rem;
  height: 2.5rem;
  border: none;
  border-radius: 0.375rem;
  cursor: pointer;
}

.color-value {
  font-family: monospace;
  font-size: 0.875rem;
  color: #6b7280;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  margin-top: 1.5rem;
}

.btn-primary,
.btn-secondary {
  padding: 0.5rem 1rem;
  border-radius: 0.375rem;
  font-weight: 500;
  transition: all 0.2s;
  cursor: pointer;
}

.btn-primary {
  background-color: #3b82f6;
  color: white;
  border: none;
}

.btn-primary:hover:not(:disabled) {
  background-color: #2563eb;
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-secondary {
  background-color: white;
  color: #374151;
  border: 1px solid #d1d5db;
}

.btn-secondary:hover {
  background-color: #f9fafb;
  border-color: #9ca3af;
}
</style>
