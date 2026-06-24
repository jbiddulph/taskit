<template>
  <div class="relative">
    <button
      type="button"
      @click="toggleDropdown"
      class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white text-left flex items-center justify-between"
    >
      <div class="flex items-center gap-2">
        <Icon :name="displayIcon" class="w-4 h-4" />
        <span>{{ displayLabel }}</span>
      </div>
      <Icon name="ChevronDown" class="w-4 h-4 text-gray-400" />
    </button>

    <div
      v-if="isOpen"
      class="absolute z-20 w-full mt-1 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg max-h-56 overflow-y-auto"
    >
      <div class="py-1">
        <button
          v-if="allowClear"
          type="button"
          @click="selectIcon(null)"
          class="w-full px-3 py-2 text-left text-sm hover:bg-gray-100 dark:hover:bg-gray-600 flex items-center gap-2"
        >
          <span class="text-gray-400">{{ clearLabel }}</span>
        </button>
        <button
          v-for="icon in icons"
          :key="icon"
          type="button"
          @click="selectIcon(icon)"
          :class="[
            'w-full px-3 py-2 text-left text-sm hover:bg-gray-100 dark:hover:bg-gray-600 flex items-center gap-2',
            modelValue === icon ? 'bg-blue-50 dark:bg-blue-900/30' : '',
          ]"
        >
          <Icon :name="icon" class="w-4 h-4" />
          <span>{{ icon }}</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue';
import Icon from '@/components/Icon.vue';
import { TODO_CARD_ICON_OPTIONS } from '@/constants/todoTypes';

interface Props {
  modelValue?: string | null;
  icons?: readonly string[];
  allowClear?: boolean;
  clearLabel?: string;
  placeholder?: string;
}

const props = withDefaults(defineProps<Props>(), {
  icons: () => TODO_CARD_ICON_OPTIONS,
  allowClear: true,
  clearLabel: 'Use type default',
  placeholder: 'Select icon',
});

const emit = defineEmits<{
  'update:modelValue': [value: string | null];
}>();

const isOpen = ref(false);

const displayIcon = computed(() => props.modelValue || 'Circle');
const displayLabel = computed(() => props.modelValue || props.placeholder);

const toggleDropdown = () => {
  isOpen.value = !isOpen.value;
};

const selectIcon = (icon: string | null) => {
  emit('update:modelValue', icon);
  isOpen.value = false;
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
