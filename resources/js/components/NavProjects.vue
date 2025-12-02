<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { SidebarGroup, SidebarGroupLabel, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { todoApi, type Project } from '@/services/todoApi';
import { realtimeService } from '@/services/realtimeService';
import Icon from '@/components/Icon.vue';
import { useI18n } from 'vue-i18n';
const { t } = useI18n();
// import { usePage } from '@inertiajs/vue3';
const projects = ref<Project[]>([]);
const groupedProjects = ref<any>(null);
const activeClientId = ref<number | null>(null);
const todosLoaded = ref(false);
const currentProject = ref<Project | null>(null);
const loading = ref(false);
const isUpdatingProject = ref(false); // Flag to prevent circular events
const collapsedClients = ref<Set<string>>(new Set()); // Track collapsed client sections
let unsubscribeFromProjects: (() => void) | null = null;

// Check if we're on the subscription page
const isOnSubscriptionPage = computed(() => {
  return window.location.pathname === '/subscription';
});

// Check if we're on the main dashboard
const isOnDashboard = computed(() => {
  return window.location.pathname === '/dashboard';
});

// Load projects on mount
onMounted(async () => {
  await loadProjects();
  
  // Subscribe to real-time project updates
  unsubscribeFromProjects = realtimeService.onProject(async (event) => {
    console.log('Real-time project event received:', event);
    
    switch (event.type) {
      case 'project_created':
        // Add new project to the list
        const newProject = event.data;
        if (!projects.value.find(p => p.id === newProject.id)) {
          projects.value.push(newProject);
          console.log('Added new project to list:', newProject.name);
        }
        break;
        
      case 'project_updated':
        // Update existing project in the list
        const updatedProject = event.data;
        const updateIndex = projects.value.findIndex(p => p.id === updatedProject.id);
        if (updateIndex !== -1) {
          projects.value[updateIndex] = updatedProject;
          console.log('Updated project in list:', updatedProject.name);
        }
        break;
        
      case 'project_deleted':
        // Remove project from the list
        const deletedProject = event.data;
        const deleteIndex = projects.value.findIndex(p => p.id === deletedProject.id);
        if (deleteIndex !== -1) {
          projects.value.splice(deleteIndex, 1);
          console.log('Removed project from list:', deletedProject.name);
        }
        break;
    }
  });
  
  // Listen for project creation events to refresh the list
  window.addEventListener('projectCreated', async () => {
    await loadProjects();
  });
  
  // Listen for todo changes to update project stats
  window.addEventListener('todoChanged', async () => {
    await loadProjects();
  });
  
  // Listen for project selection events from the dropdown
  window.addEventListener('projectSelected', async (e: any) => {
    if (e.detail?.projectId && !isUpdatingProject.value) {
      isUpdatingProject.value = true;
      try {
        const project = projects.value.find(p => p.id === e.detail.projectId);
        if (project) {
          currentProject.value = project;
          console.log('Sidebar updated to show selected project:', project.name);
        }
      } finally {
        isUpdatingProject.value = false;
      }
    }
  });
  
  // Listen for client filter changes (from Company page or other UIs)
  window.addEventListener('clientFilterChanged', (e: any) => {
    const clientId = e.detail?.clientId ?? null;
    activeClientId.value = clientId;
  });
  
  // Listen for subscription downgrades to reload projects list
  window.addEventListener('subscription-downgrade', async (e: any) => {
    console.log('Subscription downgrade detected, reloading projects:', e.detail);
    await loadProjects();
  });
  
  // Listen for todos fully loaded (from TodoBoard)
  window.addEventListener('todosLoaded', () => {
    todosLoaded.value = true;
  });
});

// Cleanup on unmount
onUnmounted(() => {
  if (unsubscribeFromProjects) {
    unsubscribeFromProjects();
  }
});

const loadProjects = async () => {
  try {
    loading.value = true;
    const response = await todoApi.getProjectsWithStats();
    console.log('Projects with stats:', response);
    
    // Handle both flat array and object with data property
    if (Array.isArray(response)) {
      projects.value = response;
      groupedProjects.value = null; // No grouped data available
    } else if (response.data && Array.isArray(response.data)) {
      projects.value = response.data;
      groupedProjects.value = response.grouped || null;
    } else {
      projects.value = [];
      groupedProjects.value = null;
    }
    
    // Set current project from URL or localStorage
    const projectId = localStorage.getItem('currentProjectId');
    if (projectId) {
      const project = projects.value.find(p => p.id === parseInt(projectId));
      if (project) {
        currentProject.value = project;
      }
    }
    

  } catch (error) {
    console.error('Failed to load projects:', error);
  } finally {
    loading.value = false;
  }
};

// Computed grouped projects respecting active client filter
const visibleGroupedProjects = computed(() => {
  if (!groupedProjects.value) {
    return null;
  }

  // No active client filter → show all groups
  if (!activeClientId.value) {
    return groupedProjects.value;
  }

  const clientsGroup = groupedProjects.value.clients || {};
  const target = Object.values(clientsGroup).find(
    (c: any) => c.id === activeClientId.value
  ) as any | undefined;

  if (!target) {
    // No matching client; show no projects
    return {
      no_client: [],
      clients: {},
    };
  }

  return {
    no_client: [],
    clients: {
      [target.id]: target,
    },
  };
});

const selectProject = (project: Project) => {
  currentProject.value = project;
  localStorage.setItem('currentProjectId', project.id.toString());
  
  // Dispatch custom event for TodoBoard to listen to
  window.dispatchEvent(new CustomEvent('projectSelected', {
    detail: { projectId: project.id }
  }));
  

  
  // Navigate to dashboard with project context
  window.location.href = '/dashboard';
};

const deleteProject = async (project: Project, event: Event) => {
  event.stopPropagation();
  
  const todoCount = project.total_todos || 0;
  
  if (todoCount > 0) {
    // Project has todos - ask user what to do
    const choice = confirm(
      `Project "${project.name}" contains ${todoCount} todos.\n\n` +
      `Choose:\n` +
      `- OK: Delete project AND all todos\n` +
      `- Cancel: Keep project and todos`
    );
    
    if (choice) {
      // User chose to delete everything
      if (confirm(`⚠️ FINAL WARNING: This will permanently delete:\n` +
        `• ${t('dashboard.project')}: "${project.name}"\n` +
        `• ${todoCount} todos\n\n` +
        `This action CANNOT be undone. Are you absolutely sure?`)) {
        
        try {
          await todoApi.deleteProjectWithTodos(project.id);
          
          // Remove from local list and auto-select next project
          const index = projects.value.findIndex(p => p.id === project.id);
          if (index !== -1) {
            projects.value.splice(index, 1);
          }
          
          // If this was the current project, auto-select the next project in order
          if (currentProject.value?.id === project.id) {
            let nextProject = null;
            
            if (projects.value.length > 0) {
              // Try to select the project at the same index position
              if (index < projects.value.length) {
                nextProject = projects.value[index];
              } else if (index > 0) {
                // If we deleted the last project, select the previous one
                nextProject = projects.value[index - 1];
              } else {
                // If only one project left, select it
                nextProject = projects.value[0];
              }
            }
            
            if (nextProject) {
              currentProject.value = nextProject;
              localStorage.setItem('currentProjectId', nextProject.id.toString());
              
              console.log(`Auto-selected next project: ${nextProject.name} (ID: ${nextProject.id})`);
              console.log('Projects remaining after deletion:', projects.value.map(p => ({ id: p.id, name: p.name })));
              console.log('Updated localStorage to project ID:', nextProject.id.toString());
              
              // Dispatch event to update TodoBoard
              window.dispatchEvent(new CustomEvent('projectSelected', {
                detail: { projectId: nextProject.id }
              }));
            } else {
              // No projects left
              currentProject.value = null;
              localStorage.removeItem('currentProjectId');
              
              console.log('No projects left after deletion');
              
              // Dispatch event to clear TodoBoard
              window.dispatchEvent(new CustomEvent('projectSelected', {
                detail: { projectId: null }
              }));
            }
          }
          
          console.log(`Project "${project.name}" and ${todoCount} todos deleted successfully`);
          
          // Dispatch event to refresh sidebar project stats and TodoBoard
          window.dispatchEvent(new CustomEvent('todoChanged'));
          window.dispatchEvent(new CustomEvent('projectListChanged'));
          
          if ((window as any).$notify) {
            (window as any).$notify({
              type: 'success',
              title: 'Project Deleted',
              message: `Project "${project.name}" and ${todoCount} todos have been deleted successfully.`
            });
          }
        } catch (error) {
          console.error('Failed to delete project with todos:', error);
          if ((window as any).$notify) {
            (window as any).$notify({
              type: 'error',
              title: 'Delete Failed',
              message: 'Failed to delete project. Please try again.'
            });
          }
        }
      }
    }
  } else {
    // Project has no todos - simple deletion
    if (confirm(`Are you sure you want to delete "${project.name}"? This action cannot be undone.`)) {
      try {
        await todoApi.deleteProject(project.id);
        
        // Remove from local list and auto-select next project
        const index = projects.value.findIndex(p => p.id === project.id);
        if (index !== -1) {
          projects.value.splice(index, 1);
        }
        
        // If this was the current project, auto-select the next project in order
        if (currentProject.value?.id === project.id) {
          let nextProject = null;
          
          if (projects.value.length > 0) {
            // Try to select the project at the same index position
            if (index < projects.value.length) {
              nextProject = projects.value[index];
            } else if (index > 0) {
              // If we deleted the last project, select the previous one
              nextProject = projects.value[index - 1];
            } else {
              // If only one project left, select it
              nextProject = projects.value[0];
            }
          }
          
          if (nextProject) {
            currentProject.value = nextProject;
            localStorage.setItem('currentProjectId', nextProject.id.toString());
            
            console.log(`Auto-selected next project: ${nextProject.name} (ID: ${nextProject.id})`);
            console.log('Projects remaining after deletion:', projects.value.map(p => ({ id: p.id, name: p.name })));
            console.log('Updated localStorage to project ID:', nextProject.id.toString());
            
            // Dispatch event to update TodoBoard
            window.dispatchEvent(new CustomEvent('projectSelected', {
              detail: { projectId: nextProject.id }
            }));
          } else {
            // No projects left
            currentProject.value = null;
            localStorage.removeItem('currentProjectId');
            
            console.log('No projects left after deletion');
            
            // Dispatch event to clear TodoBoard
            window.dispatchEvent(new CustomEvent('projectSelected', {
              detail: { projectId: null }
            }));
          }
        }
        
                  console.log(`Project "${project.name}" deleted successfully`);
          
          // Dispatch event to refresh sidebar project stats and TodoBoard
          window.dispatchEvent(new CustomEvent('todoChanged'));
          window.dispatchEvent(new CustomEvent('projectListChanged'));
          
          if ((window as any).$notify) {
            (window as any).$notify({
              type: 'success',
              title: 'Project Deleted',
              message: `Project "${project.name}" has been deleted successfully.`
            });
          }
        } catch (error) {
          console.error('Failed to delete project:', error);
          if ((window as any).$notify) {
            (window as any).$notify({
              type: 'error',
              title: 'Delete Failed',
              message: 'Failed to delete project. Please try again.'
            });
          }
        }
    }
  }
};

const createProject = () => {
  // Don't allow project creation on subscription page
  if (isOnSubscriptionPage.value) {
    if ((window as any).$notify) {
      (window as any).$notify({
        type: 'warning',
        title: 'Project Creation Unavailable',
        message: 'Please complete your subscription upgrade to create new projects.'
      });
    }
    return;
  }
  
  // Dispatch event to open project creation modal in TodoBoard
  window.dispatchEvent(new CustomEvent('openCreateProjectModal'));
};

// Drag and drop functionality
const draggedProject = ref<Project | null>(null);
const draggedOverIndex = ref<number | null>(null);

const handleDragStart = (event: DragEvent, project: Project) => {
  draggedProject.value = project;
  if (event.dataTransfer) {
    event.dataTransfer.effectAllowed = 'move';
    event.dataTransfer.setData('text/plain', project.id.toString());
  }
};

const handleDragOver = (event: DragEvent, index: number) => {
  event.preventDefault();
  if (event.dataTransfer) {
    event.dataTransfer.dropEffect = 'move';
  }
  draggedOverIndex.value = index;
};

const handleDragLeave = () => {
  draggedOverIndex.value = null;
};

const handleDrop = async (event: DragEvent, dropIndex: number) => {
  event.preventDefault();
  draggedOverIndex.value = null;
  
  if (!draggedProject.value) return;
  
  const dragIndex = projects.value.findIndex(p => p.id === draggedProject.value!.id);
  if (dragIndex === -1 || dragIndex === dropIndex) {
    draggedProject.value = null;
    return;
  }
  
  // Reorder the projects array
  const reorderedProjects = [...projects.value];
  const [draggedItem] = reorderedProjects.splice(dragIndex, 1);
  reorderedProjects.splice(dropIndex, 0, draggedItem);
  
  // Update viewing_order for all projects
  const projectOrders = reorderedProjects.map((project, index) => ({
    id: project.id,
    viewing_order: index + 1
  }));
  
  try {
    await todoApi.updateProjectOrder(projectOrders);
    projects.value = reorderedProjects;
    
    if ((window as any).$notify) {
      (window as any).$notify({
        type: 'success',
        title: 'Project Order Updated',
        message: 'Projects have been reordered successfully.'
      });
    }
  } catch (error) {
    console.error('Failed to update project order:', error);
    if ((window as any).$notify) {
      (window as any).$notify({
        type: 'error',
        title: 'Update Failed',
        message: 'Failed to reorder projects. Please try again.'
      });
    }
  }
  
  draggedProject.value = null;
};

const handleDragEnd = () => {
  draggedProject.value = null;
  draggedOverIndex.value = null;
};

const toggleClientCollapse = (clientName: string) => {
  if (collapsedClients.value.has(clientName)) {
    collapsedClients.value.delete(clientName);
  } else {
    collapsedClients.value.add(clientName);
  }
};

const isClientCollapsed = (clientName: string) => {
  return collapsedClients.value.has(clientName);
};


</script>

<template>
  <SidebarGroup class="px-2 py-0">
    <SidebarGroupLabel>Projects</SidebarGroupLabel>
    <SidebarMenu>
      <!-- Create Project Button -->
      <SidebarMenuItem v-if="!loading && isOnDashboard && todosLoaded">
        <SidebarMenuButton 
          @click="createProject" 
          :tooltip="isOnSubscriptionPage ? 'Complete subscription upgrade to create projects' : 'Create New Project'"
          :class="[
            'justify-between rounded-md border text-sm font-medium transition-colors',
            'bg-black text-white hover:bg-gray-900 hover:border-gray-900',
            'dark:bg-white dark:text-black dark:hover:bg-gray-100 dark:hover:border-gray-300',
            { 'opacity-50 cursor-not-allowed': isOnSubscriptionPage }
          ]"
        >
          <div class="flex items-center gap-2">
            <Icon name="Plus" class="w-4 h-4" />
            <span>New Project</span>
          </div>
        </SidebarMenuButton>
      </SidebarMenuItem>
      
      <!-- Grouped Project List (with clients) -->
      <template v-if="visibleGroupedProjects">
        <!-- Projects without clients -->
        <template v-if="visibleGroupedProjects.no_client && visibleGroupedProjects.no_client.length > 0">
          <SidebarMenuItem>
            <div class="px-2 py-1 text-xs font-medium text-gray-500 dark:text-gray-400">No Client</div>
          </SidebarMenuItem>
            <SidebarMenuItem 
            v-for="(project, index) in visibleGroupedProjects.no_client" 
            :key="`no-client-${project.id}`"
            @dragover="handleDragOver($event, index)"
            @dragleave="handleDragLeave"
            @drop="handleDrop($event, index)"
            :class="{ 'drag-over': draggedOverIndex === index }"
          >
            <SidebarMenuButton 
              @click="selectProject(project)" 
              :is-active="currentProject?.id === project.id"
              :tooltip="project.name"
              class="group relative ml-2"
              :class="{ 'selected-project': currentProject?.id === project.id }"
              :style="currentProject?.id === project.id ? {
                borderTop: `1px solid ${project.color}`,
                borderLeft: `1px solid ${project.color}`,
                borderBottom: `1px solid ${project.color}`,
                borderRight: 'none',
                marginRight: '-2px',
                borderRadius: '15px 0px 0px 15px'
              } : {}"
            >
              <div class="flex items-center gap-2 w-full">
                <div 
                  class="w-3 h-3 rounded-full flex-shrink-0 cursor-move hover:scale-110 transition-transform"
                  :style="{ backgroundColor: project.color }"
                  draggable="true"
                  @dragstart="handleDragStart($event, project)"
                  @dragend="handleDragEnd"
                  title="Drag to reorder"
                ></div>
                <span class="flex-1 truncate">{{ project.name }}</span>
                <span class="text-xs text-gray-500 flex-shrink-0">{{ project.total_todos || 0 }}</span>
                <div class="reveal-on-hover-focus">
                  <button
                    @touchstart.stop
                    @click="deleteProject(project, $event)"
                    class="text-gray-400 hover:text-red-500 transition-colors"
                    :tooltip="`Delete ${project.name}`"
                  >
                    <Icon name="Trash2" class="w-3 h-3" />
                  </button>
                </div>
              </div>
            </SidebarMenuButton>
          </SidebarMenuItem>
        </template>

        <!-- Projects grouped by clients -->
        <template v-for="client in Object.values(visibleGroupedProjects.clients)" :key="`client-${client.id}`">
          <SidebarMenuItem>
            <SidebarMenuButton 
              @click="toggleClientCollapse(client.name)"
              class="text-xs font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800"
            >
              <Icon 
                :name="isClientCollapsed(client.name) ? 'ChevronRight' : 'ChevronDown'" 
                class="w-3 h-3" 
              />
              <span class="flex-1 truncate">{{ client.name }}</span>
              <span class="text-xs text-gray-500">{{ client.projects.length }}</span>
            </SidebarMenuButton>
          </SidebarMenuItem>
          
          <!-- Client's projects (collapsible) -->
          <template v-if="!isClientCollapsed(client.name)">
            <SidebarMenuItem 
              v-for="(project, index) in client.projects" 
              :key="`client-${client.id}-project-${project.id}`"
              @dragover="handleDragOver($event, index)"
              @dragleave="handleDragLeave"
              @drop="handleDrop($event, index)"
              :class="{ 'drag-over': draggedOverIndex === index }"
            >
              <SidebarMenuButton 
                @click="selectProject(project)" 
                :is-active="currentProject?.id === project.id"
                :tooltip="project.name"
                class="group relative ml-4"
                :class="{ 'selected-project': currentProject?.id === project.id }"
                :style="currentProject?.id === project.id ? {
                  borderTop: `1px solid ${project.color}`,
                  borderLeft: `1px solid ${project.color}`,
                  borderBottom: `1px solid ${project.color}`,
                  borderRight: 'none',
                  marginRight: '-2px',
                  borderRadius: '15px 0px 0px 15px'
                } : {}"
              >
                <div class="flex items-center gap-2 w-full">
                  <div 
                    class="w-3 h-3 rounded-full flex-shrink-0 cursor-move hover:scale-110 transition-transform"
                    :style="{ backgroundColor: project.color }"
                    draggable="true"
                    @dragstart="handleDragStart($event, project)"
                    @dragend="handleDragEnd"
                    title="Drag to reorder"
                  ></div>
                  <span class="flex-1 truncate">{{ project.name }}</span>
                  <span class="text-xs text-gray-500 flex-shrink-0">{{ project.total_todos || 0 }}</span>
                  <div class="reveal-on-hover-focus">
                    <button
                      @touchstart.stop
                      @click="deleteProject(project, $event)"
                      class="text-gray-400 hover:text-red-500 transition-colors"
                      :tooltip="`Delete ${project.name}`"
                    >
                      <Icon name="Trash2" class="w-3 h-3" />
                    </button>
                  </div>
                </div>
              </SidebarMenuButton>
            </SidebarMenuItem>
          </template>
        </template>
      </template>
      
      <!-- Fallback: Flat Project List (when no grouping available) -->
      <template v-else>
        <SidebarMenuItem 
          v-for="(project, index) in projects" 
          :key="project.id"
          @dragover="handleDragOver($event, index)"
          @dragleave="handleDragLeave"
          @drop="handleDrop($event, index)"
          :class="{ 'drag-over': draggedOverIndex === index }"
        >
          <SidebarMenuButton 
            @click="selectProject(project)" 
            :is-active="currentProject?.id === project.id"
            :tooltip="project.name"
            class="group relative"
            :class="{ 'selected-project': currentProject?.id === project.id }"
            :style="currentProject?.id === project.id ? {
              borderTop: `1px solid ${project.color}`,
              borderLeft: `1px solid ${project.color}`,
              borderBottom: `1px solid ${project.color}`,
              borderRight: 'none',
              marginRight: '-2px',
              borderRadius: '15px 0px 0px 15px'
            } : {}"
          >
            <div class="flex items-center gap-2 w-full">
              <div 
                class="w-3 h-3 rounded-full flex-shrink-0 cursor-move hover:scale-110 transition-transform"
                :style="{ backgroundColor: project.color }"
                draggable="true"
                @dragstart="handleDragStart($event, project)"
                @dragend="handleDragEnd"
                title="Drag to reorder"
              ></div>
              <span class="flex-1 truncate">{{ project.name }}</span>
              <span class="text-xs text-gray-500 flex-shrink-0">{{ project.total_todos || 0 }}</span>
              <div class="reveal-on-hover-focus">
                <button
                  @touchstart.stop
                  @click="deleteProject(project, $event)"
                  class="text-gray-400 hover:text-red-500 transition-colors"
                  :tooltip="`Delete ${project.name}`"
                >
                  <Icon name="Trash2" class="w-3 h-3" />
                </button>
              </div>
            </div>
          </SidebarMenuButton>
        </SidebarMenuItem>
      </template>
      
      <!-- Loading State -->
      <SidebarMenuItem v-if="loading">
        <SidebarMenuButton disabled>
          <Icon name="Loader2" class="w-4 h-4 animate-spin" />
          <span>Loading...</span>
        </SidebarMenuButton>
      </SidebarMenuItem>
      

    </SidebarMenu>
  </SidebarGroup>
</template>

<style scoped>
.selected-project {
  position: relative;
  z-index: 10;
}

/* Ensure the selected project button extends to the edge */
.selected-project::after {
  content: '';
  position: absolute;
  right: -2px;
  top: 0;
  bottom: 0;
  width: 2px;
  background-color: inherit;
  border-color: inherit;
}

/* Custom styles for project items */
.group:hover .opacity-0 {
  opacity: 1;
}

/* Drag and drop styles */
.drag-over {
  background-color: rgba(59, 130, 246, 0.1);
  border-radius: 8px;
  transition: background-color 0.2s ease;
}

/* Reveal delete icon on hover only on devices that support hover, and on focus for accessibility/touch */
.reveal-on-hover-focus {
  opacity: 1;
  transition: opacity 0.2s ease;
}

@media (hover: hover) and (pointer: fine) {
  .group .reveal-on-hover-focus {
    opacity: 0;
  }
  .group:hover .reveal-on-hover-focus,
  .group:focus-within .reveal-on-hover-focus {
    opacity: 1;
  }
}
</style>
