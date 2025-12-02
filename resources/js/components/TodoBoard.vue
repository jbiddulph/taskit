<template>
  <div 
    class="h-full flex flex-col"
    :class="{ 'pb-24': props.isSelectMode && hasSelection }"
  >
    <!-- Bulk submit overlay -->
    <div
      v-if="isSubmittingBulk"
      class="fixed inset-0 z-[100] bg-black/40 backdrop-blur-sm flex items-center justify-center"
    >
      <div class="flex items-center gap-3 px-4 py-3 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700">
        <Icon name="loader-circle" class="w-5 h-5 animate-spin text-blue-600" />
        <span class="text-sm text-gray-900 dark:text-gray-100">{{ t('dashboard.adding_all_please_wait') }}</span>
      </div>
    </div>

    <!-- Voice recording overlay - Pre-recording countdown (shows on top for 3 seconds) -->
    <div
      v-if="isPreRecording && isRecording"
      class="fixed inset-0 z-[101] bg-black/40 backdrop-blur-sm flex items-center justify-center"
    >
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl border-2 border-purple-500 p-8 max-w-md w-full mx-4">
        <div class="flex flex-col items-center gap-4">
          <!-- Countdown icon -->
          <div class="relative">
            <div class="absolute inset-0 bg-purple-500 rounded-full animate-ping opacity-75"></div>
            <div class="relative bg-purple-600 rounded-full p-6">
              <Icon name="Mic" class="w-12 h-12 text-white" />
            </div>
          </div>
          
          <!-- Countdown text -->
          <div class="text-center">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
              Recording started!
            </h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
              You have 10 seconds to record your task
            </p>
          </div>

          <!-- Pre-recording countdown timer -->
          <div class="text-6xl font-bold text-purple-600 dark:text-purple-400 font-mono">
            {{ preRecordingCountdown }}
          </div>

          <!-- Cancel button -->
          <button
            @click="stopRecording"
            class="mt-2 px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors font-medium"
          >
            Cancel
          </button>
        </div>
      </div>
    </div>

    <!-- Voice recording overlay - Recording -->
    <div
      v-if="isRecording"
      class="fixed inset-0 z-[100] bg-black/40 backdrop-blur-sm flex items-center justify-center"
    >
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl border-2 border-red-500 p-8 max-w-md w-full mx-4">
        <div class="flex flex-col items-center gap-4">
          <!-- Pulsing microphone icon -->
          <div class="relative">
            <div class="absolute inset-0 bg-red-500 rounded-full animate-ping opacity-75"></div>
            <div class="relative bg-red-600 rounded-full p-6">
              <Icon name="Mic" class="w-12 h-12 text-white" />
            </div>
          </div>
          
          <!-- Recording text -->
          <div class="text-center">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
              {{ t('dashboard.recording') }}
            </h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">
              {{ t('dashboard.speak_now_to_create_todo') }}
            </p>
          </div>

          <!-- Recording countdown timer (showing remaining time) -->
          <div class="text-4xl font-bold text-red-600 dark:text-red-400 font-mono">
            {{ Math.max(0, recordingDurationLimit - recordingTime) }}
          </div>
          <p class="text-xs text-gray-500 dark:text-gray-400">
            seconds remaining
          </p>

          <!-- Stop button -->
          <button
            @click="stopRecording"
            class="mt-2 px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors font-medium"
          >
            <div class="flex items-center gap-2">
              <Icon name="Square" class="w-5 h-5" />
              {{ t('dashboard.stop_recording') }}
            </div>
          </button>
        </div>
      </div>
    </div>

    <!-- Header with Search and Filters -->
    <div 
      class="mb-4 py-2 px-4 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 relative"
      :style="currentProject ? {
        borderTop: `4px solid ${currentProject.color}`,
        backgroundColor: `${currentProject.color}10`
      } : {}"
    >
      <div class="flex flex-col lg:flex-row gap-4 items-start lg:items-center justify-between">
        <div class="flex-1">
          <div class="flex items-center gap-3 mb-2">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
              {{ currentProject ? currentProject.name : t('dashboard.todo_board') }}
            </h1>
            <button
              v-if="currentProject && !props.isReadOnly"
              @click="startEditProject"
              class="p-1 text-gray-400 hover:text-blue-500 transition-colors"
              :title="t('dashboard.edit_project_name')"
            >
