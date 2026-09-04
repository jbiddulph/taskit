export type NavSection = 'company' | 'clients' | 'sites' | 'compliance' | 'team';

const SECTION_PREFIXES: Record<NavSection, readonly string[]> = {
    company: ['/companies', '/settings/company', '/settings/company-logo'],
    clients: ['/clients'],
    sites: ['/sites'],
    compliance: ['/compliance'],
    team: ['/team'],
};

export function inertiaPath(url: string): string {
    const path = (url || '').split('?')[0].split('#')[0];
    if (path.length > 1 && path.endsWith('/')) {
        return path.slice(0, -1);
    }
    return path;
}

function matchesPrefix(path: string, prefix: string): boolean {
    return path === prefix || path.startsWith(`${prefix}/`);
}

export function isNavSectionActive(url: string, section: NavSection): boolean {
    const path = inertiaPath(url);
    return SECTION_PREFIXES[section].some((prefix) => matchesPrefix(path, prefix));
}
