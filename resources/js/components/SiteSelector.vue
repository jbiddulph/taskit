<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import { operationalSiteApi, type OperationalSite } from '@/services/operationalSiteApi';

const model = defineModel<number | null>({ default: null });

const selectValue = computed({
  get: () => (model.value === null ? '' : String(model.value)),
  set: (value: string) => {
    model.value = value === '' ? null : Number(value);
  },
});

const sites = ref<OperationalSite[]>([]);
const loading = ref(false);

const selectedSite = computed(() => sites.value.find((s) => s.id === model.value) ?? null);

const emit = defineEmits<{
    selected: [site: OperationalSite | null];
}>();

onMounted(async () => {
    loading.value = true;
    try {
        sites.value = await operationalSiteApi.list();
    } finally {
        loading.value = false;
    }
});

watch(model, (id) => {
    const site = sites.value.find((s) => s.id === id) ?? null;
    emit('selected', site);
}, { immediate: true });
</script>

<template>
    <div v-if="sites.length > 0">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Site / property
        </label>
        <select
            v-model="selectValue"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
        >
            <option value="">None</option>
            <option v-for="site in sites" :key="site.id" :value="site.id">
                {{ site.type_label }} — {{ site.name }}
                <template v-if="site.full_address"> ({{ site.full_address }})</template>
            </option>
        </select>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
            Link this task to a site. Location fields will pre-fill when selected.
        </p>
    </div>
</template>
