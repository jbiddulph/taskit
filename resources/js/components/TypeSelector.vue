<template>
  <div ref="rootEl" class="space-y-2">
    <div class="relative">
      <button
        type="button"
        @mousedown.prevent
        @click.stop="toggleDropdown"
        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white text-left flex items-center justify-between"
      >
        <div class="flex items-center gap-2 min-w-0">
          <Icon v-if="displayType" :name="displayIcon" class="w-4 h-4 shrink-0" />
          <span class="truncate">{{ displayLabel || t('todos.select_type') }}</span>
        </div>
        <Icon name="ChevronDown" class="w-4 h-4 text-gray-400 shrink-0" />
      </button>

      <div
        v-if="isOpen"
        class="absolute z-10 w-full mt-1 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg max-h-56 overflow-y-auto"
        @mousedown.prevent
      >
        <div class="py-1">
          <button
            type="button"
            @mousedown.prevent
            @click.stop="selectPreset('')"
            class="w-full px-3 py-2 text-left text-sm hover:bg-gray-100 dark:hover:bg-gray-600 flex items-center gap-2"
          >
            <span class="text-gray-400">{{ t('todos.select_type') }}</span>
          </button>
          <button
            v-for="type in typeOptions"
            :key="type.value"
            type="button"
            @mousedown.prevent
            @click.stop="selectPreset(type.value)"
            class="w-full px-3 py-2 text-left text-sm hover:bg-gray-100 dark:hover:bg-gray-600 flex items-center gap-2"
          >
            <Icon :name="type.icon" class="w-4 h-4" />
            <span>{{ type.label }}</span>
          </button>
        </div>
      </div>
    </div>

    <div v-if="showOtherInput">
      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
        {{ t('todos.type_other_label') }}
      </label>
      <input
        ref="otherInput"
        v-model="otherText"
        type="text"
        required
        maxlength="50"
        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
        :placeholder="t('todos.type_other_placeholder')"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import Icon from '@/components/Icon.vue';
import { useTodoTypes } from '@/composables/useTodoTypes';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const { typeOptions, getTypeLabel, getTypeIcon } = useTodoTypes();

const modelValue = defineModel<string>({ default: '' });

const rootEl = ref<HTMLElement | null>(null);
const isOpen = ref(false);
const otherInput = ref<HTMLInputElement | null>(null);
const showOtherInput = ref(false);

const knownTypeValues = computed(() => typeOptions.value.map((option) => option.value));

const isKnownPreset = (value: string) =>
  knownTypeValues.value.includes(value) && value !== 'Other';

const syncFromModelValue = (value: string) => {
  const trimmed = value.trim();

  if (trimmed && !isKnownPreset(trimmed)) {
    showOtherInput.value = true;
    return;
  }

  if (trimmed && isKnownPreset(trimmed)) {
    showOtherInput.value = false;
  }
};

watch(
  modelValue,
  (value) => {
    syncFromModelValue((value ?? '').trim());
  },
  { immediate: true },
);

const displayType = computed(() => {
  const value = (modelValue.value ?? '').trim();

  if (value && !isKnownPreset(value)) {
    return value;
  }

  if (showOtherInput.value) {
    return 'Other';
  }

  return value;
});

const displayLabel = computed(() => {
  const value = (modelValue.value ?? '').trim();

  if (showOtherInput.value) {
    return value || t('todos.type_other');
  }

  if (value) {
    return getTypeLabel(value);
  }

  return '';
});

const displayIcon = computed(() => {
  const value = (modelValue.value ?? '').trim();

  if (value && !isKnownPreset(value)) {
    return 'CircleHelp';
  }

  if (showOtherInput.value) {
    return 'CircleHelp';
  }

  return displayType.value ? getTypeIcon(displayType.value) : 'Circle';
});

const otherText = computed({
  get: () => modelValue.value ?? '',
  set: (value: string) => {
    modelValue.value = value;
  },
});

const selectPreset = async (type: string) => {
  isOpen.value = false;

  if (!type) {
    showOtherInput.value = false;
    modelValue.value = '';
    return;
  }

  if (type === 'Other') {
    showOtherInput.value = true;

    const current = (modelValue.value ?? '').trim();
    if (!current || isKnownPreset(current)) {
      modelValue.value = '';
    }

    await nextTick();
    otherInput.value?.focus();
    return;
  }

  showOtherInput.value = false;
  modelValue.value = type;
};

const toggleDropdown = () => {
  isOpen.value = !isOpen.value;
};

const handleClickOutside = (event: Event) => {
  const target = event.target as HTMLElement;
  if (!rootEl.value?.contains(target)) {
    isOpen.value = false;
  }
};

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
});
</script>
