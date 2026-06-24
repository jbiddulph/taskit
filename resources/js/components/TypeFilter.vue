<template>
  <div class="relative">
    <button
      type="button"
      @click="toggleDropdown"
      class="px-2 py-1.5 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-left flex items-center justify-between min-w-[120px] h-[34px]"
    >
      <div class="flex items-center gap-2">
        <Icon v-if="selectedType" :name="getTypeIcon(selectedType)" class="w-4 h-4" />
        <span>{{ selectedType || 'All Types' }}</span>
      </div>
      <Icon name="ChevronDown" class="w-4 h-4 text-gray-400" />
    </button>

    <div
      v-if="isOpen"
      class="absolute z-10 w-full mt-1 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg shadow-lg max-h-56 overflow-y-auto"
    >
      <div class="py-1">
        <button
          type="button"
          @click="selectType('')"
          class="w-full px-3 py-2 text-left text-sm hover:bg-gray-100 dark:hover:bg-gray-600 flex items-center gap-2"
        >
          <span class="text-gray-500 dark:text-gray-400">All Types</span>
        </button>
        <button
          v-for="type in TODO_TYPE_OPTIONS"
          :key="type.value"
          type="button"
          @click="selectType(type.value)"
          class="w-full px-3 py-2 text-left text-sm hover:bg-gray-100 dark:hover:bg-gray-600 flex items-center gap-2"
        >
          <Icon :name="type.icon" class="w-4 h-4" />
          <span>{{ type.value }}</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue';
import Icon from '@/components/Icon.vue';
import { getTypeIcon, TODO_TYPE_OPTIONS } from '@/constants/todoTypes';

interface Props {
  modelValue?: string;
}

const props = defineProps<Props>();
const emit = defineEmits<{
  'update:modelValue': [value: string];
}>();

const isOpen = ref(false);

const selectedType = computed(() => props.modelValue);

const toggleDropdown = () => {
  isOpen.value = !isOpen.value;
};

const selectType = (type: string) => {
  emit('update:modelValue', type);
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
