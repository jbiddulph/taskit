<template>
  <div class="space-y-2">
    <div class="relative">
      <button
        type="button"
        @click="toggleDropdown"
        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white text-left flex items-center justify-between"
      >
        <div class="flex items-center gap-2 min-w-0">
          <Icon v-if="presetType" :name="displayIcon" class="w-4 h-4 shrink-0" />
          <span class="truncate">{{ displayLabel || t('todos.select_type') }}</span>
        </div>
        <Icon name="ChevronDown" class="w-4 h-4 text-gray-400 shrink-0" />
      </button>

      <div
        v-if="isOpen"
        class="absolute z-10 w-full mt-1 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg max-h-56 overflow-y-auto"
      >
        <div class="py-1">
          <button
            type="button"
            @click="selectType('')"
            class="w-full px-3 py-2 text-left text-sm hover:bg-gray-100 dark:hover:bg-gray-600 flex items-center gap-2"
          >
            <span class="text-gray-400">{{ t('todos.select_type') }}</span>
          </button>
          <button
            v-for="type in typeOptions"
            :key="type.value"
            type="button"
            @click="selectType(type.value)"
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
        v-model="customOtherType"
        type="text"
        required
        maxlength="50"
        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
        :placeholder="t('todos.type_other_placeholder')"
        @input="emitCustomType"
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

interface Props {
  modelValue?: string;
}

const props = defineProps<Props>();
const emit = defineEmits<{
  'update:modelValue': [value: string];
}>();

const isOpen = ref(false);
const presetType = ref('');
const customOtherType = ref('');
const otherInput = ref<HTMLInputElement | null>(null);

const knownTypeValues = computed(() => typeOptions.value.map((option) => option.value));

const showOtherInput = computed(() => presetType.value === 'Other');

const displayLabel = computed(() => {
  if (!presetType.value) {
    return '';
  }

  if (presetType.value === 'Other') {
    return customOtherType.value.trim() || t('todos.type_other');
  }

  return getTypeLabel(presetType.value);
});

const displayIcon = computed(() => {
  if (presetType.value === 'Other') {
    return 'CircleHelp';
  }

  return presetType.value ? getTypeIcon(presetType.value) : 'Circle';
});

const syncFromModelValue = (value?: string) => {
  if (!value) {
    presetType.value = '';
    customOtherType.value = '';
    return;
  }

  if (knownTypeValues.value.includes(value) && value !== 'Other') {
    presetType.value = value;
    customOtherType.value = '';
    return;
  }

  presetType.value = 'Other';
  customOtherType.value = value === 'Other' ? '' : value;
};

watch(() => props.modelValue, syncFromModelValue, { immediate: true });

watch(typeOptions, () => syncFromModelValue(props.modelValue));

const emitCustomType = () => {
  if (presetType.value === 'Other') {
    emit('update:modelValue', customOtherType.value.trim());
  }
};

const toggleDropdown = () => {
  isOpen.value = !isOpen.value;
};

const selectType = async (type: string) => {
  isOpen.value = false;

  if (!type) {
    presetType.value = '';
    customOtherType.value = '';
    emit('update:modelValue', '');
    return;
  }

  presetType.value = type;

  if (type === 'Other') {
    emit('update:modelValue', customOtherType.value.trim());
    await nextTick();
    otherInput.value?.focus();
    return;
  }

  customOtherType.value = '';
  emit('update:modelValue', type);
};

const handleClickOutside = (event: Event) => {
  const target = event.target as HTMLElement;
  if (!target.closest('.relative')) {
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
