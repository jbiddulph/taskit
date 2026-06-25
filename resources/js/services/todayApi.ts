import axios from 'axios';
import type { TodaySummaryData } from '@/services/voiceCommandApi';

class TodayApiService {
    async getSummary(projectId?: number | null): Promise<TodaySummaryData> {
        const response = await axios.get<{ success: boolean; data: TodaySummaryData }>('/today/summary', {
            params: projectId ? { project_id: projectId } : undefined,
        });
        return response.data.data;
    }
}

export const todayApi = new TodayApiService();
