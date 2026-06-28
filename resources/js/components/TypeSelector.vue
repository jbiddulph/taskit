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
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue';
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
