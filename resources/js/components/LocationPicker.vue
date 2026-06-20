<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import mapboxgl from 'mapbox-gl';
import 'mapbox-gl/dist/mapbox-gl.css';
import { DEFAULT_MAP_CENTER, DEFAULT_MAP_ZOOM, getMapboxAccessToken, hasMapbox } from '@/services/mapboxClient';
import { mapboxApi, type MapLocation } from '@/services/mapboxApi';
import Icon from '@/components/Icon.vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

export interface LocationValue {
    location_name: string | null;
    location_address: string | null;
    latitude: number | null;
    longitude: number | null;
}

interface Props {
    modelValue: LocationValue;
    disabled?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    disabled: false,
});

const emit = defineEmits<{
    'update:modelValue': [value: LocationValue];
}>();

const searchQuery = ref('');
const searchResults = ref<MapLocation[]>([]);
const searching = ref(false);
const mapContainer = ref<HTMLDivElement | null>(null);
const mapReady = ref(false);
const mapError = ref<string | null>(null);

let map: mapboxgl.Map | null = null;
let marker: mapboxgl.Marker | null = null;
let searchTimeout: ReturnType<typeof setTimeout> | null = null;

const hasLocation = computed(
    () => props.modelValue.latitude != null && props.modelValue.longitude != null,
);

const emptyLocation = (): LocationValue => ({
    location_name: null,
    location_address: null,
    latitude: null,
    longitude: null,
});

const updateLocation = (location: Partial<LocationValue>) => {
    emit('update:modelValue', {
        ...props.modelValue,
        ...location,
    });
};

const placeMarker = (longitude: number, latitude: number) => {
    if (!map) return;
    if (!marker) {
        marker = new mapboxgl.Marker({ color: '#2563eb' })
            .setLngLat([longitude, latitude])
            .addTo(map);
    } else {
        marker.setLngLat([longitude, latitude]);
    }
    map.flyTo({ center: [longitude, latitude], zoom: 14 });
};

const initMap = () => {
    if (!mapContainer.value || map || props.disabled) return;

    const token = getMapboxAccessToken();
    if (!token) {
        mapError.value = t('map.not_configured');
        return;
    }

    mapboxgl.accessToken = token;
    const center: [number, number] =
        props.modelValue.longitude != null && props.modelValue.latitude != null
            ? [props.modelValue.longitude, props.modelValue.latitude]
            : DEFAULT_MAP_CENTER;

    map = new mapboxgl.Map({
        container: mapContainer.value,
        style: 'mapbox://styles/mapbox/streets-v12',
        center,
        zoom: hasLocation.value ? 13 : DEFAULT_MAP_ZOOM,
    });

    map.addControl(new mapboxgl.NavigationControl({ showCompass: false }), 'top-right');

    map.on('load', () => {
        mapReady.value = true;
        if (hasLocation.value) {
            placeMarker(props.modelValue.longitude as number, props.modelValue.latitude as number);
        }
    });

    map.on('click', async (event) => {
        if (props.disabled) return;
        const { lng, lat } = event.lngLat;
        placeMarker(lng, lat);

        try {
            const result = await mapboxApi.reverseGeocode(lat, lng);
            updateLocation({
                latitude: lat,
                longitude: lng,
                location_name: result?.location_name ?? t('map.selected_location'),
                location_address: result?.location_address ?? `${lat.toFixed(5)}, ${lng.toFixed(5)}`,
            });
        } catch {
            updateLocation({
                latitude: lat,
                longitude: lng,
                location_name: t('map.selected_location'),
                location_address: `${lat.toFixed(5)}, ${lng.toFixed(5)}`,
            });
        }
    });
};

