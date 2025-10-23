<template>
  <div 
    class="h-full flex flex-col"
    :class="{ 'pb-24': isSelectMode && hasSelection }"
  >
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
          <div class="text-gray-600 dark:text-gray-400">
            <span v-if="!currentProject">Select a project to manage todos</span>
            <div v-else class="flex items-center gap-2">
              <span>Project: {{ currentProject.key }} - </span>
              <div v-if="!editingProjectDescription" class="flex items-center gap-2">
                <span>{{ currentProject.description || 'No description' }}</span>
                <button
                  @click="startEditProjectDescription"
                  class="p-1 text-gray-400 hover:text-blue-500 transition-colors"
                  title="Edit project description"
                >
                  <Icon name="Edit3" class="w-4 h-4" />
                </button>
              </div>
              <div v-else class="flex items-center gap-2">
                <input
                  v-model="editingProjectDescriptionText"
                  @keydown.enter="saveProjectDescription"
                  @keydown.escape="cancelEditProjectDescription"
                  @blur="saveProjectDescription"
                  class="px-2 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                  placeholder="Enter project description"
                  ref="projectDescriptionInput"
                />
                <button
                  @click="saveProjectDescription"
                  class="p-1 text-green-500 hover:text-green-600 transition-colors"
                  title="Save"
                >
                  <Icon name="Check" class="w-4 h-4" />
                </button>
                <button
                  @click="cancelEditProjectDescription"
                  class="p-1 text-gray-400 hover:text-red-500 transition-colors"
                  title="Cancel"
                >
                  <Icon name="X" class="w-4 h-4" />
                </button>
              </div>
            </div>
          </div>
          
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
        
        <!-- Mobile Layout: Stacked rows -->
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:gap-3">
          <!-- Project Select Dropdown - Full width on mobile -->
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
              class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 w-full md:min-w-[200px] cursor-pointer"
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
          
          <!-- Action Buttons Row - Full width on mobile -->
          <div class="flex items-center gap-3">
            <!-- Add Todo Button - Full width on mobile, auto width on desktop -->
            <button
              @click="handleShowForm"
              :disabled="!currentProject"
              class="flex-1 md:flex-none inline-flex items-center justify-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:ring-offset-2 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <Icon name="Plus" class="w-4 h-4" />
              <span class="hidden sm:inline">Add Todo</span>
              <span class="sm:hidden">Add</span>
            </button>

            <!-- Filters Button -->
            <button
              @click="showFilters = !showFilters"
              :title="showFilters ? 'Hide Filters' : 'Show Filters'"
              :class="[
                'inline-flex items-center justify-center w-10 h-10 rounded-lg border transition-colors',
                showFilters 
                  ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 border-blue-300 dark:border-blue-600' 
                  : 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-100 border-gray-300 dark:border-gray-600 hover:bg-gray-200 dark:hover:bg-gray-600'
              ]"
            >
              <Icon name="Settings" class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>

      <!-- Search and Filters -->
      <div v-if="showFilters" class="mt-4 space-y-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
        <!-- Search Bar -->
        <div class="flex-1 relative">
          <Icon name="Search" class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search todos..."
            class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white text-gray-900"
          />
        </div>
        
        
        <!-- Filter Dropdowns - Mobile: Stacked, Desktop: Row -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
          <!-- Priority Filter -->
          <div>
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Priority Filter</label>
            <select
              v-model="priorityFilter"
              class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
            >
              <option value="">All Priorities</option>
              <option value="Low">Low</option>
              <option value="Medium">Medium</option>
              <option value="High">High</option>
              <option value="Critical">Critical</option>
            </select>
          </div>
          
          <!-- Type Filter -->
          <div>
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Type Filter</label>
            <TypeFilter v-model="typeFilter" />
          </div>
          
          <!-- Assignee Filter -->
          <div>
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Assignee Filter</label>
            <select
              v-model="assigneeFilter"
              class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
            >
              <option value="">All Assignees</option>
              <option v-for="assignee in uniqueAssignees" :key="assignee" :value="assignee">
                {{ assignee }}
              </option>
            </select>
          </div>
        </div>

        <!-- Save View and Saved Views - Mobile: Stacked, Desktop: Row -->
        <div class="flex flex-col sm:flex-row gap-3">
          <!-- Save View Button -->
          <button
            type="button"
            @click="showSaveViewModal = true"
            class="flex-1 sm:flex-none px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors"
            title="Save current filters as a view"
          >
            <Icon name="Save" class="w-4 h-4 inline mr-2" />
            Save View
          </button>

          <!-- Select for Bulk Update Button -->
          <button
            @click="toggleSelectMode"
            :class="[
              'flex-1 sm:flex-none flex items-center justify-center gap-2 px-4 py-2 text-sm rounded-lg border transition-colors',
              isSelectMode 
                ? 'bg-blue-100 border-blue-300 text-blue-700 dark:bg-blue-900/20 dark:border-blue-700 dark:text-blue-300' 
                : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700'
            ]"
          >
            <Icon name="CheckSquare" class="w-4 h-4" />
            {{ isSelectMode ? 'Exit Select' : 'Select for Bulk Update' }}
          </button>

          <!-- Saved Views Dropdown - Only show if there are saved views -->
          <div v-if="savedViews.length > 0" class="flex flex-col sm:flex-row gap-2">
            <select
              v-model="selectedSavedViewName"
              @change="onApplySavedView"
              class="flex-1 px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
            >
              <option value="">Saved Views</option>
              <option v-for="view in savedViews" :key="view.name" :value="view.name">{{ view.name }}</option>
            </select>

            <button
              v-if="selectedSavedViewName"
              type="button"
              @click="deleteSavedView"
              class="px-3 py-2 text-sm bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded-lg border border-red-200 dark:border-red-700 hover:bg-red-100 dark:hover:bg-red-900/50"
              title="Delete selected view"
            >
              <Icon name="Trash2" class="w-4 h-4 inline mr-1" />
              Delete
            </button>
          </div>
        </div>
      </div>
      
      <!-- Filter Status Indicator -->
      <div v-if="hasActiveFilters" class="mt-3 flex items-center justify-between bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg px-4 py-2">
        <div class="flex items-center gap-2 text-sm text-blue-700 dark:text-blue-300">
          <Icon name="Filter" class="w-4 h-4" />
          <span>Filters have been applied</span>
        </div>
        <button
          @click="clearAllFilters"
          class="flex items-center gap-1 text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-200 transition-colors"
        >
          <Icon name="X" class="w-4 h-4" />
          <span>Clear filters</span>
        </button>
      </div>
    </div>

    <!-- Statistics / Calendar -->
    <div v-if="props.showCalendar" class="mb-4">
      <CalendarView :todos="todos" @edit-todo="handleEditTodoFromCalendar" @add-todo="handleAddTodoFromCalendar" />
    </div>
    <TodoStats v-else :todos="todos" />

    <!-- Bulk Operations Bar -->
    <BulkOperationsBar
      v-if="isSelectMode"
      :available-assignees="uniqueAssignees"
      @bulk-status-change="handleBulkStatusChange"
      @bulk-priority-change="handleBulkPriorityChange"
      @bulk-assignee-change="handleBulkAssigneeChange"
      @bulk-type-change="handleBulkTypeChange"
      @bulk-due-date-change="handleBulkDueDateChange"
      @bulk-tags-change="handleBulkTagsChange"
      @bulk-delete="handleBulkDelete"
      @clear-selection="clearSelection"
      @toggle-select-mode="toggleSelectMode"
      @refresh="loadTodos"
    />
    

    <!-- Kanban Board -->
    <div class="flex-1 flex gap-3 overflow-x-auto pb-2">
      <TodoColumn
        title="To Do"
        status="todo"
        :todos="filteredTodos.todo"
        :show-add-button="true"
        :current-project-id="currentProject?.id || null"
        :current-project-color="currentProject?.color || null"
        :is-select-mode="isSelectMode"
        :selected-items="selectedItems"
        @add="handleShowForm"
        @edit="editTodo"
        @delete="deleteTodo"
        @update="updateTodo"
        @drop="handleDrop"
        @reorder="handleReorder"
        @menu="() => {}"
        @add-subtask="handleAddSubtask"
        @toggle-selection="toggleSelection"
      />
      
      <TodoColumn
        title="In Progress"
        status="in-progress"
        :todos="filteredTodos.inProgress"
        :current-project-id="currentProject?.id || null"
        :current-project-color="currentProject?.color || null"
        :is-select-mode="isSelectMode"
        :selected-items="selectedItems"
        @edit="editTodo"
        @delete="deleteTodo"
        @update="updateTodo"
        @drop="handleDrop"
        @reorder="handleReorder"
        @menu="() => {}"
        @add-subtask="handleAddSubtask"
        @toggle-selection="toggleSelection"
      />
      
      <TodoColumn
        title="Q&A / Testing"
        status="qa-testing"
        :todos="filteredTodos.qaTesting"
        :current-project-id="currentProject?.id || null"
        :current-project-color="currentProject?.color || null"
        :is-select-mode="isSelectMode"
        :selected-items="selectedItems"
        @edit="editTodo"
        @delete="deleteTodo"
        @update="updateTodo"
        @drop="handleDrop"
        @reorder="handleReorder"
        @menu="() => {}"
        @add-subtask="handleAddSubtask"
        @toggle-selection="toggleSelection"
      />
      
      <TodoColumn
        title="Done"
        status="done"
        :todos="filteredTodos.done"
        :current-project-id="currentProject?.id || null"
        :current-project-color="currentProject?.color || null"
        :is-select-mode="isSelectMode"
        :selected-items="selectedItems"
        @edit="editTodo"
        @delete="deleteTodo"
        @update="updateTodo"
        @drop="handleDrop"
        @reorder="handleReorder"
        @menu="() => {}"
        @add-subtask="handleAddSubtask"
        @toggle-selection="toggleSelection"
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
    <div v-if="showCreateProject" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click="showCreateProject = false">
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full" @click.stop>
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
                Client (Optional)
              </label>
              <select
                v-model="newProject.client_id"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
              >
                <option :value="null">No Client</option>
                <option 
                  v-for="client in clients" 
                  :key="client.id" 
                  :value="client.id"
                >
                  {{ client.name }}
                </option>
              </select>
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
    <div v-if="showEditProject" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click="showEditProject = false">
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full" @click.stop>
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
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Client (Optional)
              </label>
              <select
                v-model="editingProjectClientId"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
              >
                <option :value="null">No Client</option>
                <option 
                  v-for="client in clients" 
                  :key="client.id" 
                  :value="client.id"
                >
                  {{ client.name }}
                </option>
              </select>
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

    <!-- Save View Modal -->
    <div v-if="showSaveViewModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" @click="showSaveViewModal = false">
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full mx-4" @click.stop>
        <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Save Current View</h3>
          <button
            @click="showSaveViewModal = false"
            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
          >
            <Icon name="X" class="w-6 h-6" />
          </button>
        </div>
        
        <div class="p-6">
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              View Name
            </label>
            <input
              v-model="newSavedViewName"
              type="text"
              placeholder="Enter view name"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
              @keyup.enter="saveCurrentAsView"
            />
          </div>
          
          <div class="flex gap-3">
            <button
              type="button"
              @click="saveCurrentAsView"
              :disabled="!newSavedViewName.trim()"
              class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Save View
            </button>
            <button
              type="button"
              @click="showSaveViewModal = false"
              class="flex-1 px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors"
            >
              Cancel
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Keyboard Shortcuts Help Modal -->
    <KeyboardShortcutsHelp
      v-if="showKeyboardHelp"
      :show-help="showKeyboardHelp"
      :shortcuts-by-category="getShortcutsByCategory()"
      @close="showKeyboardHelp = false"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue';
