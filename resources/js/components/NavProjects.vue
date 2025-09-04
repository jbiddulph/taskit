<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { SidebarGroup, SidebarGroupLabel, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { todoApi, type Project } from '@/services/todoApi';
import Icon from '@/components/Icon.vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const projects = ref<Project[]>([]);
const currentProject = ref<Project | null>(null);
const loading = ref(false);
const isUpdatingProject = ref(false); // Flag to prevent circular events

// Load projects on mount
onMounted(async () => {
  await loadProjects();
  
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
});

const loadProjects = async () => {
  try {
    loading.value = true;
    const projectsData = await todoApi.getProjectsWithStats();
    console.log('Projects with stats:', projectsData);
    projects.value = projectsData;
    
    // Set current project from URL or localStorage
    const projectId = localStorage.getItem('currentProjectId');
    if (projectId) {
      const project = projectsData.find(p => p.id === parseInt(projectId));
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
        `• Project: "${project.name}"\n` +
        `• ${todoCount} todos\n\n` +
        `This action CANNOT be undone. Are you absolutely sure?`)) {
        
        try {
          await todoApi.deleteProjectWithTodos(project.id);
          
          // Remove from local list
          const index = projects.value.findIndex(p => p.id === project.id);
          if (index !== -1) {
            projects.value.splice(index, 1);
          }
          
          // If this was the current project, clear it
          if (currentProject.value?.id === project.id) {
            currentProject.value = null;
            localStorage.removeItem('currentProjectId');
          }
          
          console.log(`Project "${project.name}" and ${todoCount} todos deleted successfully`);
          
          // Dispatch event to refresh sidebar project stats
          window.dispatchEvent(new CustomEvent('todoChanged'));
          
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
        
        // Remove from local list
        const index = projects.value.findIndex(p => p.id === project.id);
        if (index !== -1) {
          projects.value.splice(index, 1);
        }
        
        // If this was the current project, clear it
        if (currentProject.value?.id === project.id) {
          currentProject.value = null;
          localStorage.removeItem('currentProjectId');
        }
        
                  console.log(`Project "${project.name}" deleted successfully`);
          
          // Dispatch event to refresh sidebar project stats
          window.dispatchEvent(new CustomEvent('todoChanged'));
          
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


</script>

<template>
  <SidebarGroup class="px-2 py-0">
    <SidebarGroupLabel>Projects</SidebarGroupLabel>
    <SidebarMenu>
      <!-- Create Project Button -->
      <SidebarMenuItem>
        <SidebarMenuButton @click="createProject" :tooltip="'Create New Project'">
          <Icon name="Plus" class="w-4 h-4" />
          <span>New Project</span>
        </SidebarMenuButton>
      </SidebarMenuItem>
      
      <!-- Project List -->
      <SidebarMenuItem 
        v-for="(project, index) in projects" 
        :key="project.id"
        @dragover="handleDragOver($event, index)"
        @dragleave="handleDragLeave"
        @drop="handleDrop($event, index)"
        :class="{
          'drag-over': draggedOverIndex === index
        }"
      >
        <SidebarMenuButton 
          @click="selectProject(project)" 
          :is-active="currentProject?.id === project.id"
          :tooltip="project.name"
          class="group relative"
          :class="{
            'selected-project': currentProject?.id === project.id
          }"
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
            
            <!-- Delete Button (only visible on hover) -->
            <div class="opacity-0 group-hover:opacity-100 transition-opacity">
              <button
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
</style>
