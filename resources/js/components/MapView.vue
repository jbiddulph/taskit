<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import 'mapbox-gl/dist/mapbox-gl.css';
import Icon from '@/components/Icon.vue';
import { flushCheckInQueue, isOnline, queueCheckIn } from '@/composables/useOfflineQueue';
import type { Todo } from '@/services/todoApi';
import { todoApi } from '@/services/todoApi';
import { DEFAULT_MAP_CENTER, DEFAULT_MAP_ZOOM, hasMapbox } from '@/services/mapboxClient';
import {
    formatRouteDistance,
    formatRouteDuration,
    getTodoCoordinates,
    mapboxApi,
    todoHasLocation,
} from '@/services/mapboxApi';
import { useMapboxMap } from '@/composables/useMapboxMap';
import { buildMapsNavigationUrl } from '@/utils/navigationUrls';
import { useI18n } from 'vue-i18n';
import mapboxgl from 'mapbox-gl';

const { t } = useI18n();

interface Props {
    todos: Todo[];
    isReadOnly?: boolean;
    currentUserName?: string;
}

const props = withDefaults(defineProps<Props>(), {
    isReadOnly: false,
    currentUserName: '',
});

const emit = defineEmits<{
    editTodo: [todo: Todo];
    todoUpdated: [todo: Todo];
}>();

const mapContainer = ref<HTMLDivElement | null>(null);
const mapError = ref<string | null>(null);
const myRouteToday = ref(false);
const selectedRouteIds = ref<number[]>([]);
const routeLoading = ref(false);
const routeSummary = ref<{ distance?: string; duration?: string } | null>(null);
const checkInLoadingId = ref<number | null>(null);

const markers = new Map<number, mapboxgl.Marker>();

const baseLocatedTodos = computed(() =>
    props.todos.filter((todo) => todoHasLocation(todo) && !todo.parent_task_id),
);

const locatedTodos = computed(() => {
    if (!myRouteToday.value || !props.currentUserName) {
        return baseLocatedTodos.value;
    }

    return baseLocatedTodos.value.filter(
        (todo) => todo.assignee?.trim().toLowerCase() === props.currentUserName.trim().toLowerCase(),
    );
});

const selectedRouteTodos = computed(() =>
    locatedTodos.value.filter((todo) => selectedRouteIds.value.includes(todo.id)),
);

const { mapReady, getMap, scheduleMapInit } = useMapboxMap(
    mapContainer,
    () => ({
        style: 'mapbox://styles/mapbox/streets-v12',
        center: DEFAULT_MAP_CENTER,
        zoom: DEFAULT_MAP_ZOOM,
    }),
    (map) => {
        map.addControl(new mapboxgl.NavigationControl({ showCompass: false }), 'top-right');
        syncMarkers();
    },
);

const notify = (type: 'success' | 'error' | 'warning', title: string, message: string) => {
    if ((window as any).$notify) {
        (window as any).$notify({ type, title, message });
    }
};

const fitMapToMarkers = () => {
    const map = getMap();
    if (!map || locatedTodos.value.length === 0) return;

    const bounds = new mapboxgl.LngLatBounds();
    locatedTodos.value.forEach((todo) => {
        const coords = getTodoCoordinates(todo);
        if (coords) bounds.extend(coords);
    });

    if (locatedTodos.value.length === 1) {
        const coords = getTodoCoordinates(locatedTodos.value[0]);
        if (coords) map.flyTo({ center: coords, zoom: 13 });
        return;
    }

    map.fitBounds(bounds, { padding: 48, maxZoom: 14 });
};

const clearRouteLayer = () => {
    const map = getMap();
    if (!map) return;
    if (map.getLayer('route-line')) map.removeLayer('route-line');
    if (map.getSource('route-line')) map.removeSource('route-line');
};

