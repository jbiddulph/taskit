<template>
  <div class="relative">
    <button
      type="button"
      @click="toggleDropdown"
      class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white text-left flex items-center justify-between"
    >
      <div class="flex items-center gap-2">
        <Icon v-if="selectedType" :name="getTypeIcon(selectedType)" class="w-4 h-4" />
        <span>{{ selectedType ? getTypeLabel(selectedType) : t('todos.select_type') }}</span>
      </div>
      <Icon name="ChevronDown" class="w-4 h-4 text-gray-400" />
    </button>
    
    <div
      v-if="isOpen"
      class="absolute z-10 w-full mt-1 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg"
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
          v-for="type in types"
          :key="type.value"
          type="button"
          @click="selectType(type.value)"
          class="w-full px-3 py-2 text-left text-sm hover:bg-gray-100 dark:hover:bg-gray-600 flex items-center gap-2"
        >
          <Icon :name="type.icon" class="w-4 h-4" />
          <span>{{ getTypeLabel(type.value) }}</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import Icon from '@/components/Icon.vue';
import { useI18n } from 'vue-i18n';
const { t } = useI18n();

interface Props {
  modelValue?: string;
}

interface Emits {
  (e: 'update:modelValue', value: string): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const isOpen = ref(false);

const types = [
  { value: 'Bug', label: 'Bug', icon: 'Bug' },
  { value: 'Feature', label: 'Feature', icon: 'Zap' },
  { value: 'Task', label: 'Task', icon: 'CheckSquare' },
  { value: 'Story', label: 'Story', icon: 'BookOpen' },
  { value: 'Epic', label: 'Epic', icon: 'Layers' }
];

const selectedType = computed(() => props.modelValue);

const getTypeIcon = (type: string): string => {
  const iconMap: Record<string, string> = {
    'Bug': 'Bug',
    'Feature': 'Zap',
    'Task': 'CheckSquare',
    'Story': 'BookOpen',
    'Epic': 'Layers'
  };
  return iconMap[type] || 'Circle';
};

const getTypeLabel = (type: string): string => {
  const labelMap: Record<string, string> = {
    'Bug': t('todos.type_bug'),
    'Feature': t('todos.type_feature'),
    'Task': t('todos.type_task'),
    'Story': t('todos.type_story'),
    'Epic': t('todos.type_epic')
  };
  return labelMap[type] || type;
};

const toggleDropdown = () => {
  isOpen.value = !isOpen.value;
};

const selectType = (type: string) => {
  emit('update:modelValue', type);
  isOpen.value = false;
};

// Close dropdown when clicking outside
const handleClickOutside = (event: Event) => {
  const target = event.target as HTMLElement;
  if (!target.closest('.relative')) {
    isOpen.value = false;
  }
};

// Add event listener when component mounts
import { onMounted, onUnmounted } from 'vue';

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
});
</script>
