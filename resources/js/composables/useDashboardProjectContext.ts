import { ref } from 'vue';

const currentProjectId = ref<number | null>(null);

export function getActiveProjectId(): number | null {
    if (currentProjectId.value) {
        return currentProjectId.value;
    }

    const stored = localStorage.getItem('currentProjectId');
    if (!stored) {
        return null;
    }

    const id = Number.parseInt(stored, 10);
    return Number.isNaN(id) ? null : id;
}

export function useDashboardProjectContext() {
    const setCurrentProjectId = (projectId: number | null) => {
        currentProjectId.value = projectId;
    };

    return {
        currentProjectId,
        setCurrentProjectId,
        getActiveProjectId,
    };
}
