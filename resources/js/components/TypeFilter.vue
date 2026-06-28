<template>
  <div ref="rootEl" class="relative">
    <button
      type="button"
      @mousedown.prevent
      @click.stop="toggleDropdown"
      class="px-2 py-1.5 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-left flex items-center justify-between min-w-[120px] h-[34px]"
    >
      <div class="flex items-center gap-2 min-w-0">
        <Icon v-if="selectedType" :name="getTypeIcon(selectedType)" class="w-4 h-4 shrink-0" />
        <span class="truncate">{{ selectedLabel }}</span>
      </div>
      <Icon name="ChevronDown" class="w-4 h-4 text-gray-400 shrink-0" />
    </button>

    <div
      v-if="isOpen"
      class="absolute z-10 w-full min-w-[10rem] mt-1 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg shadow-lg max-h-56 overflow-y-auto"
      @mousedown.prevent
    >
      <div class="py-1">
        <button
          type="button"
          @mousedown.prevent
          @click.stop="selectType('')"
          class="w-full px-3 py-2 text-left text-sm hover:bg-gray-100 dark:hover:bg-gray-600 flex items-center gap-2"
        >
          <span class="text-gray-500 dark:text-gray-400">{{ t('todos.all_types') }}</span>
        </button>
        <button
          v-for="type in resolvedOptions"
          :key="type.value"
          type="button"
          @mousedown.prevent
          @click.stop="selectType(type.value)"
          class="w-full px-3 py-2 text-left text-sm hover:bg-gray-100 dark:hover:bg-gray-600 flex items-center gap-2"
        >
          <Icon :name="type.icon" class="w-4 h-4" />
          <span>{{ type.label }}</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue';
import Icon from '@/components/Icon.vue';
import { useTodoTypes, type TodoTypeOption } from '@/composables/useTodoTypes';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const { typeOptions, getTypeIcon } = useTodoTypes();

interface Props {
  modelValue?: string;
  options?: TodoTypeOption[];
}

const props = defineProps<Props>();
const emit = defineEmits<{
  'update:modelValue': [value: string];
}>();

const rootEl = ref<HTMLElement | null>(null);
const isOpen = ref(false);

const resolvedOptions = computed(() => props.options ?? typeOptions.value);

const selectedType = computed(() => props.modelValue);

const selectedLabel = computed(() => {
  if (!selectedType.value) {
    return t('todos.all_types');
  }

  const match = resolvedOptions.value.find((option) => option.value === selectedType.value);
  return match?.label ?? selectedType.value;
});

const toggleDropdown = () => {
  isOpen.value = !isOpen.value;
};

const selectType = (type: string) => {
  emit('update:modelValue', type);
  isOpen.value = false;
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
