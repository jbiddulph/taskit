import axios from 'axios';

// Configure axios defaults
axios.defaults.baseURL = '/api';
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.withCredentials = true; // This enables cookies and CSRF token handling

export interface Project {
    id: number;
    name: string;
    description?: string;
    key: string;
    color: string;
    is_active: boolean;
    owner_id: number;
    created_at: string;
    updated_at: string;
    total_todos?: number;
    stats?: TodoStats;
    viewing_order?: number;
}

export interface Todo {
    id: number;
    user_id: number;
    project_id: number;
    title: string;
    description?: string;
    priority: 'Low' | 'Medium' | 'High' | 'Critical';
    type?: 'Bug' | 'Feature' | 'Task' | 'Story' | 'Epic';
    tags?: string[];
    assignee?: string;
    due_date?: string;
    story_points?: number;
    status: 'todo' | 'in-progress' | 'done';
    created_at: string;
    updated_at: string;
    comments?: TodoComment[];
    attachments?: TodoAttachment[];
    project?: Project;
    is_new_assigned?: boolean;
}

export interface TodoComment {
    id: number;
    todo_id: number;
    user_id: number;
    content: string;
    created_at: string;
    updated_at: string;
    user?: {
        id: number;
        name: string;
        email: string;
    };
}

export interface TodoAttachment {
    id: number;
    todo_id: number;
    user_id: number;
    filename: string;
    original_filename: string;
    mime_type: string;
    file_path: string;
    file_size: number;
    created_at: string;
    updated_at: string;
    user?: {
        id: number;
        name: string;
        email: string;
    };
}

export interface TodoFilters {
    search?: string;
    priority?: string;
    type?: string;
    assignee?: string;
    status?: string;
    overdue?: boolean;
    due_today?: boolean;
}

export interface TodoStats {
    total: number;
    todo: number;
    'in-progress': number;
    done: number;
    overdue: number;
    due_today: number;
}

export interface ApiResponse<T> {
    success: boolean;
    message: string;
    data: T;
}

export interface TodoListResponse {
    data: {
        todo: Todo[];
        'in-progress': Todo[];
        done: Todo[];
    };
    stats: TodoStats;
}

class TodoApiService {
    private async request<T>(config: any): Promise<T> {
        try {
            const response = await axios(config);
            return response.data;
        } catch (error: any) {
            if (error.response?.data?.message) {
                throw new Error(error.response.data.message);
            }
            throw new Error('An error occurred while processing your request');
        }
    }

    // Get all todos with filters
    async getTodos(filters: TodoFilters = {}): Promise<TodoListResponse> {
        const params = new URLSearchParams();
        
        Object.entries(filters).forEach(([key, value]) => {
            if (value !== undefined && value !== null && value !== '') {
                params.append(key, value.toString());
            }
        });

        return this.request<TodoListResponse>({
            method: 'GET',
            url: `/todos?${params.toString()}`,
        });
    }

    // Get a single todo
    async getTodo(id: number): Promise<Todo> {
        const response = await this.request<ApiResponse<Todo>>({
            method: 'GET',
            url: `/todos/${id}`,
        });
        // Mark assignment as seen when opening a todo
        try {
            if (response.data?.is_new_assigned) {
                await this.request({
                    method: 'POST',
                    url: `/todos/${id}/mark-assignment-seen`,
                });
            }
        } catch { /* ignore */ }
        return response.data;
    }

    // Create a new todo
    async createTodo(todoData: Partial<Todo>): Promise<Todo> {
        const response = await this.request<ApiResponse<Todo>>({
            method: 'POST',
            url: '/todos',
            data: todoData,
        });
        return response.data;
    }

    // Update a todo
    async updateTodo(id: number, todoData: Partial<Todo>): Promise<Todo> {
        const response = await this.request<ApiResponse<Todo>>({
            method: 'PUT',
            url: `/todos/${id}`,
            data: todoData,
        });
        return response.data;
    }

    // Delete a todo
    async deleteTodo(id: number): Promise<void> {
        await this.request({
            method: 'DELETE',
            url: `/todos/${id}`,
        });
    }

    // Update todo status (for drag & drop)
    async updateTodoStatus(id: number, status: string): Promise<Todo> {
        const response = await this.request<ApiResponse<Todo>>({
            method: 'PATCH',
            url: `/todos/${id}/status`,
            data: { status },
        });
        return response.data;
    }

