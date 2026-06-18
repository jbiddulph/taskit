import type { Project } from '@/services/todoApi';

function normalizeName(name: string): string {
    return name.toLowerCase().replace(/[^a-z0-9\s]/g, ' ').replace(/\s+/g, ' ').trim();
}

function nameTokens(name: string): Set<string> {
    return new Set(normalizeName(name).split(' ').filter((token) => token.length > 1));
}

export function matchProjectByName(projects: Project[], name?: string | null): number | null {
    if (!name?.trim()) {
        return null;
    }

    const normalized = normalizeName(name);

    const exact = projects.find((project) => normalizeName(project.name) === normalized);
    if (exact) {
        return exact.id;
    }

    const partial = projects.find((project) => {
        const projectName = normalizeName(project.name);
        return projectName.includes(normalized) || normalized.includes(projectName);
    });
    if (partial) {
        return partial.id;
    }

    const queryTokens = nameTokens(name);
    if (queryTokens.size === 0) {
        return null;
    }

    let bestMatch: { id: number; score: number } | null = null;

    for (const project of projects) {
        const projectTokens = nameTokens(project.name);
        const overlap = [...queryTokens].filter((token) => projectTokens.has(token)).length;
        const score = overlap / Math.max(queryTokens.size, projectTokens.size);

        if (score >= 0.5 && (!bestMatch || score > bestMatch.score)) {
            bestMatch = { id: project.id, score };
        }
    }

    return bestMatch?.id ?? null;
}
