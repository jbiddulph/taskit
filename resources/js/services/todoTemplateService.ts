import { ref } from 'vue';

export interface TodoTemplate {
  id: number;
  name: string;
  description: string;
  category: string;
  todos: TodoTemplateItem[];
  created_by: number;
  company_id: number;
  is_public: boolean;
  usage_count: number;
  created_at: string;
  updated_at: string;
}

export interface TodoTemplateItem {
  title: string;
  description: string;
  priority: 'Low' | 'Medium' | 'High' | 'Critical';
  type: 'Bug' | 'Feature' | 'Task' | 'Story' | 'Epic';
  estimated_hours?: number;
  tags: string[];
  subtasks?: TodoTemplateItem[];
}

class TodoTemplateService {
  private templates = ref<TodoTemplate[]>([]);

  // Get all templates for a company
  getTemplates(companyId: number): TodoTemplate[] {
    return this.templates.value.filter(t => t.company_id === companyId);
  }

  // Get public templates
  getPublicTemplates(): TodoTemplate[] {
    return this.templates.value.filter(t => t.is_public);
  }

  // Get templates by category
  getTemplatesByCategory(companyId: number, category: string): TodoTemplate[] {
    return this.templates.value.filter(t => 
      t.company_id === companyId && t.category === category
    );
  }

  // Create a new template
  async createTemplate(template: Omit<TodoTemplate, 'id' | 'created_at' | 'updated_at' | 'usage_count'>): Promise<TodoTemplate> {
    const newTemplate: TodoTemplate = {
      ...template,
      id: Date.now(), // In real app, this would come from API
      usage_count: 0,
      created_at: new Date().toISOString(),
      updated_at: new Date().toISOString()
    };

    this.templates.value.push(newTemplate);
    return newTemplate;
  }

  // Update a template
  async updateTemplate(id: number, updates: Partial<TodoTemplate>): Promise<TodoTemplate | null> {
    const index = this.templates.value.findIndex(t => t.id === id);
    if (index === -1) return null;

    this.templates.value[index] = {
      ...this.templates.value[index],
      ...updates,
      updated_at: new Date().toISOString()
    };

    return this.templates.value[index];
  }

  // Delete a template
  async deleteTemplate(id: number): Promise<boolean> {
    const index = this.templates.value.findIndex(t => t.id === id);
    if (index === -1) return false;

    this.templates.value.splice(index, 1);
    return true;
  }

  // Use a template (increment usage count)
  async useTemplate(id: number): Promise<TodoTemplate | null> {
    const template = this.templates.value.find(t => t.id === id);
    if (!template) return null;

    template.usage_count++;
    return template;
  }

  // Get template categories
  getCategories(companyId: number): string[] {
    const categories = new Set<string>();
    this.templates.value
      .filter(t => t.company_id === companyId)
      .forEach(t => categories.add(t.category));
    
    return Array.from(categories).sort();
  }

  // Search templates
  searchTemplates(companyId: number, query: string): TodoTemplate[] {
    const lowercaseQuery = query.toLowerCase();
    return this.templates.value.filter(t => 
      t.company_id === companyId &&
      (t.name.toLowerCase().includes(lowercaseQuery) ||
       t.description.toLowerCase().includes(lowercaseQuery) ||
       t.category.toLowerCase().includes(lowercaseQuery))
    );
  }

  // Convert template to todos
  convertTemplateToTodos(template: TodoTemplate, projectId: number, assignee?: string): any[] {
    return template.todos.map(todo => ({
      title: todo.title,
      description: todo.description,
      priority: todo.priority,
      type: todo.type,
      assignee: assignee || '',
      tags: todo.tags,
      project_id: projectId,
      estimated_hours: todo.estimated_hours,
      subtasks: todo.subtasks?.map(subtask => ({
        title: subtask.title,
        description: subtask.description,
        priority: subtask.priority,
        type: subtask.type,
        assignee: assignee || '',
        tags: subtask.tags,
        estimated_hours: subtask.estimated_hours
      }))
    }));
  }

  // Set templates (for loading from API)
  setTemplates(templates: TodoTemplate[]) {
    this.templates.value = templates;
  }
}

export const todoTemplateService = new TodoTemplateService();
