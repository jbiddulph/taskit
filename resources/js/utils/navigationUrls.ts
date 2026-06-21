export interface NavigationStop {
    latitude: number;
    longitude: number;
    label?: string;
}

function isAppleDevice(): boolean {
    if (typeof navigator === 'undefined') {
        return false;
    }

    return /iPad|iPhone|iPod|Macintosh/.test(navigator.userAgent);
}

export function buildMapsNavigationUrl(
    stops: NavigationStop[],
    preference: 'apple' | 'google' | 'auto' = 'auto',
): string {
    if (stops.length === 0) {
        return '';
    }

    const useApple = preference === 'apple' || (preference === 'auto' && isAppleDevice());
    const destination = stops[stops.length - 1];
    const waypoints = stops.slice(0, -1);

    if (useApple) {
        if (stops.length === 1) {
            return `maps://?daddr=${destination.latitude},${destination.longitude}&dirflg=d`;
        }

        const via = waypoints
            .map((stop) => `${stop.latitude},${stop.longitude}`)
            .join('+to:');

        return `maps://?saddr=Current+Location&daddr=${via}+to:${destination.latitude},${destination.longitude}&dirflg=d`;
    }

    if (stops.length === 1) {
        return `https://www.google.com/maps/dir/?api=1&destination=${destination.latitude},${destination.longitude}&travelmode=driving`;
    }

    const waypointParam = waypoints
        .map((stop) => `${stop.latitude},${stop.longitude}`)
        .join('|');

    return `https://www.google.com/maps/dir/?api=1&origin=Current+Location&destination=${destination.latitude},${destination.longitude}&waypoints=${encodeURIComponent(waypointParam)}&travelmode=driving`;
}