<Icon name="Edit3" class="w-5 h-5" />
            </button>
          </div>
          <div class="text-gray-600 dark:text-gray-400">
            <span v-if="!currentProject">{{ t('dashboard.select_project') }}</span>
            <div v-else class="flex items-center gap-2">
              <span>{{ t('dashboard.project_label') }}: {{ currentProject.key }} - </span>
              <div v-if="!editingProjectDescription" class="flex items-center gap-2">
                <span>{{ currentProject.description || t('dashboard.no_description') }}</span>
                <button
                  v-if="!props.isReadOnly"
                  @click="startEditProjectDescription"
                  class="p-1 text-gray-400 hover:text-blue-500 transition-colors"
                  :title="t('dashboard.edit_project_description')"
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
                  :placeholder="t('todos.enter_project_description')"
                  ref="projectDescriptionInput"
                />
                <button
                  @click="saveProjectDescription"
                  class="p-1 text-green-500 hover:text-green-600 transition-colors"
                  :title="t('common.save')"
                >
                  <Icon name="Check" class="w-4 h-4" />
                </button>
                <button
                  @click="cancelEditProjectDescription"
                  class="p-1 text-gray-400 hover:text-red-500 transition-colors"
                  :title="t('common.cancel')"
                >
                  <Icon name="X" class="w-4 h-4" />
                </button>
              </div>
            </div>
          </div>
          
                      <!-- Create Project Button (only shown when no project is selected) -->
            <div v-if="!currentProject && !props.isReadOnly" class="mt-4">
              <button
                @click="showCreateProject = true"
                class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors"
              >
                <Icon name="Plus" class="w-4 h-4" />
                {{ t('dashboard.create_project') }}
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
              v-if="!props.isReadOnly"
              @click="handleShowForm"
              :disabled="!currentProject"
              class="flex-1 md:flex-none inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors disabled:opacity-50 disabled:cursor-not-allowed
                     bg-black text-white hover:bg-gray-900 focus:ring-gray-900
                     dark:bg-white dark:text-black dark:hover:bg-gray-100 dark:focus:ring-gray-300"
            >
              <Icon name="Plus" class="w-4 h-4" />
              <span class="hidden sm:inline">{{ t('dashboard.add') }}</span>
              <span class="sm:hidden">{{ t('dashboard.add') }}</span>
            </button>

            <!-- Add Bulk Button (center) -->
            <button
              v-if="!props.isReadOnly"
              @click="startBulkMode"
              :disabled="!currentProject"
              class="flex-1 md:flex-none inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors disabled:opacity-50 disabled:cursor-not-allowed
                     bg-black text-white hover:bg-gray-900 focus:ring-gray-900
                     dark:bg-white dark:text-black dark:hover:bg-gray-100 dark:focus:ring-gray-300"
            >
              <Icon name="Plus" class="w-4 h-4" />
              <span class="hidden sm:inline">{{ t('dashboard.add_bulk') }}</span>
              <span class="sm:hidden">{{ t('dashboard.bulk') }}</span>
            </button>

            <!-- Voice Record Button (right) -->
            <button
              v-if="!props.isReadOnly"
              @click="handleVoiceRecord"
              :disabled="!currentProject || isRecording || isPreRecording"
              :class="[
                'flex-1 md:flex-none inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors disabled:opacity-50 disabled:cursor-not-allowed',
                (isRecording || isPreRecording)
                  ? 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500' 
                  : 'bg-black text-white hover:bg-gray-900 focus:ring-gray-900 dark:bg-white dark:text-black dark:hover:bg-gray-100 dark:focus:ring-gray-300'
              ]"
              :title="(isRecording || isPreRecording) ? t('dashboard.stop_recording') : t('dashboard.voice_record')"
            >
              <Icon name="Mic" class="w-4 h-4" />
              <span class="hidden sm:inline">{{ (isRecording || isPreRecording) ? t('dashboard.recording') : t('dashboard.voice') }}</span>
            </button>

            <!-- Filters Button -->
            <button
              v-if="!props.isReadOnly"
              @click="showFilters = !showFilters"
              :title="showFilters ? t('dashboard.hide_filters') : t('dashboard.show_filters')"
              :class="[
                'inline-flex items-center justify-center w-10 h-10 rounded-lg border transition-colors',
                showFilters 
                  ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 border-blue-300 dark:border-blue-600' 
                  : 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-100 border-gray-300 dark:border-gray-600 hover:bg-gray-200 dark:hover:bg-gray-600'
              ]"
            >
              <Icon name="Filter" class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>

  <!-- Bulk Pending - Mobile-friendly stacked cards -->
  <div v-if="pendingBulkTodos.length > 0" class="mt-3 p-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
    <div class="flex items-center justify-between mb-3">
      <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">
        {{ t('dashboard.pending_todos_to_add') }} ({{ pendingBulkTodos.length }})
      </h3>
      <div class="flex items-center gap-2">
        <button
          @click="handleShowForm"
          class="inline-flex items-center gap-1 px-3 py-1.5 text-xs bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded hover:bg-gray-200 dark:hover:bg-gray-600"
        >
          <Icon name="Plus" class="w-3 h-3" /> {{ t('dashboard.add_another') }}
        </button>
        <button
          @click="onSubmitBulkTodos"
          class="inline-flex items-center gap-1 px-3 py-1.5 text-xs bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50"
          :disabled="pendingBulkTodos.length === 0 || !currentProject"
        >
          <Icon name="Check" class="w-3 h-3" /> {{ t('dashboard.add_all') }}
        </button>
      </div>
    </div>

    <div class="space-y-2">
      <div
        v-for="(p, idx) in pendingBulkTodos"
        :key="idx"
        class="p-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900"
      >
        <div class="flex items-start justify-between gap-3">
          <div class="min-w-0">
            <div class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ p.title }}</div>
            <div class="mt-1 flex flex-wrap gap-1 text-[11px] text-gray-600 dark:text-gray-300">
              <span v-if="p.priority" class="px-2 py-0.5 rounded bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-200">{{ p.priority }}</span>
              <span v-if="p.type" class="px-2 py-0.5 rounded bg-purple-100 dark:bg-purple-900/40 text-purple-700 dark:text-purple-200">{{ p.type }}</span>
              <span v-if="p.assignee" class="px-2 py-0.5 rounded bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-200">{{ p.assignee }}</span>
              <span v-if="p.due_date" class="px-2 py-0.5 rounded bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-200">{{ p.due_date }}</span>
            </div>
          </div>
          <div class="flex items-center gap-2">
            <button @click="removePending(idx)" class="text-xs text-red-600 hover:text-red-700">{{ t('dashboard.remove') }}</button>
          </div>
        </div>
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
            :placeholder="t('dashboard.search_placeholder')"
            class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white text-gray-900"
          />
        </div>
        
        
        <!-- Filter Dropdowns - Mobile: Stacked, Desktop: Row -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
          <!-- Priority Filter -->
          <div>
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">{{ t('dashboard.priority_filter') }}</label>
            <select
              v-model="priorityFilter"
              class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
            >
              <option value="">{{t('filters.all_priorities')}}</option>
              <option :value="t('todos.priority_low')">{{t('todos.priority_low')}}</option>
              <option :value="t('todos.priority_medium')">{{t('todos.priority_medium')}}</option>
              <option :value="t('todos.priority_high')">{{t('todos.priority_high')}}</option>
              <option :value="t('todos.priority_critical')">{{t('todos.priority_critical')}}</option>
            </select>
          </div>
          
          <!-- Type Filter -->
          <div>
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">{{ t('dashboard.type_filter') }}</label>
            <TypeFilter v-model="typeFilter" />
          </div>
          
          <!-- Assignee Filter -->
          <div>
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">{{ t('dashboard.assignee_filter') }}</label>
            <select
              v-model="assigneeFilter"
              class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
            >
              <option value="">{{ t('dashboard.all_assignees') }}</option>
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
            :title="t('dashboard.save_view_tooltip')"
          >
            <Icon name="Save" class="w-4 h-4 inline mr-2" />
            {{ t('dashboard.save_view') }}
          </button>


          <!-- Saved Views Dropdown - Only show if there are saved views -->
          <div v-if="savedViews.length > 0" class="flex flex-col sm:flex-row gap-2">
            <select
              v-model="selectedSavedViewName"
              @change="onApplySavedView"
              class="flex-1 px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
            >
              <option value="">{{ t('dashboard.saved_views') }}</option>
              <option v-for="view in savedViews" :key="view.name" :value="view.name">{{ view.name }}</option>
            </select>

            <button
              v-if="selectedSavedViewName"
              type="button"
              @click="deleteSavedView"
              class="px-3 py-2 text-sm bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded-lg border border-red-200 dark:border-red-700 hover:bg-red-100 dark:hover:bg-red-900/50"
              :title="t('dashboard.delete_view')"
            >
              <Icon name="Trash2" class="w-4 h-4 inline mr-1" />
              {{ t('common.delete') }}
            </button>
          </div>
        </div>
      </div>
      
      <!-- Filter Status Indicator -->
      <div v-if="hasActiveFilters" class="mt-3 flex items-center justify-between bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg px-4 py-2">
        <div class="flex items-center gap-2 text-sm text-blue-700 dark:text-blue-300">
          <Icon name="Filter" class="w-4 h-4" />
          <span>{{ t('dashboard.filters_applied') }}</span>
        </div>
        <button
          @click="clearAllFilters"
          class="flex items-center gap-1 text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-200 transition-colors"
        >
          <Icon name="X" class="w-4 h-4" />
          <span>{{ t('dashboard.clear_filters') }}</span>
        </button>
      </div>
    </div>

    <!-- Statistics / Calendar -->
    <div v-if="props.showCalendar" class="mb-4">
      <CalendarView :todos="todos" :isReadOnly="props.isReadOnly" @edit-todo="handleEditTodoFromCalendar" @add-todo="handleAddTodoFromCalendar" />
    </div>

    <!-- Bulk Operations Bar -->
    <BulkOperationsBar
      v-if="props.isSelectMode && !props.isReadOnly"
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
        :title="t('todos.to_do')"
        status="todo"
        :todos="filteredTodos.todo"
        :show-add-button="true"
        :current-project-id="currentProject?.id || null"
        :current-project-color="currentProject?.color || null"
        :is-select-mode="props.isSelectMode"
        :selected-items="selectedItems"
        :is-read-only="props.isReadOnly"
        @add="handleShowForm"
        @edit="editTodo"
        @delete="deleteTodo"
        @update="updateTodo"
        @drop="handleDrop"
        @reorder="handleReorder"
        @menu="() => {}"
        @add-subtask="handleAddSubtask"
        @toggle-selection="toggleSelection"
        @todo-click="emit('todo-click', $event)"
      />
      
      <TodoColumn
        :title="t('todos.in_progress')"
        status="in-progress"
        :todos="filteredTodos.inProgress"
        :current-project-id="currentProject?.id || null"
        :current-project-color="currentProject?.color || null"
        :is-select-mode="props.isSelectMode"
        :selected-items="selectedItems"
        :is-read-only="props.isReadOnly"
        @edit="editTodo"
        @delete="deleteTodo"
        @update="updateTodo"
        @drop="handleDrop"
        @reorder="handleReorder"
        @menu="() => {}"
        @add-subtask="handleAddSubtask"
        @toggle-selection="toggleSelection"
        @todo-click="emit('todo-click', $event)"
      />
      
      <TodoColumn
        :title="t('dashboard.qa_testing')"
        status="qa-testing"
        :todos="filteredTodos.qaTesting"
        :current-project-id="currentProject?.id || null"
        :current-project-color="currentProject?.color || null"
        :is-select-mode="props.isSelectMode"
        :selected-items="selectedItems"
        :is-read-only="props.isReadOnly"
        @edit="editTodo"
        @delete="deleteTodo"
        @update="updateTodo"
        @drop="handleDrop"
        @reorder="handleReorder"
        @menu="() => {}"
        @add-subtask="handleAddSubtask"
        @toggle-selection="toggleSelection"
        @todo-click="emit('todo-click', $event)"
      />
      
      <TodoColumn
        :title="t('todos.done')"
        status="done"
        :todos="filteredTodos.done"
        :current-project-id="currentProject?.id || null"
        :current-project-color="currentProject?.color || null"
        :is-select-mode="props.isSelectMode"
        :selected-items="selectedItems"
        :is-read-only="props.isReadOnly"
        @edit="editTodo"
        @delete="deleteTodo"
        @update="updateTodo"
        @drop="handleDrop"
        @reorder="handleReorder"
        @menu="() => {}"
        @add-subtask="handleAddSubtask"
        @toggle-selection="toggleSelection"
        @todo-click="emit('todo-click', $event)"
      />
    </div>

    <!-- Statistics - Full Width Below Columns -->
    <div v-if="!props.showCalendar" class="w-full mt-4">
      <TodoStats :todos="todos" />
    </div>

    <!-- Todo Form Modal -->
    <TodoForm
      v-if="showForm && !props.isReadOnly"
      :todo="editingTodo"
      :is-editing="!!editingTodo"
      :current-project="currentProject"
      :modal-title="!editingTodo && isBulkMode ? t('dashboard.add_bulk') : undefined"
      @close="closeForm"
      @save="saveTodo"
    />

    <!-- Create Project Modal -->
    <div v-if="showCreateProject && !props.isReadOnly" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click="showCreateProject = false">
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full" @click.stop>
        <div class="p-6">
          <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">{{ t('projects.create_new_project') }}</h2>
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
                {{ t('projects.project_name') }} *
              </label>
              <input
                v-model="newProject.name"
                type="text"
                required
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                :placeholder="t('todos.enter_project_name')"
              />
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                {{ t('projects.description_optional') }}
              </label>
              <textarea
                v-model="newProject.description"
                rows="3"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                :placeholder="t('todos.enter_project_description')"
              ></textarea>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                {{ t('projects.project_key_optional') }}
              </label>
              <input
                v-model="newProject.key"
                type="text"
                maxlength="10"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                placeholder="e.g., PROJ, FEAT"
              />
              <p class="text-sm text-gray-500 mt-1">{{ t('projects.leave_empty_auto') }}</p>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                {{ t('projects.client_optional') }}
              </label>
              <select
                v-model="newProject.client_id"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
              >
                <option :value="null">{{ t('projects.no_client') }}</option>
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
                {{ t('projects.color') }}
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
                {{ t('projects.create_project') }}
              </button>
              <button
                type="button"
                @click="showCreateProject = false"
                class="flex-1 px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors"
              >
                {{ t('common.cancel') }}
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
            <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">{{ t('projects.edit_project') }}</h2>
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
                {{ t('projects.project_name') }} *
              </label>
              <input
                v-model="editingProjectName"
                type="text"
                required
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                :placeholder="t('todos.enter_project_name')"
              />
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                {{ t('projects.client_optional') }}
              </label>
              <select
                v-model="editingProjectClientId"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
              >
                <option :value="null">{{ t('projects.no_client') }}</option>
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
                {{ t('projects.save_changes') }}
              </button>
              <button
                type="button"
                @click="showEditProject = false"
                class="flex-1 px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors"
              >
                {{ t('common.cancel') }}
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
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ t('dashboard.save_view') }}</h3>
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
              {{ t('dashboard.saved_views') }}
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
              {{ t('dashboard.save_view') }}
            </button>
            <button
              type="button"
              @click="showSaveViewModal = false"
              class="flex-1 px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors"
            >
              {{ t('common.cancel') }}
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
import { useI18n } from 'vue-i18n';
const { t } = useI18n();
// Define props
const props = defineProps<{
  showActivityFeed?: boolean;
  showCalendar?: boolean;
  isSelectMode?: boolean;
  isReadOnly?: boolean;
  todos?: any[];
  projects?: any[];
  selectedProject?: any;
}>();