import Icon from '@/components/Icon.vue';
import TodoColumn from './TodoColumn.vue';
import TodoForm from './TodoForm.vue';
import TodoStats from './TodoStats.vue';
import TypeFilter from './TypeFilter.vue';
import CalendarView from './CalendarView.vue';
import BulkOperationsBar from './BulkOperationsBar.vue';
import KeyboardShortcutsHelp from './KeyboardShortcutsHelp.vue';
import { todoApi, type Project, type Todo } from '@/services/todoApi';
import { realtimeService } from '@/services/realtimeService';
import { deleteImagesInHtml } from '@/services/supabaseClient';
import { useAnalytics } from '../composables/useAnalytics';
import { useKeyboardShortcuts } from '../composables/useKeyboardShortcuts';
import { useBulkOperations } from '../composables/useBulkOperations';

// Define props
const props = defineProps<{
  showActivityFeed?: boolean;
  showCalendar?: boolean;
}>();

// Define emits
const emit = defineEmits<{
  'project-changed': [project: any];
  'toggle-activity-feed': [];
  'toggle-calendar': [];
}>();

const todos = ref<Todo[]>([]);
const projects = ref<Project[]>([]);
const showForm = ref(false);
const showCreateProject = ref(false);
const showEditProject = ref(false);
// showCalendar is now a prop
const showFilters = ref(false);
const showSaveViewModal = ref(false);
const showKeyboardHelp = ref(false);
const { trackTodoEvent, trackProjectEvent } = useAnalytics();

