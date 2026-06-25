import axios from 'axios';

export interface ProjectGroup {
    id: number;
    project_id: number;
    name: string;
    description?: string | null;
    color?: string | null;
    viewing_order: number;
    is_default: boolean;
    created_at: string;
    updated_at: string;
}

class ProjectGroupApiService {
    async list(projectId: number): Promise<ProjectGroup[]> {
        const response = await axios.get<{ success: boolean; data: ProjectGroup[] }>(
            `/projects/${projectId}/groups`,
        );
        return response.data.data;
    }

    async create(
        projectId: number,
        data: { name: string; description?: string; color?: string; template?: 'viewing' | 'listing' },
    ): Promise<ProjectGroup> {
        const response = await axios.post<{ success: boolean; data: ProjectGroup }>(
            `/projects/${projectId}/groups`,
            data,
        );
        return response.data.data;
    }

    async update(groupId: number, data: Partial<Pick<ProjectGroup, 'name' | 'description' | 'color'>>): Promise<ProjectGroup> {
        const response = await axios.put<{ success: boolean; data: ProjectGroup }>(
            `/project-groups/${groupId}`,
            data,
        );
        return response.data.data;
    }

    async delete(groupId: number): Promise<void> {
        await axios.delete(`/project-groups/${groupId}`);
    }
}

export const projectGroupApi = new ProjectGroupApiService();