// Define emits
const emit = defineEmits<{
  'project-changed': [project: any];
  'toggle-activity-feed': [];
  'toggle-calendar': [];
  'toggle-select-mode': [];
  'todo-click': [todo: any];
}>();

const todos = ref<Todo[]>([]);
const isBulkMode = ref(false);
const isSubmittingBulk = ref(false);
type PendingTodo = {
  title: string;
  description?: string;
  priority?: string;
  type?: string;
  assignee?: string;
  due_date?: string;
  tags?: string[];
  parent_task_id?: number | null;
};
const pendingBulkTodos = ref<PendingTodo[]>([]);

const startBulkMode = () => {
  isBulkMode.value = true;
  handleShowForm();
};

const removePending = (idx: number) => {
  pendingBulkTodos.value.splice(idx, 1);
};
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
  selectedItems, 
  selectedCount, 
  hasSelection,
  clearSelection,
  toggleSelection: toggleSelectionById,
  isSelected
} = useBulkOperations();

// Local toggle function that emits to parent
const toggleSelectMode = () => {
  emit('toggle-select-mode');
};

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
    // Exclude subtasks from the filtered list (they're attached to parents)
    if (todo.parent_task_id !== null) {
      return false;
    }
    
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
  console.log('💾 saveTodo called with todo:', todo);
  console.log('💾 parent_task_id:', todo.parent_task_id);
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
      // In bulk mode, collect locally instead of immediate API create
      if (isBulkMode.value) {
        pendingBulkTodos.value.push({
          title: todo.title,
          description: todo.description,
          priority: todo.priority,
          type: todo.type,
          assignee: todo.assignee,
          due_date: (todo as any).due_date,
          tags: (todo as any).tags || [],
          parent_task_id: (todo as any).parent_task_id || null,
        });
        showForm.value = false;
        if ((window as any).$notify) {
          (window as any).$notify({ type: 'success', title: 'Queued', message: 'Todo added to bulk list.' });
        }
        return;
      }
      // Add new todo or subtask
      let newTodo;
      if (todo.parent_task_id) {
        // Create subtask
        console.log('🎯 Creating subtask with parent_task_id:', todo.parent_task_id, 'Payload:', { ...todo, project_id: currentProject.value.id });
        newTodo = await todoApi.createSubtask(todo.parent_task_id, {
          ...todo,
          project_id: currentProject.value.id
        });
        console.log('🎯 Created subtask:', newTodo);
        
        // Real-time updates will handle adding the subtask to the list
      } else {
        // Create regular todo
        newTodo = await todoApi.createTodo({
          ...todo,
          project_id: currentProject.value.id
        });
        
        // Real-time updates will handle adding the todo to the list
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

const deleteTodo = async (id: number | string) => {
  const todoId = typeof id === 'string' ? parseInt(id) : id;
  const todo = todos.value.find(t => t.id === todoId);
  if (!todo) return;
  
  if (!confirm(`Are you sure you want to delete "${todo.title}"?\n\nThis action cannot be undone.`)) {
    return;
  }
  
  try {
    const toDelete = todos.value.find(t => t.id === todoId);
    await todoApi.deleteTodo(todoId);
    
    // Remove from local state
    todos.value = todos.value.filter(t => t.id !== todoId);
    
    // Track todo deletion event
    trackTodoEvent('deleted', {
      todo_id: todoId,
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
    
    // Real-time updates will handle removing the todo from the list
    
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

// Voice recording state
const isRecording = ref(false);
const isPreRecording = ref(false);
const recognition = ref<any>(null);
const recordingTime = ref(0);
const recordingTimer = ref<number | null>(null);
const preRecordingCountdown = ref(3);
const preRecordingTimer = ref<number | null>(null);
const recordingDurationLimit = 10; // 10 seconds
const accumulatedTranscript = ref('');

// Initialize voice recording
const initVoiceRecording = () => {
  // Check if we're in a secure context (HTTPS or localhost)
  if (!window.isSecureContext) {
    console.warn('Web Speech API requires a secure context (HTTPS or localhost)');
    if ((window as any).$notify) {
      (window as any).$notify({
        type: 'error',
        title: 'Secure Context Required',
        message: 'Voice recording requires a secure connection. Please use HTTPS or localhost.'
      });
    }
    return;
  }
  
  // Check if browser supports Web Speech API
  if (!('webkitSpeechRecognition' in window) && !('SpeechRecognition' in window)) {
    console.warn('Web Speech API not supported in this browser');
    return;
  }

  const SpeechRecognition = (window as any).webkitSpeechRecognition || (window as any).SpeechRecognition;
  recognition.value = new SpeechRecognition();
  
  recognition.value.continuous = true; // Keep recording until stopped
  recognition.value.interimResults = true; // Show interim results
  recognition.value.lang = 'en-US';

  recognition.value.onresult = async (event: any) => {
    // Accumulate all results
    let interimTranscript = '';
    let finalTranscript = '';
    
    for (let i = event.resultIndex; i < event.results.length; i++) {
      const transcript = event.results[i][0].transcript;
      if (event.results[i].isFinal) {
        finalTranscript += transcript + ' ';
      } else {
        interimTranscript += transcript;
      }
    }
    
    accumulatedTranscript.value = finalTranscript + interimTranscript;
    console.log('Voice transcription (interim):', accumulatedTranscript.value);
    
    // Only create todo when we have final results and recording has stopped
    // We'll handle this in onend to avoid creating multiple todos
  };

  recognition.value.onerror = (event: any) => {
    console.error('❌ Speech recognition error:', event.error);
    
    let errorMessage = 'Failed to recognize speech.';
    let errorTitle = 'Voice Recognition Error';
    
    if (event.error === 'not-allowed') {
      errorTitle = 'Microphone Access Denied';
      // Detect browser for specific instructions
      const isChrome = /Chrome/.test(navigator.userAgent) && /Google Inc/.test(navigator.vendor);
      const isSafari = /Safari/.test(navigator.userAgent) && /Apple Computer/.test(navigator.vendor);
      const isFirefox = /Firefox/.test(navigator.userAgent);
      
      if (isChrome) {
        errorMessage = 'Microphone access was denied. To enable:\n1. Click the lock icon (🔒) in the address bar\n2. Find "Microphone" in the permissions list\n3. Change it to "Allow"\n4. Refresh the page and try again.';
      } else if (isSafari) {
        errorMessage = 'Microphone access was denied. To enable:\n1. Go to Safari → Settings for This Website\n2. Find "Microphone" and set it to "Allow"\n3. Refresh the page and try again.';
      } else if (isFirefox) {
        errorMessage = 'Microphone access was denied. To enable:\n1. Click the lock icon (🔒) in the address bar\n2. Click "Permissions" → "Microphone"\n3. Select "Allow" and refresh the page.';
      } else {
        errorMessage = 'Microphone access was denied. Please allow microphone access in your browser settings (look for the lock icon in the address bar) and try again.';
      }
    } else if (event.error === 'no-speech') {
      errorMessage = 'No speech detected. Please try speaking again or check that your microphone is working.';
    } else if (event.error === 'audio-capture') {
      errorMessage = 'No microphone found. Please connect a microphone and try again.';
    } else if (event.error === 'network') {
      errorMessage = 'Network error. Please check your internet connection and try again.';
    } else if (event.error === 'aborted') {
      // User stopped recording, don't show error
      return;
    } else {
      errorMessage = `Speech recognition error: ${event.error}. Please try again.`;
    }
    
    if ((window as any).$notify) {
      (window as any).$notify({
        type: 'error',
        title: errorTitle,
        message: errorMessage
      });
    }
    isRecording.value = false;
    stopTimer();
    accumulatedTranscript.value = '';
  };

  recognition.value.onstart = () => {
    console.log('🎤 Speech recognition started');
  };

  recognition.value.onend = async () => {
    console.log('🛑 Speech recognition ended');
    isRecording.value = false;
    stopTimer();
    
    // Create todo from accumulated transcript when recording ends
    if (accumulatedTranscript.value.trim() && currentProject.value) {
      const transcript = accumulatedTranscript.value.trim();
      console.log('Creating todo from voice transcription:', transcript);
      
      try {
        const newTodo = await todoApi.createTodo({
          project_id: currentProject.value.id,
          title: transcript,
          description: '',
          status: 'todo',
          priority: 'Medium',
        });
        
        console.log('✅ Voice todo created successfully:', newTodo);
        
        // The realtime service should automatically add it to the list
        // But we can also manually add it for immediate feedback
        if (newTodo.project_id === currentProject.value.id) {
          todos.value.push(newTodo);
        }
        
        // Close any open modal/form
        showForm.value = false;
        editingTodo.value = null;
        
        if ((window as any).$notify) {
          (window as any).$notify({
            type: 'success',
            title: 'Voice Todo Created',
            message: `Created: "${transcript}"`
          });
        }
      } catch (error) {
        console.error('❌ Error creating voice todo:', error);
        if ((window as any).$notify) {
          (window as any).$notify({
            type: 'error',
            title: 'Save Failed',
            message: 'Failed to create todo from voice recording. Please try again.'
          });
        }
      }
      
      // Reset accumulated transcript
      accumulatedTranscript.value = '';
    }
  };
};

// Timer formatting function
const formatTimer = (seconds: number): string => {
  const mins = Math.floor(seconds / 60);
  const secs = seconds % 60;
  return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
};

// Start timer with 10 second limit
const startTimer = () => {
  recordingTime.value = 0;
  recordingTimer.value = window.setInterval(() => {
    recordingTime.value++;
    // Auto-stop after 10 seconds
    if (recordingTime.value >= recordingDurationLimit) {
      stopRecording();
    }
  }, 1000);
};

// Start pre-recording countdown (recording already started)
const startPreRecordingCountdown = () => {
  isPreRecording.value = true;
  preRecordingCountdown.value = 3;
  preRecordingTimer.value = window.setInterval(() => {
    preRecordingCountdown.value--;
    if (preRecordingCountdown.value <= 0) {
      stopPreRecordingCountdown();
      // Start the 10-second timer now
      startTimer();
    }
  }, 1000);
};

// Stop pre-recording countdown
const stopPreRecordingCountdown = () => {
  if (preRecordingTimer.value) {
    clearInterval(preRecordingTimer.value);
    preRecordingTimer.value = null;
  }
  isPreRecording.value = false;
  preRecordingCountdown.value = 3;
};

// Start actual recording
const startActualRecording = () => {
  if (!recognition.value || !currentProject.value) {
    return;
  }
  
  console.log('🎤 Starting voice recording...');
  try {
    accumulatedTranscript.value = ''; // Reset transcript for new recording
    recognition.value.start();
    isRecording.value = true;
    // Timer will start after 3-second countdown completes
    console.log('✅ Recording started successfully');
  } catch (error: any) {
    console.error('❌ Failed to start speech recognition:', error);
    isRecording.value = false;
    stopTimer();
    
    let errorMessage = 'Failed to start voice recording. Please try again.';
    if (error.message && error.message.includes('not-allowed')) {
      errorMessage = 'Microphone access denied. Please allow microphone access in your browser settings.';
    }
    
    if ((window as any).$notify) {
      (window as any).$notify({
        type: 'error',
        title: 'Recording Failed',
        message: errorMessage
      });
    }
  }
};

// Stop timer
const stopTimer = () => {
  if (recordingTimer.value) {
    clearInterval(recordingTimer.value);
    recordingTimer.value = null;
  }
};

// Stop recording handler
const stopRecording = () => {
  console.log('🔴 Stop recording called');
  stopPreRecordingCountdown(); // Stop pre-recording if active
  
  if (recognition.value && isRecording.value) {
    recognition.value.stop();
    // onend will handle creating the todo and resetting the transcript
  } else {
    // If not recording, just reset state
    isRecording.value = false;
    stopTimer();
    accumulatedTranscript.value = '';
  }
};

const handleVoiceRecord = () => {
  if (!currentProject.value) {
    if ((window as any).$notify) {
      (window as any).$notify({
        type: 'warning',
        title: 'Project Required',
        message: 'Please select a project first before creating a voice todo.'
      });
    }
    return;
  }

  if (!recognition.value) {
    initVoiceRecording();
    // If initialization failed, recognition will still be null
    if (!recognition.value) {
      if ((window as any).$notify) {
        (window as any).$notify({
          type: 'error',
          title: 'Browser Not Supported',
          message: 'Voice recording is not supported in this browser. Please use Chrome, Edge, or Safari.'
        });
      }
      return;
    }
  }

  if (isRecording.value || isPreRecording.value) {
    // Stop recording or pre-recording countdown
    console.log('🛑 Already recording or counting down, stopping...');
    stopRecording();
  } else {
    // Start recording immediately, then show 3-second countdown overlay
    console.log('🎤 Starting voice recording immediately...');
    startActualRecording();
    startPreRecordingCountdown();
  }
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
    
    // In read-only mode, find project from props
    if (props.isReadOnly && props.projects) {
      const project = props.projects.find(p => p.id === projectId);
      if (project) {
        currentProject.value = project;
        // Filter todos for the selected project
        if (props.todos) {
          todos.value = props.todos.filter(todo => todo.project_id === projectId);
        }
        return;
      }
    }
    
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
      if (props.isSelectMode) {
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
  console.log('🚀 loadTodos called');
  try {
    const filters: any = {};
    if (currentProject.value) {
      filters.project_id = currentProject.value.id;
      console.log('🚀 Loading todos for project:', currentProject.value.id);
    } else {
      console.log('🚀 No current project set');
    }
    const response = await todoApi.getTodos(filters);
    console.log('🚀 API response received:', response);
    console.log('🚀 response.data.todo:', response.data.todo);
    console.log('🚀 response.data[\'in-progress\']:', response.data['in-progress']);
    console.log('🚀 response.data[\'qa-testing\']:', response.data['qa-testing']);
    console.log('🚀 response.data.done:', response.data.done);
    
    // Check specific todos
    const allTodos = response.data.todo.concat(response.data['in-progress']).concat(response.data['qa-testing']).concat(response.data.done);
    console.log('🚀 All todos from API:', allTodos.map(t => ({ id: t.id, title: t.title, parent_task_id: t.parent_task_id })));
    const subtasks = allTodos.filter(t => t.parent_task_id !== null);
    console.log('🚀 Filtered subtasks:', subtasks.map(s => ({ id: s.id, title: s.title, parent_task_id: s.parent_task_id })));
    const parentTasks = allTodos.filter(t => t.parent_task_id === null);
    
    console.log('📋 Loaded todos - Total:', allTodos.length, 'Subtasks:', subtasks.length, 'Parents:', parentTasks.length);
    
    // Attach subtasks to their parent tasks
    allTodos.forEach(todo => {
      if (!todo.parent_task_id) {
        // Initialize subtasks array if it doesn't exist
        if (!todo.subtasks) {
          todo.subtasks = [];
        }
        
        // Find and attach all subtasks that belong to this parent
        const childSubtasks = subtasks.filter(st => st.parent_task_id === todo.id);
        todo.subtasks = childSubtasks;
        
        // Debug logging
        if (childSubtasks.length > 0) {
          console.log(`✅ Todo "${todo.title}" has ${childSubtasks.length} subtasks:`, childSubtasks.map(s => s.title));
        } else {
          console.log(`ℹ️ Todo "${todo.title}" has NO subtasks (looking for parent_task_id=${todo.id})`);
        }
      } else {
        console.log(`ℹ️ Skipping subtask "${todo.title}" (parent_task_id=${todo.parent_task_id})`);
      }
    });
    
    // Debug: Check if we have any todos with subtasks attached
    const todosWithSubtasks = allTodos.filter(t => t.subtasks && t.subtasks.length > 0);
    console.log(`📊 Todos with subtasks attached: ${todosWithSubtasks.length}`);
    
    todos.value = allTodos;
    console.log('📋 Final todos array:', todos.value.length);
    

  } catch (error) {
    console.error('❌ Error loading todos:', error);
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

// Helper to submit all pending bulk todos (top-level so template can call)
async function onSubmitBulkTodos() {
  if (!currentProject.value) return;
  isSubmittingBulk.value = true;
  try {
    for (const p of pendingBulkTodos.value) {
      try {
        if (p.parent_task_id) {
          await todoApi.createSubtask(p.parent_task_id, {
            ...p,
            project_id: currentProject.value.id,
            status: 'todo',
          } as any);
        } else {
          await todoApi.createTodo({
            ...p,
            project_id: currentProject.value.id,
            status: 'todo',
          } as any);
        }
      } catch (e) {
        console.error('Failed to create in bulk', e);
      }
    }
    pendingBulkTodos.value = [];
    isBulkMode.value = false;
    await loadTodos();
    if ((window as any).$notify) {
      (window as any).$notify({ type: 'success', title: 'Bulk Added', message: 'All pending todos created.' });
    }
  } finally {
    isSubmittingBulk.value = false;
  }
}

// Single onMounted hook to handle all initialization
onMounted(async () => {
  // In read-only mode, use passed data instead of making API calls
  if (props.isReadOnly) {
    if (props.projects && props.projects.length > 0) {
      projects.value = props.projects;
      if (props.selectedProject) {
        currentProject.value = props.selectedProject;
        selectedProjectId.value = props.selectedProject.id.toString();
      } else {
        currentProject.value = props.projects[0];
        selectedProjectId.value = props.projects[0].id.toString();
      }
      
      // Filter todos for the selected project
      if (props.todos && props.todos.length > 0) {
        todos.value = props.todos.filter(todo => todo.project_id === currentProject.value?.id);
      }
    }
    return;
  }
  
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
  
  // Initialize voice recording
  if (!props.isReadOnly) {
    initVoiceRecording();
  }
  
  // Subscribe to real-time todo updates (allow in read-only mode for viewing)
  // if (!props.isReadOnly) {
  
  console.log('🔥 Setting up real-time todo subscription...');
  unsubscribeFromTodos = realtimeService.onTodo(async (event) => {
    console.log('🔥 Real-time todo event received:', event.type, event.data);
    
    switch (event.type) {
      case 'todo_created':
        // Add new todo to the list if it belongs to current project
        const newTodo = event.data;
        console.log('🔥 Processing todo_created:', newTodo);
        console.log('🔥 New todo parent_task_id:', newTodo.parent_task_id);
        
        if (!currentProject.value || newTodo.project_id === currentProject.value.id) {
          // Check if this is a subtask
          if (newTodo.parent_task_id) {
            // Find the parent todo and add this subtask to its subtasks array
            const parentIndex = todos.value.findIndex(t => t.id === newTodo.parent_task_id);
            if (parentIndex !== -1) {
              const parentTodo = todos.value[parentIndex];
              // Initialize subtasks array if it doesn't exist
              if (!parentTodo.subtasks) {
                parentTodo.subtasks = [];
              }
              
              // Check if subtask already exists
              const existingSubtaskIndex = parentTodo.subtasks.findIndex((st: any) => st.id === newTodo.id);
              if (existingSubtaskIndex === -1) {
                // Ensure the new subtask has the project relationship
                const subtaskWithProject = {
                  ...newTodo,
                  project: currentProject.value
                };
                parentTodo.subtasks.push(subtaskWithProject);
                console.log('🔥 Added subtask to parent:', parentTodo.title, 'Subtask:', newTodo.title);
                // Force reactivity update
                todos.value = [...todos.value];
              } else {
                console.log('🔥 Subtask already exists in parent, skipping');
              }
            } else {
              console.log('🔥 Parent todo not found for subtask, adding to main list temporarily');
              // Parent not found yet, add to main list (it will be moved when parent loads)
              const todoWithProject = {
                ...newTodo,
                project: currentProject.value
              };
              todos.value.push(todoWithProject);
            }
          } else {
            // Regular todo (not a subtask)
            if (!todos.value.find(t => t.id === newTodo.id)) {
              // Ensure the new todo has the project relationship
              const todoWithProject = {
                ...newTodo,
                project: currentProject.value,
                subtasks: [] // Initialize subtasks array
              };
              console.log('🔥 Adding new todo to list:', todoWithProject);
              todos.value.push(todoWithProject);
              console.log('🔥 Updated todos list:', todos.value.length, 'todos');
            } else {
              console.log('🔥 Todo already exists, skipping');
            }
          }
        } else {
          console.log('🔥 Todo not for current project, skipping');
        }
        break;
        
      case 'todo_updated':
        // Update existing todo in the list
        const updatedTodo = event.data;
        console.log('🔥 Processing todo_updated:', updatedTodo);
        console.log('🔥 Updated todo parent_task_id:', updatedTodo.parent_task_id);
        
        // Check if this is a subtask
        if (updatedTodo.parent_task_id) {
          // Find the parent todo and update the subtask in its subtasks array
          const parentIndex = todos.value.findIndex(t => t.id === updatedTodo.parent_task_id);
          if (parentIndex !== -1) {
            const parentTodo = todos.value[parentIndex];
            if (parentTodo.subtasks) {
              const subtaskIndex = parentTodo.subtasks.findIndex((st: any) => st.id === updatedTodo.id);
              if (subtaskIndex !== -1) {
                // Preserve the project relationship
                const existingSubtask = parentTodo.subtasks[subtaskIndex];
                const subtaskWithProject = {
                  ...updatedTodo,
                  project: existingSubtask.project || currentProject.value
                };
                parentTodo.subtasks[subtaskIndex] = subtaskWithProject;
                console.log('🔥 Updated subtask in parent:', parentTodo.title, 'Subtask:', updatedTodo.title);
                // Force reactivity update
                todos.value = [...todos.value];
              } else {
                console.log('🔥 Subtask not found in parent for update, skipping');
              }
            }
          } else {
            console.log('🔥 Parent todo not found for subtask update, trying main list');
            // Try to update in main list if parent not found
            const updateIndex = todos.value.findIndex(t => t.id === updatedTodo.id);
            if (updateIndex !== -1) {
              const existingTodo = todos.value[updateIndex];
              const todoWithProject = {
                ...updatedTodo,
                project: existingTodo.project || currentProject.value
              };
              todos.value[updateIndex] = todoWithProject;
            }
          }
        } else {
          // Regular todo (not a subtask)
          const updateIndex = todos.value.findIndex(t => t.id === updatedTodo.id);
          if (updateIndex !== -1) {
            // Preserve the project relationship from the existing todo
            const existingTodo = todos.value[updateIndex];
            const todoWithProject = {
              ...updatedTodo,
              project: existingTodo.project || currentProject.value,
              subtasks: existingTodo.subtasks || [] // Preserve subtasks
            };
            console.log('🔥 Updating todo at index:', updateIndex, todoWithProject);
            todos.value[updateIndex] = todoWithProject;
            console.log('🔥 Updated todos list:', todos.value.length, 'todos');
          } else {
            console.log('🔥 Todo not found for update, skipping');
          }
        }
        break;
        
      case 'todo_deleted':
        // Remove todo from the list
        const deletedTodo = event.data;
        console.log('🔥 Processing todo_deleted:', deletedTodo);
        console.log('🔥 Deleted todo parent_task_id:', deletedTodo.parent_task_id);
        
        // Check if this is a subtask
        if (deletedTodo.parent_task_id) {
          // Find the parent todo and remove the subtask from its subtasks array
          const parentIndex = todos.value.findIndex(t => t.id === deletedTodo.parent_task_id);
          if (parentIndex !== -1) {
            const parentTodo = todos.value[parentIndex];
            if (parentTodo.subtasks) {
              const subtaskIndex = parentTodo.subtasks.findIndex((st: any) => st.id === deletedTodo.id);
              if (subtaskIndex !== -1) {
                parentTodo.subtasks.splice(subtaskIndex, 1);
                console.log('🔥 Removed subtask from parent:', parentTodo.title, 'Subtask ID:', deletedTodo.id);
                // Force reactivity update
                todos.value = [...todos.value];
                
                // Dispatch event to refresh sidebar project stats
                window.dispatchEvent(new CustomEvent('todoChanged'));
              } else {
                console.log('🔥 Subtask not found in parent for deletion, skipping');
              }
            }
          } else {
            console.log('🔥 Parent todo not found for subtask deletion, trying main list');
            // Try to delete from main list if parent not found
            const deleteIndex = todos.value.findIndex(t => t.id === deletedTodo.id);
            if (deleteIndex !== -1) {
              todos.value.splice(deleteIndex, 1);
              todos.value = [...todos.value];
              window.dispatchEvent(new CustomEvent('todoChanged'));
            }
          }
        } else {
          // Regular todo (not a subtask)
          const deleteIndex = todos.value.findIndex(t => t.id === deletedTodo.id);
          if (deleteIndex !== -1) {
            console.log('🔥 Removing todo at index:', deleteIndex);
            todos.value.splice(deleteIndex, 1);
            // Force reactivity update to ensure UI changes immediately
            todos.value = [...todos.value];
            console.log('🔥 Updated todos list:', todos.value.length, 'todos');
            
            // Dispatch event to refresh sidebar project stats
            window.dispatchEvent(new CustomEvent('todoChanged'));
          } else {
            console.log('🔥 Todo not found for deletion, skipping');
          }
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
  // }

  // Load saved views
  loadSavedViews();

  // Listen for openTodoById dispatched by notifications
  const handleOpenTodoById = async (e: any) => {
    try {
      const id = e?.detail?.todoId;
      if (!id) return;
      if (todos.value.length === 0) {
        await loadTodos();
      }
      let todo = todos.value.find(t => t.id === id) as unknown as Todo | undefined;
      if (!todo) {
        const fetched = await todoApi.getTodo(id);
        todo = fetched as unknown as Todo;
      }
      if (todo) {
        await editTodo(todo as unknown as Todo);
        const highlight = e?.detail?.highlight || null;
        if (highlight) {
          window.dispatchEvent(new CustomEvent('highlightComment', { detail: { commentId: highlight } }));
        }
      }
    } catch (err) {
      console.error('Failed to open todo by id:', err);
    }
  };
  window.addEventListener('openTodoById', handleOpenTodoById);
});

// Cleanup on unmount
onUnmounted(() => {
  if (unsubscribeFromTodos) {
    unsubscribeFromTodos();
  }
  // Clean up voice recording timers
  stopTimer();
  stopPreRecordingCountdown();
  // Stop recording if active
  if (recognition.value && isRecording.value) {
    recognition.value.stop();
  }
  // Remove listeners
  window.removeEventListener('openTodoById', (e: any) => {});
});

</script>