// Initialize keyboard shortcuts and bulk operations
const { registerShortcut, getShortcutsByCategory } = useKeyboardShortcuts();
const { 
  isSelectMode, 
  selectedItems, 
  selectedCount, 
  hasSelection,
  toggleSelectMode,
  clearSelection,
  toggleSelection: toggleSelectionById,
  isSelected
} = useBulkOperations();

// Wrapper function to handle todo object
const toggleSelection = (todo: Todo) => {
  toggleSelectionById(todo.id);
};

const currentProject = ref<Project | null>(null);

// Watch for project changes and emit to parent
watch(currentProject, (newProject) => {
  emit('project-changed', newProject);
}, { immediate: true });
const selectedProjectId = ref<string>('');
const editingTodo = ref<Todo | null>(null);
const editingProjectName = ref('');
const editingProjectClientId = ref<number | null>(null);
const editingProjectDescription = ref(false);
const editingProjectDescriptionText = ref('');
const projectDescriptionInput = ref<HTMLInputElement | null>(null);
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
  newSavedViewName.value = '';
  showSaveViewModal.value = false;
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
  color: '#3B82F6',
  client_id: null as number | null
});

const clients = ref<any[]>([]);
const isUpdatingProject = ref(false); // Flag to prevent circular events
let unsubscribeFromTodos: (() => void) | null = null;