const syncMarkers = () => {
    const map = getMap();
    if (!map) return;

    const activeIds = new Set(locatedTodos.value.map((todo) => todo.id));

    markers.forEach((marker, id) => {
        if (!activeIds.has(id)) {
            marker.remove();
            markers.delete(id);
        }
    });

    locatedTodos.value.forEach((todo) => {
        const coords = getTodoCoordinates(todo);
        if (!coords) return;

        const color = todo.project?.color || '#2563eb';
        const checkedIn = todo.checked_in_at ? ' · ✓ checked in' : '';
        const popup = new mapboxgl.Popup({ offset: 16, closeButton: false }).setHTML(
            `<div style="font-size:12px;font-weight:600">${todo.title}</div>
             <div style="font-size:11px;color:#6b7280;margin-top:2px">${todo.location_name || todo.location_address || ''}${checkedIn}</div>`,
        );

        const existing = markers.get(todo.id);
        if (existing) {
            existing.setLngLat(coords).setPopup(popup);
            return;
        }

        const el = document.createElement('button');
        el.type = 'button';
        el.className = 'map-task-marker';
        el.style.cssText = [
            'width:14px',
            'height:14px',
            'border-radius:9999px',
            'border:2px solid white',
            `background:${color}`,
            'box-shadow:0 1px 4px rgba(0,0,0,.35)',
            'cursor:pointer',
        ].join(';');
        el.addEventListener('click', (event) => {
            event.stopPropagation();
            emit('editTodo', todo);
        });

        const marker = new mapboxgl.Marker({ element: el })
            .setLngLat(coords)
            .setPopup(popup)
            .addTo(map);
        markers.set(todo.id, marker);
    });

    fitMapToMarkers();
};

const toggleMyRouteToday = () => {
    myRouteToday.value = !myRouteToday.value;
    selectedRouteIds.value = [];
    routeSummary.value = null;
    clearRouteLayer();

    if (myRouteToday.value) {
        selectedRouteIds.value = locatedTodos.value.map((todo) => todo.id);
    }
};

const planRoute = async () => {
    const map = getMap();
    if (!map || selectedRouteTodos.value.length < 2) return;

    routeLoading.value = true;
    routeSummary.value = null;

    try {
        const route = await mapboxApi.directions(
            selectedRouteTodos.value.map((todo) => ({
                latitude: todo.latitude as number,
                longitude: todo.longitude as number,
            })),
        );

        clearRouteLayer();

        if (!route?.geometry) {
            routeSummary.value = null;
            return;
        }

        map.addSource('route-line', {
            type: 'geojson',
            data: {
                type: 'Feature',
                properties: {},
                geometry: route.geometry,
            },
        });

        map.addLayer({
            id: 'route-line',
            type: 'line',
            source: 'route-line',
            layout: {
                'line-join': 'round',
                'line-cap': 'round',
            },
            paint: {
                'line-color': '#2563eb',
                'line-width': 4,
                'line-opacity': 0.85,
            },
        });

        const bounds = new mapboxgl.LngLatBounds();
        route.geometry.coordinates.forEach((coord) => bounds.extend(coord));
        map.fitBounds(bounds, { padding: 48, maxZoom: 14 });

        routeSummary.value = {
            distance: formatRouteDistance(route.distance_meters),
            duration: formatRouteDuration(route.duration_seconds),
        };
    } catch (error: any) {
        const message =
            error.response?.data?.message ||
            error.message ||
            t('map.route_plan_failed');
        notify('error', t('map.plan_route'), message);
    } finally {
        routeLoading.value = false;
    }
};

const openNavigation = () => {
    const stops = selectedRouteTodos.value
        .map((todo) => {
            const coords = getTodoCoordinates(todo);
            if (!coords) return null;
            return {
                latitude: coords[1],
                longitude: coords[0],
                label: todo.title,
            };
        })
        .filter(Boolean) as Array<{ latitude: number; longitude: number; label?: string }>;

    if (stops.length === 0) {
        notify('warning', t('map.navigate'), t('map.select_stops_first'));
        return;
    }

    const url = buildMapsNavigationUrl(stops);
    if (url) {
        window.open(url, '_blank', 'noopener,noreferrer');
    }
};

