export type Priority = 'low' | 'normal' | 'high' | 'urgent' | 'Low' | 'Medium' | 'High' | 'Critical';

export type TaskStatus =
  | 'todo'
  | 'in-progress'
  | 'qa-testing'
  | 'done'
  | 'waiting'
  | 'blocked'
  | 'cancelled'
  | 'completed';

export interface ZapTaskClientOptions {
  baseUrl?: string;
  apiKey?: string;
  /** Optional fetch implementation (defaults to global fetch) */
  fetch?: typeof fetch;
}

export interface Task {
  id: number;
  company_id?: number | null;
  workspace_id?: number | null;
  project_id?: number | null;
  asset_id?: number | null;
  created_by?: number | null;
  assigned_to?: string | null;
  title: string;
  description?: string | null;
  status: string;
  priority: string;
  category?: string | null;
  due_date?: string | null;
  completed_at?: string | null;
  source?: string | null;
  metadata?: Record<string, unknown> | null;
  recurrence?: Record<string, unknown> | null;
  checklist?: ChecklistItem[];
  created_at?: string;
  updated_at?: string;
}

export interface ChecklistItem {
  id: number;
  task_id?: number;
  title: string;
  completed: boolean;
  position: number;
  completed_at?: string | null;
  completed_by?: number | null;
}

export interface Asset {
  id: number;
  company_id: number;
  workspace_id?: number | null;
  type: string;
  name: string;
  reference?: string | null;
  status?: string;
  metadata?: Record<string, unknown> | null;
  created_at?: string;
  updated_at?: string;
}

export interface Workspace {
  id: number;
  company_id: number;
  name: string;
  description?: string | null;
  type: string;
  is_default: boolean;
  created_at?: string;
  updated_at?: string;
}

export interface CreateTaskInput {
  title: string;
  description?: string;
  instructions?: string;
  project_id?: number;
  workspace_id?: number;
  assetId?: number;
  asset_id?: number;
  assigned_to?: string;
  status?: TaskStatus;
  priority?: Priority;
  category?: string;
  dueDate?: string;
  due_date?: string;
  source?: string;
  metadata?: Record<string, unknown>;
  recurrence?: Record<string, unknown>;
  checklist?: Array<{ title: string; completed?: boolean }>;
}

export interface CreateAssetInput {
  type: string;
  name: string;
  reference?: string;
  workspace_id?: number;
  status?: string;
  metadata?: Record<string, unknown>;
}

export interface ApiResponse<T> {
  success: boolean;
  message?: string;
  data?: T;
  errors?: Record<string, string[]>;
}

export class ZapTaskApiError extends Error {
  constructor(
    message: string,
    public status: number,
    public body?: unknown,
  ) {
    super(message);
    this.name = 'ZapTaskApiError';
  }
}

export class ZapTaskClient {
  private readonly baseUrl: string;
  private readonly apiKey?: string;
  private readonly fetchImpl: typeof fetch;

  constructor(options: ZapTaskClientOptions = {}) {
    this.baseUrl = (options.baseUrl ?? '').replace(/\/$/, '');
    this.apiKey = options.apiKey;
    this.fetchImpl = options.fetch ?? fetch.bind(globalThis);
  }

  readonly tasks = {
    list: (params?: Record<string, string | number | undefined>) =>
      this.get<{ tasks: Task[]; meta: Record<string, number> }>('/api/v1/tasks', params),
    get: (id: number) => this.get<Task>(`/api/v1/tasks/${id}`),
    create: (input: CreateTaskInput) =>
      this.post<Task>('/api/v1/tasks', this.normalizeTaskInput(input)),
    update: (id: number, input: Partial<CreateTaskInput>) =>
      this.patch<Task>(`/api/v1/tasks/${id}`, this.normalizeTaskInput(input)),
    delete: (id: number) => this.delete<null>(`/api/v1/tasks/${id}`),
  };

  readonly assets = {
    list: (params?: Record<string, string | number | undefined>) =>
      this.get<{ assets: Asset[]; meta: Record<string, number> }>('/api/v1/assets', params),
    get: (id: number) => this.get<Asset>(`/api/v1/assets/${id}`),
    create: (input: CreateAssetInput) => this.post<Asset>('/api/v1/assets', input),
    update: (id: number, input: Partial<CreateAssetInput>) =>
      this.patch<Asset>(`/api/v1/assets/${id}`, input),
    delete: (id: number) => this.delete<null>(`/api/v1/assets/${id}`),
  };

  readonly workspaces = {
    list: () => this.get<{ workspaces: Workspace[] }>('/api/v1/workspaces'),
    get: (id: number) => this.get<Workspace>(`/api/v1/workspaces/${id}`),
    create: (input: { name: string; description?: string; type?: string; is_default?: boolean }) =>
      this.post<Workspace>('/api/v1/workspaces', input),
    update: (
      id: number,
      input: Partial<{ name: string; description?: string; type?: string; is_default?: boolean }>,
    ) => this.patch<Workspace>(`/api/v1/workspaces/${id}`, input),
    delete: (id: number) => this.delete<null>(`/api/v1/workspaces/${id}`),
  };

  readonly users = {
    list: () => this.get<{ users: Array<Record<string, unknown>> }>('/api/v1/users'),
  };

  readonly files = {
    // Attachments remain on the session SPA API; specialised apps can use the SDK base for /api/todos/{id}/attachments
    listForTask: (todoId: number) => this.get(`/api/todos/${todoId}/attachments`),
  };

  private normalizeTaskInput(input: Partial<CreateTaskInput>): Record<string, unknown> {
    const payload: Record<string, unknown> = { ...input };
    if (input.assetId !== undefined) {
      payload.asset_id = input.assetId;
      delete payload.assetId;
    }
    if (input.dueDate !== undefined) {
      payload.due_date = input.dueDate;
      delete payload.dueDate;
    }
    return payload;
  }

  private async get<T>(path: string, params?: Record<string, string | number | undefined>): Promise<T> {
    const url = new URL(`${this.baseUrl}${path}`, this.baseUrl || 'http://localhost');
    if (params) {
      Object.entries(params).forEach(([key, value]) => {
        if (value !== undefined && value !== null && value !== '') {
          url.searchParams.set(key, String(value));
        }
      });
    }
    return this.request<T>(url.pathname + url.search, { method: 'GET' });
  }

  private post<T>(path: string, body: unknown): Promise<T> {
    return this.request<T>(path, { method: 'POST', body: JSON.stringify(body) });
  }

  private patch<T>(path: string, body: unknown): Promise<T> {
    return this.request<T>(path, { method: 'PATCH', body: JSON.stringify(body) });
  }

  private delete<T>(path: string): Promise<T> {
    return this.request<T>(path, { method: 'DELETE' });
  }

  private async request<T>(path: string, init: RequestInit): Promise<T> {
    const headers: Record<string, string> = {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      ...(init.headers as Record<string, string> | undefined),
    };

    if (this.apiKey) {
      headers.Authorization = `Bearer ${this.apiKey}`;
    }

    const response = await this.fetchImpl(`${this.baseUrl}${path}`, {
      ...init,
      headers,
    });

    const json = (await response.json().catch(() => null)) as ApiResponse<T> | null;

    if (!response.ok || json?.success === false) {
      throw new ZapTaskApiError(
        json?.message ?? `Request failed with status ${response.status}`,
        response.status,
        json,
      );
    }

    return (json?.data ?? json) as T;
  }
}

export default ZapTaskClient;