    // Get unique assignees
    async getAssignees(): Promise<string[]> {
        const response = await this.request<ApiResponse<string[]>>({
            method: 'GET',
            url: '/todos/assignees',
        });
        return response.data;
    }

    // Project operations
    async getProjects(): Promise<Project[]> {
        const response = await this.request<ApiResponse<Project[]>>({
            method: 'GET',
            url: '/projects',
        });
        return response.data;
    }

    async getProjectsWithStats(): Promise<Project[]> {
        const response = await this.request<ApiResponse<Project[]>>({
            method: 'GET',
            url: '/projects-with-stats',
        });
        return response.data;
    }

    async getProject(id: number): Promise<Project> {
        const response = await this.request<ApiResponse<Project>>({
            method: 'GET',
            url: `/projects/${id}`,
        });
        return response.data;
    }

    async createProject(projectData: Partial<Project>): Promise<Project> {
        const response = await this.request<ApiResponse<Project>>({
            method: 'POST',
            url: '/projects',
            data: projectData,
        });
        return response.data;
    }

    async updateProject(id: number, projectData: Partial<Project>): Promise<Project> {
        const response = await this.request<ApiResponse<Project>>({
            method: 'PUT',
            url: `/projects/${id}`,
            data: projectData,
        });
        return response.data;
    }

    async deleteProject(id: number): Promise<void> {
        await this.request({
            method: 'DELETE',
            url: `/projects/${id}`,
        });
    }

    async deleteProjectWithTodos(id: number): Promise<void> {
        await this.request({
            method: 'DELETE',
            url: `/projects/${id}/with-todos`,
        });
    }

    async getProjectStats(id: number): Promise<TodoStats> {
        const response = await this.request<ApiResponse<TodoStats>>({
            method: 'GET',
            url: `/projects/${id}/stats`,
        });
        return response.data;
    }

    async updateProjectOrder(projectOrders: { id: number; viewing_order: number }[]): Promise<void> {
        await this.request({
            method: 'PATCH',
            url: '/projects/update-order',
            data: { project_orders: projectOrders },
        });
    }

    // Comment operations
    async getComments(todoId: number): Promise<TodoComment[]> {
        const response = await this.request<ApiResponse<TodoComment[]>>({
            method: 'GET',
            url: `/todos/${todoId}/comments`,
        });
        return response.data;
    }

    async addComment(todoId: number, content: string): Promise<TodoComment> {
        const response = await this.request<ApiResponse<TodoComment>>({
            method: 'POST',
            url: `/todos/${todoId}/comments`,
            data: { content },
        });
        return response.data;
    }

    async updateComment(todoId: number, commentId: number, content: string): Promise<TodoComment> {
        const response = await this.request<ApiResponse<TodoComment>>({
            method: 'PUT',
            url: `/todos/${todoId}/comments/${commentId}`,
            data: { content },
        });
        return response.data;
    }

    async deleteComment(todoId: number, commentId: number): Promise<void> {
        await this.request({
            method: 'DELETE',
            url: `/todos/${todoId}/comments/${commentId}`,
        });
    }

    // Attachment operations
    async getAttachments(todoId: number): Promise<TodoAttachment[]> {
        const response = await this.request<ApiResponse<TodoAttachment[]>>({
            method: 'GET',
            url: `/todos/${todoId}/attachments`,
        });
        return response.data;
    }

    async uploadAttachment(todoId: number, file: File): Promise<TodoAttachment> {
        const formData = new FormData();
        formData.append('file', file);

        const response = await this.request<ApiResponse<TodoAttachment>>({
            method: 'POST',
            url: `/todos/${todoId}/attachments`,
            data: formData,
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });
        return response.data;
    }

    async deleteAttachment(todoId: number, attachmentId: number): Promise<void> {
        await this.request({
            method: 'DELETE',
            url: `/todos/${todoId}/attachments/${attachmentId}`,
        });
    }

    // Get attachment download URL
    getAttachmentDownloadUrl(todoId: number, attachmentId: number): string {
        return `/api/todos/${todoId}/attachments/${attachmentId}/download`;
    }

    // Get attachment preview URL
    getAttachmentPreviewUrl(todoId: number, attachmentId: number): string {
        return `/api/todos/${todoId}/attachments/${attachmentId}/preview`;
    }
}

export const todoApi = new TodoApiService();