// Computed properties
const filteredTodos = computed(() => {
  const filtered = todos.value.filter(todo => {
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
    todo: filtered.filter(t => t.status === 'todo').sort((a, b) => (a.order || 0) - (b.order || 0)),
    inProgress: filtered.filter(t => t.status === 'in-progress').sort((a, b) => (a.order || 0) - (b.order || 0)),
    qaTesting: filtered.filter(t => t.status === 'qa-testing').sort((a, b) => (a.order || 0) - (b.order || 0)),
    done: filtered.filter(t => t.status === 'done').sort((a, b) => (a.order || 0) - (b.order || 0))
  };
});

const uniqueAssignees = computed(() => {
  const assignees = todos.value
    .map(todo => todo.assignee)
    .filter(Boolean) as string[];
  return [...new Set(assignees)];
});

// Check if any filters are currently applied
const hasActiveFilters = computed(() => {
  return searchQuery.value.trim() !== '' || 
         priorityFilter.value !== '' || 
         typeFilter.value !== '' || 
         assigneeFilter.value !== '';
});

// Safe projects array to prevent undefined errors
const safeProjects = computed(() => {
  return projects.value || [];
});

// Methods
const editTodo = async (todo: Todo) => {
  // If marked as newly assigned, clear the badge by calling the detail API (which marks seen)
  try {
    if (todo.is_new_assigned) {
      await todoApi.getTodo(todo.id);
      // Optimistically clear flag locally
      const idx = todos.value.findIndex(t => t.id === todo.id);
      if (idx !== -1) todos.value[idx].is_new_assigned = false;
    }
  } catch {}
  editingTodo.value = { ...todo };
  showForm.value = true;
};

const handleEditTodoFromCalendar = (todo: Todo) => {
  editingTodo.value = { ...todo };
  showForm.value = true;
};

const handleAddTodoFromCalendar = (date: Date) => {
  // Format date as YYYY-MM-DD for the form, avoiding timezone issues
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  const formattedDate = `${year}-${month}-${day}`;
  
  // Set up form for creating a new todo with preselected date
  editingTodo.value = {
    id: 0,
    user_id: 0,
    project_id: currentProject.value?.id || 0,
    company_id: 0,
    title: '',
    description: '',
    status: 'todo',
    priority: 'Medium',
    type: 'Task',
    assignee: '',
    due_date: formattedDate,
    tags: [],
    parent_task_id: null,
    created_at: '',
    updated_at: '',
    project: currentProject.value || null,
    comments: [],
    attachments: [],
    subtasks: []
  };
  showForm.value = true;
};

const handleAddSubtask = (parentTodo: Todo) => {
  // Set up form for creating a subtask
  editingTodo.value = {
    id: 0,
    user_id: 0,
    project_id: parentTodo.project_id,
    parent_task_id: parentTodo.id,
    title: '',
    description: '',
    priority: parentTodo.priority,
    type: parentTodo.type,
    tags: [],
    assignee: parentTodo.assignee,
    due_date: '',
    story_points: 1,
    status: 'todo',
    created_at: '',
    updated_at: '',
    subtasks: [],
  } as Todo;
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
    
    if (editingTodo.value && editingTodo.value.id > 0) {
      // Update existing todo
      const updatedTodo = await todoApi.updateTodo(todo.id, todo);
      const index = todos.value.findIndex(t => t.id === todo.id);
      if (index !== -1) {
        todos.value[index] = updatedTodo;
        
        // Force reactivity update to ensure UI changes immediately
        todos.value = [...todos.value];
      } else {
        // Check if it's a subtask - find the parent and update the subtask
        for (let i = 0; i < todos.value.length; i++) {
          if (todos.value[i].subtasks) {
            const subtaskIndex = todos.value[i].subtasks!.findIndex(subtask => subtask.id === todo.id);
            if (subtaskIndex !== -1) {
              todos.value[i].subtasks![subtaskIndex] = updatedTodo;
              
              // Force reactivity update
              todos.value = [...todos.value];
              break;
            }
          }
        }
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
      // Add new todo or subtask
      let newTodo;
      if (todo.parent_task_id) {
        // Create subtask
        newTodo = await todoApi.createSubtask(todo.parent_task_id, {
          ...todo,
          project_id: currentProject.value.id
        });
        
        // Add subtask locally as fallback since realtime isn't working
        const parentIndex = todos.value.findIndex(t => t.id === todo.parent_task_id);
        if (parentIndex !== -1) {
          if (!todos.value[parentIndex].subtasks) {
            todos.value[parentIndex].subtasks = [];
          }
          todos.value[parentIndex].subtasks!.push(newTodo);
          // Force reactivity update
          todos.value = [...todos.value];
        }
      } else {
        // Create regular todo
        newTodo = await todoApi.createTodo({
          ...todo,
          project_id: currentProject.value.id
        });
        
        // Add todo locally as fallback since realtime isn't working
        todos.value.push(newTodo);
        // Force reactivity update
        todos.value = [...todos.value];
      }
      
      if ((window as any).$notify) {
        (window as any).$notify({
          type: 'success',
          title: todo.parent_task_id ? 'Subtask Created' : 'Todo Created',
          message: `${todo.parent_task_id ? 'Subtask' : 'Todo'} "${newTodo.title}" has been created successfully.`
        });
      }
    }
    showForm.value = false;
    
    // Dispatch event to refresh sidebar projects
    window.dispatchEvent(new CustomEvent('projectCreated'));
    
    // Dispatch event to refresh sidebar project stats
    window.dispatchEvent(new CustomEvent('todoChanged'));
  } catch (error) {
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
    
    // Track todo deletion event
    trackTodoEvent('deleted', {
      todo_id: id,
      project_id: currentProject.value?.id,
      project_name: currentProject.value?.name,
      priority: toDelete?.priority,
      type: toDelete?.type,
      status: toDelete?.status,
    });
    
    // Attempt to cleanup images from Supabase storage
    if (toDelete?.description) {
      deleteImagesInHtml(toDelete.description).catch(() => {});
    }
    
    // Remove todo locally as fallback since realtime isn't working
    todos.value = todos.value.filter(t => t.id !== id);
    // Force reactivity update
    todos.value = [...todos.value];
    
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
    const updatedTodo = await todoApi.updateTodoStatus(todo.id, newStatus as 'todo' | 'in-progress' | 'qa-testing' | 'done');
    
    // Track todo status change event
    trackTodoEvent('status_changed', {
      todo_id: todo.id,
      project_id: currentProject.value.id,
      project_name: currentProject.value.name,
      old_status: todo.status,
      new_status: newStatus,
      priority: todo.priority,
      type: todo.type,
    });
    
    // Find the todo by ID, ensuring type consistency
    const index = todos.value.findIndex(t => String(t.id) === String(todo.id));
    
    if (index !== -1) {
      todos.value[index] = updatedTodo;
      
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
      // Reload todos from API as fallback
      await loadTodos();
    }
  } catch (error) {
    if ((window as any).$notify) {
      (window as any).$notify({
        type: 'error',
        title: 'Update Failed',
        message: 'Failed to update todo status. Please try again.'
      });
    }
  }
};

const handleReorder = async (draggedTodo: Todo, targetTodo: Todo, position: 'before' | 'after') => {
  
  // Validate that both todos belong to the current project
  if (!currentProject.value) {
    if ((window as any).$notify) {
      (window as any).$notify({
        type: 'error',
        title: 'Project Required',
        message: 'Please select a project before reordering todos.'
      });
    }
    return;
  }
  
  if (draggedTodo.project_id !== currentProject.value.id || targetTodo.project_id !== currentProject.value.id) {
    if ((window as any).$notify) {
      (window as any).$notify({
        type: 'error',
        title: 'Invalid Reorder',
        message: 'Cannot reorder todos from different projects.'
      });
    }
    return;
  }
  
  try {
    // Get the todos for the same status and project
    const statusTodos = todos.value
      .filter(t => t.status === draggedTodo.status && t.project_id === currentProject.value.id)
      .sort((a, b) => (a.order || 0) - (b.order || 0));
    
    // Find current positions
    const draggedIndex = statusTodos.findIndex(t => String(t.id) === String(draggedTodo.id));
    const targetIndex = statusTodos.findIndex(t => String(t.id) === String(targetTodo.id));
    
    if (draggedIndex === -1 || targetIndex === -1) {
      return;
    }
    
    // Calculate new order
    let newOrder: number;
    if (position === 'before') {
      if (targetIndex === 0) {
        // Insert at the beginning
        newOrder = Math.max(1, (statusTodos[0].order || 1) - 1);
      } else {
        // Insert between target-1 and target
        const prevOrder = statusTodos[targetIndex - 1].order || targetIndex;
        const targetOrder = statusTodos[targetIndex].order || targetIndex + 1;
        newOrder = Math.floor((prevOrder + targetOrder) / 2);
        if (newOrder === prevOrder) {
          // If we can't fit between, reassign all orders
          newOrder = targetIndex;
        }
      }
    } else {
      // position === 'after'
      if (targetIndex === statusTodos.length - 1) {
        // Insert at the end
        newOrder = (statusTodos[statusTodos.length - 1].order || statusTodos.length) + 1;
      } else {
        // Insert between target and target+1
        const targetOrder = statusTodos[targetIndex].order || targetIndex + 1;
        const nextOrder = statusTodos[targetIndex + 1].order || targetIndex + 2;
        newOrder = Math.floor((targetOrder + nextOrder) / 2);
        if (newOrder === targetOrder) {
          // If we can't fit between, reassign all orders
          newOrder = targetIndex + 2;
        }
      }
    }
    
    // If we need to reassign orders (when we can't fit between existing orders)
    if (newOrder <= 0 || statusTodos.some(t => t.order === newOrder && String(t.id) !== String(draggedTodo.id))) {
      // Reassign all orders to make space
      const reorderedTodos = [...statusTodos];
      const [removed] = reorderedTodos.splice(draggedIndex, 1);
      const insertIndex = position === 'before' ? targetIndex : targetIndex + 1;
      reorderedTodos.splice(insertIndex > draggedIndex ? insertIndex - 1 : insertIndex, 0, removed);
      
      const todoOrders = reorderedTodos.map((todo, index) => ({
        id: todo.id,
        order: index + 1
      }));
      
      await todoApi.updateTodoOrder(todoOrders);
      
      // Update local state
      todoOrders.forEach(({ id, order }) => {
        const index = todos.value.findIndex(t => String(t.id) === String(id));
        if (index !== -1) {
          todos.value[index].order = order;
        }
      });
    } else {
      // Simple case: just update the dragged todo's order
      await todoApi.updateTodoOrder([{ id: draggedTodo.id, order: newOrder }]);
      
      // Update local state
      const index = todos.value.findIndex(t => String(t.id) === String(draggedTodo.id));
      if (index !== -1) {
        todos.value[index].order = newOrder;
      }
    }
    
    if ((window as any).$notify) {
      (window as any).$notify({
        type: 'success',
        title: 'Todo Reordered',
        message: `"${draggedTodo.title}" has been reordered successfully.`
      });
    }
    
  } catch (error) {
    if ((window as any).$notify) {
      (window as any).$notify({
        type: 'error',
        title: 'Reorder Failed',
        message: 'Failed to reorder todo. Please try again.'
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
  editingProjectClientId.value = currentProject.value.client_id || null;
  showEditProject.value = true;
};

const saveEditProject = async () => {
  if (!currentProject.value || !editingProjectName.value.trim()) return;
  
  try {
    const updatedProject = await todoApi.updateProject(currentProject.value.id, {
      name: editingProjectName.value.trim(),
      client_id: editingProjectClientId.value
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
    editingProjectClientId.value = null;
    
    // Dispatch event to refresh sidebar projects
    window.dispatchEvent(new CustomEvent('todoChanged'));
    
  } catch (error) {
    if ((window as any).$notify) {
      (window as any).$notify({
        type: 'error',
        title: 'Update Failed',
        message: 'Failed to update project. Please try again.'
      });
    }
  }
};

const startEditProjectDescription = () => {
  editingProjectDescription.value = true;
  editingProjectDescriptionText.value = currentProject.value?.description || '';
  nextTick(() => {
    projectDescriptionInput.value?.focus();
  });
};

const saveProjectDescription = async () => {
  if (!currentProject.value) return;
  
  try {
    const updatedProject = await todoApi.updateProject(currentProject.value.id, {
      description: editingProjectDescriptionText.value.trim()
    });
    
    // Update current project
    currentProject.value = { ...currentProject.value, ...updatedProject };
    
    // Show success notification
    if ((window as any).$notify) {
      (window as any).$notify({
        type: 'success',
        title: 'Project Updated',
        message: 'Project description has been updated successfully.'
      });
    }
    
    // Exit edit mode
    editingProjectDescription.value = false;
    editingProjectDescriptionText.value = '';
    
    // Dispatch event to refresh sidebar projects
    window.dispatchEvent(new CustomEvent('todoChanged'));
    
  } catch (error) {
    if ((window as any).$notify) {
      (window as any).$notify({
        type: 'error',
        title: 'Update Failed',
        message: 'Failed to update project description. Please try again.'
      });
    }
  }
};

const cancelEditProjectDescription = () => {
  editingProjectDescription.value = false;
  editingProjectDescriptionText.value = '';
};



const updateTodo = (updatedTodo: Todo) => {
  // Update the todo in the local array
  const index = todos.value.findIndex(todo => todo.id === updatedTodo.id);
  if (index !== -1) {
    // Preserve the project relationship from the existing todo
    const existingTodo = todos.value[index];
    const todoWithProject = {
      ...updatedTodo,
      project: existingTodo.project || currentProject.value
    };
    todos.value[index] = todoWithProject;
    
    // Force reactivity update to ensure UI changes immediately
    todos.value = [...todos.value];
    
    // Dispatch event to refresh sidebar project stats
    window.dispatchEvent(new CustomEvent('todoChanged'));
  } else {
    // Check if it's a subtask - find the parent and update the subtask
    for (let i = 0; i < todos.value.length; i++) {
      if (todos.value[i].subtasks) {
        const subtaskIndex = todos.value[i].subtasks!.findIndex(subtask => subtask.id === updatedTodo.id);
        if (subtaskIndex !== -1) {
          // Preserve the project relationship for subtasks too
          const existingSubtask = todos.value[i].subtasks![subtaskIndex];
          const subtaskWithProject = {
            ...updatedTodo,
            project: existingSubtask.project || currentProject.value
          };
          todos.value[i].subtasks![subtaskIndex] = subtaskWithProject;
          
          // Force reactivity update
          todos.value = [...todos.value];
          
          // Dispatch event to refresh sidebar project stats
          window.dispatchEvent(new CustomEvent('todoChanged'));
          break;
        }
      }
    }
  }
};

const clearAllFilters = () => {
  searchQuery.value = '';
  priorityFilter.value = '';
  typeFilter.value = '';
  assigneeFilter.value = '';
  selectedSavedViewName.value = '';
};

const resetNewProject = () => {
  newProject.value = {
    name: '',
    description: '',
    key: '',
    color: '#3B82F6',
    client_id: null
  };
};

const loadClients = async () => {
  try {
    const response = await fetch('/api/clients', {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    });
    
    if (response.ok) {
      const data = await response.json();
      clients.value = data.data || [];
    }
  } catch (error) {
    clients.value = [];
  }
};

const createProject = async () => {
  try {
    const projectData = {
      name: newProject.value.name,
      description: newProject.value.description || undefined,
      key: newProject.value.key || undefined,
      color: newProject.value.color,
      client_id: newProject.value.client_id || undefined
    };
    
    const newProjectCreated = await todoApi.createProject(projectData);
    
    // Track project creation event
    trackProjectEvent('created', {
      project_id: newProjectCreated.id,
      project_name: newProjectCreated.name,
      has_description: !!newProjectCreated.description,
      has_custom_key: !!newProjectCreated.key,
      color: newProjectCreated.color,
    });
    
    // Set as current project
    currentProject.value = newProjectCreated;
    selectedProjectId.value = newProjectCreated.id.toString();
    localStorage.setItem('currentProjectId', newProjectCreated.id.toString());
    
    // Close modal and reset form
    showCreateProject.value = false;
    resetNewProject();
    
    // Refresh projects list to include the new project
    await loadProjects();
    
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
    
  } catch (error) {
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
    
    // Try to load projects with stats first
    try {
      const response = await todoApi.getProjectsWithStats();
      
      // Check if response has the expected structure
      if (response && Array.isArray(response)) {
        projects.value = response;
      } else if (response && response.data && Array.isArray(response.data)) {
        projects.value = response.data;
      } else {
        throw new Error('Invalid response structure');
      }
    } catch (statsError) {
      
      // Fallback to regular getProjects
      const response = await todoApi.getProjects();
      
      if (response && Array.isArray(response)) {
        projects.value = response;
      } else if (response && response.data && Array.isArray(response.data)) {
        projects.value = response.data;
      } else {
        projects.value = [];
      }
    }
    
    // Set selected project ID if current project exists
    if (currentProject.value) {
      selectedProjectId.value = currentProject.value.id.toString();
    } else if (projects.value.length > 0 && !localStorage.getItem('currentProjectId')) {
      // If no current project is set and projects are available, auto-select the first one
      const firstProject = projects.value[0];
      currentProject.value = firstProject;
      selectedProjectId.value = firstProject.id.toString();
      localStorage.setItem('currentProjectId', firstProject.id.toString());
      
    }
  } catch (error) {
    projects.value = [];
  }
};



// Handle project selection change (keeping for backward compatibility)
const onProjectChange = async () => {
  
  if (!selectedProjectId.value) {
    currentProject.value = null;
    localStorage.removeItem('currentProjectId');
    todos.value = [];
    return;
  }
  
  // Prevent circular events
  if (isUpdatingProject.value) {
    return;
  }
  
  try {
    isUpdatingProject.value = true;
    
    const projectId = parseInt(selectedProjectId.value);
    const project = await todoApi.getProject(projectId);
    
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

// Register keyboard shortcuts
const registerKeyboardShortcuts = () => {
  // New todo shortcut (using Cmd+Shift+N to avoid browser conflict)
  registerShortcut({
    key: 'n',
    ctrlKey: true,
    shiftKey: true,
    action: () => {
      handleShowForm();
    },
    description: 'Create new todo',
    category: 'Actions'
  });

  // Search shortcut (using Cmd+Shift+F to avoid browser conflict)
  registerShortcut({
    key: 'f',
    ctrlKey: true,
    shiftKey: true,
    action: () => {
      const searchInput = document.querySelector('input[placeholder*="Search"]') as HTMLInputElement;
      if (searchInput) {
        searchInput.focus();
      }
    },
    description: 'Focus search',
    category: 'Navigation'
  });

  // Save shortcut (using Cmd+Shift+S to avoid browser conflict)
  registerShortcut({
    key: 's',
    ctrlKey: true,
    shiftKey: true,
    action: () => {
      // This would save any pending changes
    },
    description: 'Save changes',
    category: 'Actions'
  });

  // Help shortcut
  registerShortcut({
    key: '?',
    action: () => {
      showKeyboardHelp.value = !showKeyboardHelp.value;
    },
    description: 'Show keyboard shortcuts',
    category: 'Help'
  });

  // Toggle select mode (using Cmd+Shift+A to avoid browser conflict)
  registerShortcut({
    key: 'a',
    ctrlKey: true,
    shiftKey: true,
    action: () => {
      toggleSelectMode();
    },
    description: 'Toggle select mode',
    category: 'Bulk Operations'
  });

  // Escape to exit select mode
  registerShortcut({
    key: 'Escape',
    action: () => {
      if (isSelectMode.value) {
        toggleSelectMode();
      }
    },
    description: 'Exit select mode',
    category: 'Bulk Operations'
  });
};

// Bulk operations methods
const handleBulkStatusChange = async (status: string) => {
  const selectedIds = Array.from(selectedItems.value);
  try {
    await todoApi.bulkUpdateStatus(selectedIds, status);
    await loadTodos();
    clearSelection();
  } catch (error) {
  }
};

const handleBulkPriorityChange = async (priority: string) => {
  const selectedIds = Array.from(selectedItems.value);
  try {
    await todoApi.bulkUpdatePriority(selectedIds, priority);
    await loadTodos();
    clearSelection();
  } catch (error) {
  }
};

const handleBulkAssigneeChange = async (assignee: string | null) => {
  const selectedIds = Array.from(selectedItems.value);
  try {
    await todoApi.bulkUpdateAssignee(selectedIds, assignee);
    await loadTodos();
    clearSelection();
  } catch (error) {
  }
};

const handleBulkTypeChange = async (type: string) => {
  const selectedIds = Array.from(selectedItems.value);
  try {
    await todoApi.bulkUpdateType(selectedIds, type);
    await loadTodos();
    clearSelection();
  } catch (error) {
  }
};

const handleBulkDueDateChange = async (dueDate: string) => {
  const selectedIds = Array.from(selectedItems.value);
  try {
    await todoApi.bulkUpdateDueDate(selectedIds, dueDate);
    await loadTodos();
    clearSelection();
  } catch (error) {
  }
};

const handleBulkTagsChange = async (tags: string[]) => {
  const selectedIds = Array.from(selectedItems.value);
  try {
    await todoApi.bulkUpdateTags(selectedIds, tags);
    await loadTodos();
    clearSelection();
  } catch (error) {
  }
};

const handleBulkDelete = async () => {
  const selectedIds = Array.from(selectedItems.value);
  const confirmed = confirm(`Are you sure you want to delete ${selectedIds.length} todo(s)?`);
  if (confirmed) {
    try {
      await todoApi.bulkDelete(selectedIds);
      await loadTodos();
      clearSelection();
    } catch (error) {
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
      
      // Don't dispatch event here to prevent circular loop
      // The sidebar will be updated when the component mounts
    } else if (projects.value.length > 0) {
      // If no project is selected and projects are available, select the first one
      const firstProject = projects.value[0];
      currentProject.value = firstProject;
      selectedProjectId.value = firstProject.id.toString();
      localStorage.setItem('currentProjectId', firstProject.id.toString());
      
    }
  } catch (error) {
    localStorage.removeItem('currentProjectId');
    
    // If there was an error but projects are available, try to select the first one
    if (projects.value.length > 0) {
      const firstProject = projects.value[0];
      currentProject.value = firstProject;
      selectedProjectId.value = firstProject.id.toString();
      localStorage.setItem('currentProjectId', firstProject.id.toString());
      
    }
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
    
    // Check specific todos
    const allTodos = response.data.todo.concat(response.data['in-progress']).concat(response.data['qa-testing']).concat(response.data.done);
    const subtasks = allTodos.filter(t => t.parent_task_id !== null);
    const parentTasks = allTodos.filter(t => t.parent_task_id === null);
    
    todos.value = allTodos;
    

  } catch (error) {
  }
};

// Watch for project changes and reload todos
watch(currentProject, async (newProject, oldProject) => {
  // Only reload todos if the project actually changed (not just set for the first time)
  if (newProject && oldProject && newProject.id !== oldProject.id) {
    selectedProjectId.value = newProject.id.toString();
    await loadTodos();
  }
});

// Watch for project creation modal and reset form when opened
watch(showCreateProject, (newValue) => {
  if (newValue) {
    resetNewProject();
  }
});

// Single onMounted hook to handle all initialization
onMounted(async () => {
  // Register keyboard shortcuts
  registerKeyboardShortcuts();
  
  // Load projects first (so dropdown has options)
  await loadProjects();
  
  // Load clients for project creation
  await loadClients();
  
  // Then load current project from localStorage
  await loadCurrentProject();
  
  // Finally load todos for the current project
  await loadTodos();
  
  // Subscribe to real-time todo updates
  
  unsubscribeFromTodos = realtimeService.onTodo(async (event) => {
    
    switch (event.type) {
      case 'todo_created':
        // Add new todo to the list if it belongs to current project
        const newTodo = event.data;
        if (!currentProject.value || newTodo.project_id === currentProject.value.id) {
          if (!todos.value.find(t => t.id === newTodo.id)) {
            // Ensure the new todo has the project relationship
            const todoWithProject = {
              ...newTodo,
              project: currentProject.value
            };
            todos.value.push(todoWithProject);
          }
        }
        break;
        
      case 'todo_updated':
        // Update existing todo in the list
        const updatedTodo = event.data;
        const updateIndex = todos.value.findIndex(t => t.id === updatedTodo.id);
        if (updateIndex !== -1) {
          // Preserve the project relationship from the existing todo
          const existingTodo = todos.value[updateIndex];
          const todoWithProject = {
            ...updatedTodo,
            project: existingTodo.project || currentProject.value
          };
          todos.value[updateIndex] = todoWithProject;
        }
        break;
        
      case 'todo_deleted':
        // Remove todo from the list
        const deletedTodo = event.data;
        const deleteIndex = todos.value.findIndex(t => t.id === deletedTodo.id);
        if (deleteIndex !== -1) {
          todos.value.splice(deleteIndex, 1);
        }
        break;
    }
  });
  
  // Set up event listeners
  const handleStorageChange = (e: StorageEvent) => {
    if (e.key === 'currentProjectId') {
      loadCurrentProject();
    }
  };
  
  window.addEventListener('storage', handleStorageChange);
  
  // Also listen for custom events from the sidebar
  window.addEventListener('projectSelected', async (e: any) => {
    if (!isUpdatingProject.value) {
      isUpdatingProject.value = true;
      try {
        if (e.detail?.projectId) {
          // Project was selected
          localStorage.setItem('currentProjectId', e.detail.projectId.toString());
          await loadCurrentProject();
          await loadTodos();
        } else {
          // Project was cleared (null projectId)
          currentProject.value = null;
          selectedProjectId.value = '';
          localStorage.removeItem('currentProjectId');
          todos.value = [];
        }
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
  
  // Listen for project changes (like deletion) to refresh project list
  window.addEventListener('todoChanged', async () => {
    await loadProjects();
    
    // Check if current project still exists
    if (currentProject.value) {
      const projectStillExists = projects.value.find(p => p.id === currentProject.value!.id);
      if (!projectStillExists) {
        currentProject.value = null;
        selectedProjectId.value = '';
        localStorage.removeItem('currentProjectId');
        todos.value = [];
        
        // Auto-select first available project if any
        if (projects.value.length > 0) {
          const firstProject = projects.value[0];
          currentProject.value = firstProject;
          selectedProjectId.value = firstProject.id.toString();
          localStorage.setItem('currentProjectId', firstProject.id.toString());
          await loadTodos();
        }
      }
    }
  });
  
  // Listen for project list changes (more specific event for project deletions)
  window.addEventListener('projectListChanged', async () => {
    await loadProjects();
    
    // Force reload current project from localStorage to sync with NavProjects
    const storedProjectId = localStorage.getItem('currentProjectId');
    
    if (storedProjectId) {
      const projectExists = projects.value.find(p => p.id === parseInt(storedProjectId));
      if (projectExists) {
        currentProject.value = projectExists;
        selectedProjectId.value = storedProjectId;
        await loadTodos();
      } else {
        currentProject.value = null;
        selectedProjectId.value = '';
        localStorage.removeItem('currentProjectId');
        todos.value = [];
      }
    } else {
      currentProject.value = null;
      selectedProjectId.value = '';
      todos.value = [];
    }
  });
  
  // Listen for subscription downgrades to reload projects and check current project access
  window.addEventListener('subscription-downgrade', async (e: any) => {
    await loadProjects();
    
    // Check if current project is still accessible after downgrade
    if (currentProject.value) {
      const projectStillAccessible = projects.value.find(p => p.id === currentProject.value!.id);
      if (!projectStillAccessible) {
        currentProject.value = null;
        selectedProjectId.value = '';
        localStorage.removeItem('currentProjectId');
        todos.value = [];
        
        // Auto-select first available project if any
        if (projects.value.length > 0) {
          const firstProject = projects.value[0];
          currentProject.value = firstProject;
          selectedProjectId.value = firstProject.id.toString();
          localStorage.setItem('currentProjectId', firstProject.id.toString());
          await loadTodos();
        }
      }
    }
  });

  // Load saved views
  loadSavedViews();
});

// Cleanup on unmount
onUnmounted(() => {
  if (unsubscribeFromTodos) {
    unsubscribeFromTodos();
  }
});

</script>
