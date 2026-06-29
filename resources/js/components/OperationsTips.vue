<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';

export type OperationsTipsContext =
    | 'sites_index'
    | 'sites_create'
    | 'sites_show'
    | 'task_form'
    | 'inspection_create'
    | 'inspection_show';

const props = withDefaults(
    defineProps<{
        context: OperationsTipsContext;
        defaultOpen?: boolean;
    }>(),
    { defaultOpen: false },
);

const { t, tm } = useI18n();
const isOpen = ref(props.defaultOpen);

const title = computed(() => t(`operations.tips.${props.context}.title`));
const intro = computed(() => t(`operations.tips.${props.context}.intro`));

const tips = computed(() => {
    const raw = tm(`operations.tips.${props.context}.items`) as string[] | Record<string, string>;
    if (Array.isArray(raw)) {
        return raw;
    }
    return Object.values(raw ?? {});
});

const examples = computed(() => {
    const raw = tm(`operations.tips.${props.context}.examples`) as string[] | Record<string, string>;
    if (!raw) return [];
    if (Array.isArray(raw)) return raw;
    return Object.values(raw);
});
</script>

<template>
    <div class="rounded-lg border border-blue-200 dark:border-blue-900/60 bg-blue-50/80 dark:bg-blue-950/20 overflow-hidden">
        <button
            type="button"
            class="w-full flex items-center justify-between gap-3 px-4 py-3 text-left hover:bg-blue-100/50 dark:hover:bg-blue-950/40 transition-colors"
            @click="isOpen = !isOpen"
        >
            <div class="flex items-center gap-2 text-sm font-medium text-blue-900 dark:text-blue-200">
                <Icon name="Lightbulb" class="w-4 h-4 shrink-0" />
                {{ title }}
            </div>
            <Icon :name="isOpen ? 'ChevronUp' : 'ChevronDown'" class="w-4 h-4 text-blue-700 dark:text-blue-300" />
        </button>

        <div v-if="isOpen" class="px-4 pb-4 space-y-4 border-t border-blue-200/60 dark:border-blue-900/40">
            <p v-if="intro" class="text-sm text-blue-900/80 dark:text-blue-100/80 pt-3">{{ intro }}</p>

            <ul v-if="tips.length" class="space-y-2">
                <li
                    v-for="(tip, index) in tips"
                    :key="index"
                    class="flex gap-2 text-sm text-gray-700 dark:text-gray-300"
                >
                    <span class="text-blue-600 dark:text-blue-400 shrink-0">•</span>
                    <span>{{ tip }}</span>
                </li>
            </ul>

            <div v-if="examples.length" class="rounded-md bg-white/70 dark:bg-gray-900/50 border border-blue-100 dark:border-blue-900/30 p-3">
                <div class="text-xs font-semibold uppercase tracking-wide text-blue-800 dark:text-blue-300 mb-2">
                    {{ t('operations.tips.examples_heading') }}
                </div>
                <ul class="space-y-1.5">
                    <li
                        v-for="(example, index) in examples"
                        :key="index"
                        class="text-sm text-gray-600 dark:text-gray-400 font-mono text-[13px]"
                    >
                        {{ example }}
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>
