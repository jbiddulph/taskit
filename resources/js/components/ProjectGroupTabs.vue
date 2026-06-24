<template>
  <div v-if="groups.length > 0" class="mt-2 border-t border-gray-200 dark:border-gray-700 pt-2">
    <div class="flex items-center gap-1.5 overflow-x-auto pb-1">
      <button
        v-for="group in groups"
        :key="group.id"
        type="button"
        @click="$emit('select', group)"
        :class="[
          'inline-flex items-center gap-1.5 shrink-0 px-3 py-1.5 rounded-md text-xs font-medium border transition-colors',
          currentGroupId === group.id
            ? 'bg-gray-900 text-white border-gray-900 dark:bg-white dark:text-gray-900 dark:border-white'
            : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-700',
        ]"
        :style="currentGroupId === group.id && group.color ? { backgroundColor: group.color, borderColor: group.color } : {}"
      >
        <span class="truncate max-w-[10rem]">{{ group.name }}</span>
        <span
          v-if="group.is_default"
          class="text-[10px] uppercase tracking-wide opacity-70"
        >
          default
        </span>
      </button>

      <button
        v-if="!readOnly"
        type="button"
        @click="$emit('create')"
        class="inline-flex items-center gap-1 shrink-0 px-2.5 py-1.5 rounded-md text-xs font-medium border border-dashed border-gray-300 text-gray-600 hover:border-gray-400 hover:text-gray-800 dark:border-gray-600 dark:text-gray-300 dark:hover:border-gray-500"
        :title="t('todos.project_groups.add_group')"
      >
        <Icon name="Plus" class="w-3.5 h-3.5" />
        {{ t('todos.project_groups.add_group') }}
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import type { ProjectGroup } from '@/services/projectGroupApi';
import { useI18n } from 'vue-i18n';

defineProps<{
  groups: ProjectGroup[];
  currentGroupId: number | null;
  readOnly?: boolean;
}>();

defineEmits<{
  select: [group: ProjectGroup];
  create: [];
}>();

const { t } = useI18n();
</script>
