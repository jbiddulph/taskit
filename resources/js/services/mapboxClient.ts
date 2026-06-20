const runtimeToken = (globalThis as any)?.VITE_MAPBOX_ACCESS_TOKEN as string | undefined;

export function getMapboxAccessToken(): string | undefined {
    const token = (import.meta as any)?.env?.VITE_MAPBOX_ACCESS_TOKEN || runtimeToken;
    return token?.trim() || undefined;
}

export function hasMapbox(): boolean {
    return !!getMapboxAccessToken();
}

export const DEFAULT_MAP_CENTER: [number, number] = [-0.1276, 51.5072]; // London
export const DEFAULT_MAP_ZOOM = 10;