const checkInAtTask = async (todo: Todo) => {
    if (props.isReadOnly || todo.checked_in_at) {
        return;
    }

    if (!navigator.geolocation) {
        notify('error', t('map.check_in'), t('map.geolocation_unavailable'));
        return;
    }

    checkInLoadingId.value = todo.id;

    navigator.geolocation.getCurrentPosition(
        async (position) => {
            const latitude = position.coords.latitude;
            const longitude = position.coords.longitude;

            if (!isOnline()) {
                queueCheckIn(todo.id, latitude, longitude);
                checkInLoadingId.value = null;
                notify('warning', t('map.check_in'), t('map.check_in_queued'));
                return;
            }

            try {
                const updated = await todoApi.checkIn(todo.id, latitude, longitude);
                emit('todoUpdated', updated);
                notify('success', t('map.check_in'), t('map.check_in_success'));
            } catch (error: any) {
                if (!isOnline() || error?.message === 'Network Error') {
                    queueCheckIn(todo.id, latitude, longitude);
                    notify('warning', t('map.check_in'), t('map.check_in_queued'));
                } else {
                    const message = error.response?.data?.message || error.message || t('map.check_in_failed');
                    notify('error', t('map.check_in'), message);
                }
            } finally {
                checkInLoadingId.value = null;
            }
        },
        () => {
            checkInLoadingId.value = null;
            notify('error', t('map.check_in'), t('map.geolocation_denied'));
        },
        { enableHighAccuracy: true, timeout: 15000 },
    );
};

const clearRoute = () => {
    selectedRouteIds.value = [];
    routeSummary.value = null;
    clearRouteLayer();
};

watch(
    () => props.todos,
    () => syncMarkers(),
    { deep: true },
);

watch(locatedTodos, () => {
    syncMarkers();
});

onMounted(async () => {
    if (!hasMapbox()) {
        mapError.value = t('map.not_configured');
        return;
    }

    const syncResult = await flushCheckInQueue((todoId, latitude, longitude) =>
        todoApi.checkIn(todoId, latitude, longitude),
    );
    if (syncResult.synced > 0) {
        notify('success', t('map.check_in'), t('map.check_in_synced', { count: syncResult.synced }));
    }

    window.addEventListener('online', handleOnlineSync);
    await scheduleMapInit();
});

const handleOnlineSync = async () => {
    const syncResult = await flushCheckInQueue((todoId, latitude, longitude) =>
        todoApi.checkIn(todoId, latitude, longitude),
    );
    if (syncResult.synced > 0) {
        notify('success', t('map.check_in'), t('map.check_in_synced', { count: syncResult.synced }));
    }
};

watch(mapReady, (ready) => {
    if (ready) syncMarkers();
});

onUnmounted(() => {
    window.removeEventListener('online', handleOnlineSync);
    markers.forEach((marker) => marker.remove());
    markers.clear();
});
</script>

