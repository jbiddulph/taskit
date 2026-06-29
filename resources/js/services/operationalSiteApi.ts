import axios from 'axios';

export interface OperationalSite {
    id: number;
    name: string;
    type: string;
    type_label: string;
    full_address: string;
    parent_id?: number | null;
    latitude?: number | null;
    longitude?: number | null;
}

export interface DocumentExtractionProposal {
    id: number;
    status: string;
    extracted_data: Record<string, string | null>;
    summary?: string | null;
    site?: { id: number; name: string };
    document?: { id: number; title: string; original_filename: string };
}

class OperationalSiteApi {
    async list(): Promise<OperationalSite[]> {
        const response = await axios.get('/sites');
        return response.data.data ?? [];
    }

    async uploadDocument(
        siteId: number,
        file: File,
        options: { title?: string; expires_at?: string; extract?: boolean } = {},
    ) {
        const formData = new FormData();
        formData.append('file', file);
        if (options.title) formData.append('title', options.title);
        if (options.expires_at) formData.append('expires_at', options.expires_at);
        if (options.extract !== undefined) formData.append('extract', options.extract ? '1' : '0');

        const response = await axios.post(`/sites/${siteId}/documents`, formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        return response.data;
    }
}

class DocumentExtractionApi {
    async getPending(): Promise<DocumentExtractionProposal[]> {
        const response = await axios.get('/document-extraction/proposals/pending');
        return response.data.data ?? [];
    }

    async approve(proposalId: number, projectId?: number) {
        const response = await axios.post(`/document-extraction/proposals/${proposalId}/approve`, {
            project_id: projectId,
        });
        return response.data as {
            success: boolean;
            message: string;
            data?: {
                task?: { id: number; title: string; project_id: number } | null;
            };
        };
    }

    async dismiss(proposalId: number) {
        const response = await axios.post(`/document-extraction/proposals/${proposalId}/dismiss`);
        return response.data;
    }
}

export const operationalSiteApi = new OperationalSiteApi();
export const documentExtractionApi = new DocumentExtractionApi();
