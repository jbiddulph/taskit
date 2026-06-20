import axios from 'axios';
import type { Todo } from '@/services/todoApi';

export interface MapLocation {
    location_name?: string | null;
    location_address?: string | null;
    latitude: number;
    longitude: number;
}

export interface MapRoute {
    distance_meters?: number | null;
    duration_seconds?: number | null;
    geometry?: {
        type: 'LineString';
        coordinates: [number, number][];
    } | null;
}

class MapboxApiService {
    private async request<T>(config: Parameters<typeof axios.request>[0]): Promise<T> {
        const response = await axios.request<T>({
            baseURL: '/api',
            withCredentials: true,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
            ...config,
        });
        return response.data;
    }

    async getStatus(): Promise<{ configured: boolean }> {
        const response = await this.request<{ success: boolean; data: { configured: boolean } }>({
            method: 'GET',
            url: '/mapbox/status',
        });
        return response.data;
    }

    async geocode(query: string, proximity?: { latitude: number; longitude: number }): Promise<MapLocation[]> {
        const response = await this.request<{ success: boolean; data: MapLocation[] }>({
            method: 'GET',
            url: '/mapbox/geocode',
            params: {
                query,
                proximity_latitude: proximity?.latitude,
                proximity_longitude: proximity?.longitude,
            },
        });
        return response.data;
    }

    async reverseGeocode(latitude: number, longitude: number): Promise<MapLocation | null> {
        const response = await this.request<{ success: boolean; data: MapLocation | null }>({
            method: 'GET',
            url: '/mapbox/reverse-geocode',
            params: { latitude, longitude },
        });
        return response.data;
    }

    async directions(coordinates: Array<{ latitude: number; longitude: number }>, profile = 'driving'): Promise<MapRoute | null> {
        const response = await this.request<{ success: boolean; data: MapRoute | null }>({
            method: 'POST',
            url: '/mapbox/directions',
            data: { coordinates, profile },
        });
        return response.data;
    }
}

export const mapboxApi = new MapboxApiService();

export function todoHasLocation(todo: Pick<Todo, 'latitude' | 'longitude'>): boolean {
    return todo.latitude != null && todo.longitude != null;
}

export function getTodoCoordinates(todo: Pick<Todo, 'latitude' | 'longitude'>): [number, number] | null {
    if (!todoHasLocation(todo)) {
        return null;
    }
    return [todo.longitude as number, todo.latitude as number];
}

export function formatRouteDistance(meters?: number | null): string {
    if (!meters) return '';
    if (meters < 1000) return `${Math.round(meters)} m`;
    return `${(meters / 1000).toFixed(1)} km`;
}

export function formatRouteDuration(seconds?: number | null): string {
    if (!seconds) return '';
    const minutes = Math.round(seconds / 60);
    if (minutes < 60) return `${minutes} min`;
    const hours = Math.floor(minutes / 60);
    const remainder = minutes % 60;
    return remainder ? `${hours}h ${remainder}m` : `${hours}h`;
}