<template>
    <div class="rounded-lg border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800">
        <div class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ t('map.task_map') }}</h2>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    {{ t('map.task_map_hint', { count: locatedTodos.length }) }}
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <button
                    v-if="currentUserName"
                    type="button"
                    class="rounded-full border px-2.5 py-1 text-xs font-medium transition"
                    :class="myRouteToday
                        ? 'border-blue-500 bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-200'
                        : 'border-gray-300 text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300'"
                    @click="toggleMyRouteToday"
                >
                    {{ t('map.my_route_today') }}
                </button>
                <div v-if="routeSummary" class="text-xs text-blue-700 dark:text-blue-300">
                    {{ routeSummary.distance }} · {{ routeSummary.duration }}
                </div>
            </div>
        </div>

        <div v-if="mapError" class="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-200">
            {{ mapError }}
        </div>

        <template v-else>
            <div class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_16rem]">
                <div
                    ref="mapContainer"
                    class="task-map h-[320px] w-full overflow-hidden rounded-md border border-gray-200 dark:border-gray-700"
                    :class="{ 'opacity-60': !mapReady }"
                />

                <div class="flex flex-col gap-2">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            {{ t('map.field_route') }}
                        </h3>
                        <button
                            v-if="selectedRouteIds.length > 0"
                            type="button"
                            class="text-xs text-gray-500 hover:text-gray-700 dark:hover:text-gray-300"
                            @click="clearRoute"
                        >
                            {{ t('map.clear_route') }}
                        </button>
                    </div>

                    <div v-if="locatedTodos.length === 0" class="rounded-md border border-dashed border-gray-200 px-3 py-4 text-xs text-gray-500 dark:border-gray-700 dark:text-gray-400">
                        {{ myRouteToday ? t('map.no_my_route_tasks') : t('map.no_located_tasks') }}
                    </div>

                    <div v-else class="max-h-56 space-y-1 overflow-y-auto">
                        <div
                            v-for="todo in locatedTodos"
                            :key="todo.id"
                            class="rounded-md border border-gray-200 px-2 py-1.5 text-xs dark:border-gray-700"
                        >
                            <label class="flex cursor-pointer items-start gap-2">
                                <input
                                    v-model="selectedRouteIds"
                                    type="checkbox"
                                    :value="todo.id"
                                    class="mt-0.5"
                                    :disabled="isReadOnly"
                                />
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate font-medium text-gray-900 dark:text-gray-100">{{ todo.title }}</span>
                                    <span class="block truncate text-gray-500 dark:text-gray-400">{{ todo.location_name || todo.location_address }}</span>
                                    <span
                                        v-if="todo.checked_in_at"
                                        class="mt-0.5 inline-block rounded bg-emerald-100 px-1.5 py-0.5 text-[10px] font-medium text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200"
                                    >
                                        {{ t('map.checked_in') }}
                                    </span>
                                </span>
                            </label>
                            <button
                                v-if="!isReadOnly && !todo.checked_in_at"
                                type="button"
                                class="mt-1 w-full rounded border border-gray-300 px-2 py-1 text-[11px] font-medium hover:bg-gray-50 disabled:opacity-50 dark:border-gray-600 dark:hover:bg-gray-900/40"
                                :disabled="checkInLoadingId === todo.id"
                                @click.stop="checkInAtTask(todo)"
                            >
                                {{ checkInLoadingId === todo.id ? t('map.checking_in') : t('map.check_in') }}
                            </button>
                        </div>
                    </div>

                    <button
                        type="button"
                        class="inline-flex items-center justify-center gap-1.5 rounded-md border border-gray-300 bg-black px-3 py-2 text-xs font-medium text-white hover:bg-gray-900 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-600 dark:bg-white dark:text-black dark:hover:bg-gray-100"
                        :disabled="selectedRouteIds.length < 2 || routeLoading || isReadOnly"
                        @click="planRoute"
                    >
                        <Icon name="Route" class="h-3.5 w-3.5" />
                        {{ routeLoading ? t('map.planning_route') : t('map.plan_route') }}
                    </button>

                    <button
                        type="button"
                        class="inline-flex items-center justify-center gap-1.5 rounded-md border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-900 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 dark:hover:bg-gray-800"
                        :disabled="selectedRouteIds.length < 1 || isReadOnly"
                        @click="openNavigation"
                    >
                        <Icon name="Navigation" class="h-3.5 w-3.5" />
                        {{ t('map.navigate') }}
                    </button>

                    <p class="text-[11px] text-gray-500 dark:text-gray-400">{{ t('map.route_hint') }}</p>
                </div>
            </div>
        </template>
    </div>
</template>

<style scoped>
.task-map {
    position: relative;
    overflow: hidden;
}

.task-map :deep(.mapboxgl-map) {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
}
</style>
