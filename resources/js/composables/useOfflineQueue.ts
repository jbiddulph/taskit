const QUEUE_KEY = 'zaptask_offline_checkins_v1';

export interface OfflineCheckInItem {
    id: string;
    todoId: number;
    latitude: number;
    longitude: number;
    queuedAt: string;
}

function readQueue(): OfflineCheckInItem[] {
    try {
        const raw = localStorage.getItem(QUEUE_KEY);
        return raw ? JSON.parse(raw) : [];
    } catch {
        return [];
    }
}

function writeQueue(items: OfflineCheckInItem[]) {
    localStorage.setItem(QUEUE_KEY, JSON.stringify(items));
}

export function queueCheckIn(todoId: number, latitude: number, longitude: number): OfflineCheckInItem {
    const item: OfflineCheckInItem = {
        id: `${todoId}-${Date.now()}`,
        todoId,
        latitude,
        longitude,
        queuedAt: new Date().toISOString(),
    };

    const queue = readQueue();
    queue.push(item);
    writeQueue(queue);

    return item;
}

export function getQueuedCheckIns(): OfflineCheckInItem[] {
    return readQueue();
}

export function removeQueuedCheckIn(id: string) {
    writeQueue(readQueue().filter((item) => item.id !== id));
}

export async function flushCheckInQueue(
    checkInFn: (todoId: number, latitude: number, longitude: number) => Promise<unknown>,
): Promise<{ synced: number; failed: number }> {
    const queue = readQueue();
    let synced = 0;
    let failed = 0;

    for (const item of queue) {
        try {
            await checkInFn(item.todoId, item.latitude, item.longitude);
            removeQueuedCheckIn(item.id);
            synced++;
        } catch {
            failed++;
        }
    }

    return { synced, failed };
}

export function isOnline(): boolean {
    return typeof navigator === 'undefined' ? true : navigator.onLine;
}
