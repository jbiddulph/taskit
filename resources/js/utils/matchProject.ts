import type { Project } from '@/services/todoApi';
import { findMentionedNameInText, matchNameToList } from '@/utils/textMatch';

export function matchProjectByName(projects: Project[], name?: string | null): number | null {
    const match = matchNameToList(projects, name, 0.35);
    return match?.id ?? null;
}

export function matchProjectFromTranscript(projects: Project[], transcript?: string | null): number | null {
    if (!transcript?.trim()) {
        return null;
    }

    const match = findMentionedNameInText(projects, transcript);
    return match?.id ?? null;
}