const runSearch = () => {
    if (searchTimeout) clearTimeout(searchTimeout);
    const query = searchQuery.value.trim();
    if (query.length < 2) {
        searchResults.value = [];
        return;
    }

    searchTimeout = setTimeout(async () => {
        searching.value = true;
        try {
            const proximity =
                props.modelValue.latitude != null && props.modelValue.longitude != null
                    ? {
                          latitude: props.modelValue.latitude,
                          longitude: props.modelValue.longitude,
                      }
                    : undefined;
            searchResults.value = await mapboxApi.geocode(query, proximity);
        } catch {
            searchResults.value = [];
        } finally {
            searching.value = false;
        }
    }, 300);
};

const selectResult = (result: MapLocation) => {
    updateLocation({
        location_name: result.location_name ?? null,
        location_address: result.location_address ?? null,
        latitude: result.latitude,
        longitude: result.longitude,
    });
    searchResults.value = [];
    searchQuery.value = result.location_address ?? result.location_name ?? '';
    placeMarker(result.longitude, result.latitude);
};

const clearLocation = () => {
    updateLocation(emptyLocation());
    searchQuery.value = '';
    searchResults.value = [];
    marker?.remove();
    marker = null;
};

watch(
    () => [props.modelValue.latitude, props.modelValue.longitude],
    ([latitude, longitude]) => {
        if (map && latitude != null && longitude != null) {
            placeMarker(longitude as number, latitude as number);
        }
    },
);

onMounted(() => {
    if (hasMapbox()) {
        initMap();
    } else {
        mapError.value = t('map.not_configured');
    }
    if (props.modelValue.location_address) {
        searchQuery.value = props.modelValue.location_address;
    }
});

onUnmounted(() => {
    marker?.remove();
    map?.remove();
    map = null;
    marker = null;
    if (searchTimeout) clearTimeout(searchTimeout);
});
</script>

<template>
    <div class="space-y-2">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ t('map.location') }}
        </label>

        <div v-if="mapError" class="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-200">
            {{ mapError }}
        </div>

        <template v-else>
            <div class="relative">
                <input
                    v-model="searchQuery"
                    type="text"
                    :disabled="disabled"
                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                    :placeholder="t('map.search_placeholder')"
                    @input="runSearch"
                />
                <div
                    v-if="searching"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-gray-400"
                >
                    ...
                </div>
                <div
                    v-if="searchResults.length > 0"
                    class="absolute z-20 mt-1 max-h-48 w-full overflow-y-auto rounded-md border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800"
                >
                    <button
                        v-for="(result, index) in searchResults"
                        :key="`${result.longitude}-${result.latitude}-${index}`"
                        type="button"
                        class="block w-full px-3 py-2 text-left text-sm hover:bg-gray-50 dark:hover:bg-gray-700"
                        @click="selectResult(result)"
                    >
                        <span class="font-medium text-gray-900 dark:text-gray-100">{{ result.location_name }}</span>
                        <span class="mt-0.5 block text-xs text-gray-500 dark:text-gray-400">{{ result.location_address }}</span>
                    </button>
                </div>
            </div>

            <div
                ref="mapContainer"
                class="h-44 w-full overflow-hidden rounded-md border border-gray-200 dark:border-gray-700"
                :class="{ 'opacity-60': !mapReady }"
            />

            <div
                v-if="hasLocation"
                class="flex items-start justify-between gap-2 rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900/40"
            >
                <div class="min-w-0">
                    <div class="flex items-center gap-1.5 font-medium text-gray-900 dark:text-gray-100">
                        <Icon name="MapPin" class="h-4 w-4 shrink-0 text-blue-600" />
                        <span class="truncate">{{ modelValue.location_name || t('map.selected_location') }}</span>
                    </div>
                    <p v-if="modelValue.location_address" class="mt-0.5 truncate text-xs text-gray-500 dark:text-gray-400">
                        {{ modelValue.location_address }}
                    </p>
                </div>
                <button
                    v-if="!disabled"
                    type="button"
                    class="shrink-0 text-xs text-red-600 hover:text-red-700 dark:text-red-400"
                    @click="clearLocation"
                >
                    {{ t('map.clear_location') }}
                </button>
            </div>

            <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('map.click_hint') }}</p>
        </template>
    </div>
</template>
