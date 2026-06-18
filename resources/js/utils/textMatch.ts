const STOP_WORDS = new Set([
    'a', 'an', 'the', 'my', 'our', 'for', 'in', 'on', 'to', 'and', 'or', 'of', 'project', 'projects',
]);

export function normalizeText(value: string): string {
    return value
        .toLowerCase()
        .replace(/[^a-z0-9\s]/g, ' ')
        .replace(/\s+/g, ' ')
        .trim();
}

export function textTokens(value: string): string[] {
    return normalizeText(value)
        .split(' ')
        .filter((token) => token.length > 1 && !STOP_WORDS.has(token));
}

export function tokenOverlapScore(query: string, candidate: string): number {
    const queryTokens = textTokens(query);
    const candidateTokens = new Set(textTokens(candidate));

    if (queryTokens.length === 0 || candidateTokens.size === 0) {
        return 0;
    }

    const overlap = queryTokens.filter((token) => candidateTokens.has(token)).length;
    return overlap / Math.max(queryTokens.length, candidateTokens.size);
}

export type BoardStatus = 'todo' | 'in-progress' | 'qa-testing' | 'done';

export function inferStatusFromText(text?: string | null): BoardStatus | null {
    if (!text?.trim()) {
        return null;
    }

    const normalized = normalizeText(text);

    const inProgressPatterns = [
        /\bin[\s-]?progress\b/,
        /\bprogress\s+column\b/,
        /\bbeing\s+worked\b/,
        /\bworking\s+on\b/,
        /\bunder[\s-]?way\b/,
        /\bstarted\b/,
        /\bactive\b/,
        /\bwip\b/,
        /\bdoing\b/,
        /\bcurrent\s+sprint\b/,
    ];

    const qaPatterns = [
        /\bq\s*&?\s*a\b/,
        /\bqa[\s-]?testing\b/,
        /\btesting\s+column\b/,
        /\bin\s+review\b/,
        /\bfor\s+review\b/,
        /\bneeds?\s+review\b/,
        /\bquality\s+assurance\b/,
        /\btesting\b/,
    ];

    const donePatterns = [
        /\bcompleted?\b/,
        /\bfinished\b/,
        /\bshipped\b/,
        /\bclosed\b/,
        /\bdone\s+column\b/,
    ];

    const todoPatterns = [
        /\bto[\s-]?do\b/,
        /\btodo\s+column\b/,
        /\bbacklog\b/,
        /\bnot\s+started\b/,
        /\bnew\s+column\b/,
        /\bpending\b/,
        /\bicebox\b/,
    ];

    if (inProgressPatterns.some((pattern) => pattern.test(normalized))) {
        return 'in-progress';
    }

    if (qaPatterns.some((pattern) => pattern.test(normalized))) {
        return 'qa-testing';
    }

    if (donePatterns.some((pattern) => pattern.test(normalized))) {
        return 'done';
    }

    if (todoPatterns.some((pattern) => pattern.test(normalized))) {
        return 'todo';
    }

    return null;
}

export function normalizeBoardStatus(status?: string | null, fallbackText?: string | null): BoardStatus {
    const inferred = inferStatusFromText(status) ?? inferStatusFromText(fallbackText);
    if (inferred) {
        return inferred;
    }

    const normalized = normalizeText(status || '');

    if (['in progress', 'in-progress', 'in_progress', 'doing', 'active', 'progress'].includes(normalized)) {
        return 'in-progress';
    }

    if (['qa', 'qa-testing', 'qa testing', 'testing', 'review'].includes(normalized)) {
        return 'qa-testing';
    }

    if (['done', 'complete', 'completed', 'finished'].includes(normalized)) {
        return 'done';
    }

    return 'todo';
}

export interface NamedItem {
    id: number;
    name: string;
}

export function matchNameToList<T extends NamedItem>(
    items: T[],
    spoken?: string | null,
    minimumScore = 0.35,
): T | null {
    if (!spoken?.trim() || items.length === 0) {
        return null;
    }

    const normalizedQuery = normalizeText(spoken);

    const exact = items.find((item) => normalizeText(item.name) === normalizedQuery);
    if (exact) {
        return exact;
    }

    const partial = items.find((item) => {
        const normalizedName = normalizeText(item.name);
        return normalizedName.includes(normalizedQuery) || normalizedQuery.includes(normalizedName);
    });
    if (partial) {
        return partial;
    }

    let best: { item: T; score: number } | null = null;

    for (const item of items) {
        const score = tokenOverlapScore(spoken, item.name);
        if (score >= minimumScore && (!best || score > best.score)) {
            best = { item, score };
        }
    }

    return best?.item ?? null;
}

export function findMentionedNameInText<T extends NamedItem>(text: string, items: T[]): T | null {
    if (!text.trim() || items.length === 0) {
        return null;
    }

    const normalizedText = normalizeText(text);
    let best: { item: T; score: number } | null = null;

    for (const item of items) {
        const normalizedName = normalizeText(item.name);
        if (normalizedName && normalizedText.includes(normalizedName)) {
            const score = normalizedName.length / normalizedText.length;
            if (!best || score > best.score) {
                best = { item, score };
            }
            continue;
        }

        const score = tokenOverlapScore(item.name, text);
        if (score >= 0.5 && (!best || score > best.score)) {
            best = { item, score };
        }
    }

    return best?.item ?? null;
}
