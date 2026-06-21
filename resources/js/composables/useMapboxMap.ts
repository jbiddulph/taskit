import { nextTick, onUnmounted, ref, type Ref } from 'vue';
import { useResizeObserver } from '@vueuse/core';
import mapboxgl from 'mapbox-gl';
import { getMapboxAccessToken } from '@/services/mapboxClient';

type MapOptions = Omit<mapboxgl.MapOptions, 'container'>;

export function useMapboxMap(
    containerRef: Ref<HTMLElement | null>,
    options: MapOptions | (() => MapOptions),
    onReady?: (map: mapboxgl.Map) => void,
) {
    const mapReady = ref(false);
    let map: mapboxgl.Map | null = null;
    let initTimeout: ReturnType<typeof setTimeout> | null = null;

    const resolveOptions = (): MapOptions =>
        typeof options === 'function' ? options() : options;

    const resizeMap = () => {
        map?.resize();
    };

    const destroyMap = () => {
        map?.remove();
        map = null;
        mapReady.value = false;
    };

    const initMap = () => {
        const container = containerRef.value;
        if (!container || map) return false;

        if (container.offsetWidth === 0 || container.offsetHeight === 0) {
            return false;
        }

        const token = getMapboxAccessToken();
        if (!token) return false;

        mapboxgl.accessToken = token;
        map = new mapboxgl.Map({
            container,
            ...resolveOptions(),
        });

        map.on('load', () => {
            mapReady.value = true;
            resizeMap();
            if (map && onReady) {
                onReady(map);
            }
        });

        return true;
    };

    const scheduleMapInit = async () => {
        await nextTick();
        await new Promise<void>((resolve) => requestAnimationFrame(() => resolve()));

        if (initMap()) {
            resizeMap();
            return;
        }

        initTimeout = setTimeout(() => {
            initMap();
            resizeMap();
        }, 100);
    };

    useResizeObserver(containerRef, () => {
        resizeMap();
    });

    onUnmounted(() => {
        if (initTimeout) clearTimeout(initTimeout);
        destroyMap();
    });

    return {
        mapReady,
        getMap: () => map,
        scheduleMapInit,
        resizeMap,
        destroyMap,
    };
}
