<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import 'mapbox-gl/dist/mapbox-gl.css';
import Icon from '@/components/Icon.vue';
import { DEFAULT_MAP_CENTER, DEFAULT_MAP_ZOOM, hasMapbox } from '@/services/mapboxClient';
import { mapboxApi } from '@/services/mapboxApi';
import { useMapboxMap } from '@/composables/useMapboxMap';
import { useI18n } from 'vue-i18n';
import mapboxgl from 'mapbox-gl';

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

const mapContainer = ref<HTMLDivElement | null>(null);
const mapError = ref<string | null>(null);

let marker: mapboxgl.Marker | null = null;

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
    const map = getMap();
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

const { mapReady, getMap, scheduleMapInit } = useMapboxMap(
    mapContainer,
    () => ({
        style: 'mapbox://styles/mapbox/streets-v12',
        center:
            props.modelValue.longitude != null && props.modelValue.latitude != null
                ? [props.modelValue.longitude, props.modelValue.latitude]
                : DEFAULT_MAP_CENTER,
        zoom: hasLocation.value ? 13 : DEFAULT_MAP_ZOOM,
    }),
    (map) => {
        map.addControl(new mapboxgl.NavigationControl({ showCompass: false }), 'top-right');
        if (hasLocation.value) {
            placeMarker(props.modelValue.longitude as number, props.modelValue.latitude as number);
        }
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
    },
);

const clearLocation = () => {
    updateLocation(emptyLocation());
    marker?.remove();
    marker = null;
};

watch(
    () => [props.modelValue.latitude, props.modelValue.longitude],
    ([latitude, longitude]) => {
        if (getMap() && latitude != null && longitude != null) {
            placeMarker(longitude as number, latitude as number);
        }
    },
);

onMounted(async () => {
    if (!hasMapbox()) {
        mapError.value = t('map.not_configured');
        return;
    }

    await scheduleMapInit();
});

onUnmounted(() => {
    marker?.remove();
    marker = null;
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
            <div
                ref="mapContainer"
                class="location-picker-map h-44 w-full overflow-hidden rounded-md border border-gray-200 dark:border-gray-700"
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

<style scoped>
.location-picker-map {
    position: relative;
    overflow: hidden;
}

.location-picker-map :deep(.mapboxgl-map) {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
}
</style>
