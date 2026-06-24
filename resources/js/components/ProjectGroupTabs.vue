<template>
  <div v-if="groups.length > 0" class="mt-2 border-t border-gray-200 dark:border-gray-700 pt-2">
    <div class="flex items-center gap-1.5 overflow-x-auto pb-1">
      <div
        v-for="group in groups"
        :key="group.id"
        class="relative inline-flex shrink-0"
        :class="[
          'rounded-md border transition-colors',
          currentGroupId === group.id
            ? 'bg-gray-900 text-white border-gray-900 dark:bg-white dark:text-gray-900 dark:border-white'
            : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-700',
        ]"
        :style="currentGroupId === group.id && group.color ? { backgroundColor: group.color, borderColor: group.color } : {}"
      >
        <button
          type="button"
          @click="$emit('select', group)"
          class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium"
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
          @click.stop="toggleMenu(group.id)"
          class="inline-flex items-center justify-center border-l border-current/20 px-1.5 text-current/80 hover:text-current focus:outline-none focus:ring-2 focus:ring-blue-500"
          :aria-expanded="openMenuGroupId === group.id"
          :aria-label="t('todos.project_groups.board_actions')"
          :title="t('todos.project_groups.board_actions')"
        >
          <Icon name="ChevronDown" class="w-3.5 h-3.5" />
        </button>

        <div
          v-if="openMenuGroupId === group.id"
          class="absolute right-0 top-full z-30 mt-1 w-40 overflow-hidden rounded-md border border-gray-200 bg-white py-1 text-gray-700 shadow-lg dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
          @click.stop
        >
          <button
            type="button"
            @click="emitAction('edit', group)"
            class="flex w-full items-center gap-2 px-3 py-2 text-left text-xs hover:bg-gray-100 dark:hover:bg-gray-700"
          >
            <Icon name="Edit3" class="w-3.5 h-3.5" />
            {{ t('common.edit') }}
          </button>
          <button
            v-if="!group.is_default"
            type="button"
            @click="emitAction('delete', group)"
            class="flex w-full items-center gap-2 px-3 py-2 text-left text-xs text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/30"
          >
            <Icon name="Trash2" class="w-3.5 h-3.5" />
            {{ t('common.delete') }}
          </button>
        </div>
      </div>

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
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';

defineProps<{
  groups: ProjectGroup[];
  currentGroupId: number | null;
  readOnly?: boolean;
}>();

const emit = defineEmits<{
  select: [group: ProjectGroup];
  create: [];
  edit: [group: ProjectGroup];
  delete: [group: ProjectGroup];
}>();

const { t } = useI18n();
const openMenuGroupId = ref<number | null>(null);

const toggleMenu = (groupId: number) => {
  openMenuGroupId.value = openMenuGroupId.value === groupId ? null : groupId;
};

const emitAction = (action: 'edit' | 'delete', group: ProjectGroup) => {
  openMenuGroupId.value = null;
  if (action === 'edit') {
    emit('edit', group);
    return;
  }

  emit('delete', group);
};

const closeMenu = () => {
  openMenuGroupId.value = null;
};

onMounted(() => {
  document.addEventListener('click', closeMenu);
});

onBeforeUnmount(() => {
  document.removeEventListener('click', closeMenu);
});
</script>
