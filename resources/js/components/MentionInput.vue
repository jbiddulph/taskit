<template>
  <div class="relative">
    <textarea
      ref="textarea"
      v-model="content"
      @input="handleInput"
      @keydown="handleKeydown"
      @keyup="handleKeyup"
      @click="handleClick"
      :placeholder="placeholder"
      :class="textareaClass"
      rows="3"
    />
    
    <!-- Mention suggestions dropdown -->
    <div
      v-if="showSuggestions && suggestions.length > 0"
      class="absolute z-50 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg max-h-48 overflow-y-auto"
      :style="suggestionStyle"
    >
      <div
        v-for="(user, index) in suggestions"
        :key="user.id"
        @click="selectUser(user)"
        :class="[
          'px-3 py-2 cursor-pointer flex items-center gap-2 hover:bg-gray-100 dark:hover:bg-gray-700',
          index === selectedIndex ? 'bg-blue-50 dark:bg-blue-900/20' : ''
        ]"
      >
        <div v-if="user.avatar" class="w-6 h-6 rounded-full overflow-hidden">
          <img :src="user.avatar" :alt="user.name" class="w-full h-full object-cover" />
        </div>
        <div v-else class="w-6 h-6 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center">
          <span class="text-xs font-medium text-gray-600 dark:text-gray-300">
            {{ user.name.charAt(0).toUpperCase() }}
          </span>
        </div>
        <div>
          <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ user.name }}</div>
          <div class="text-xs text-gray-500 dark:text-gray-400">{{ user.email }}</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue';
import { mentionService, type MentionUser } from '@/services/mentionService';

interface Props {
  modelValue: string;
  placeholder?: string;
  textareaClass?: string;
  users: MentionUser[];
}

const props = withDefaults(defineProps<Props>(), {
  placeholder: 'Add a comment...',
  textareaClass: 'w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100'
});

const emit = defineEmits<{
  'update:modelValue': [value: string];
  'mention': [mentions: { userId: number; userName: string }[]];
}>();

const textarea = ref<HTMLTextAreaElement>();
const content = ref(props.modelValue);
const showSuggestions = ref(false);
const suggestions = ref<MentionUser[]>([]);
const selectedIndex = ref(0);
const mentionStart = ref(0);
const mentionQuery = ref('');

// Update mention service with users
watch(() => props.users, (users) => {
  mentionService.setUsers(users);
}, { immediate: true });

// Watch for content changes
watch(content, (newContent) => {
  emit('update:modelValue', newContent);
  
  // Extract mentions for notifications
  const mentions = mentionService.extractMentions(newContent);
  if (mentions.length > 0) {
    emit('mention', mentions);
  }
});

const suggestionStyle = computed(() => {
  if (!textarea.value) return {};
  
  const rect = textarea.value.getBoundingClientRect();
  const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
  
  return {
    top: `${rect.bottom + scrollTop}px`,
    left: `${rect.left}px`,
    width: `${rect.width}px`
  };
});

const handleInput = () => {
  const cursorPosition = textarea.value?.selectionStart || 0;
  const beforeCursor = content.value.substring(0, cursorPosition);
  
  // Check if we're typing a mention
  const mentionMatch = beforeCursor.match(/@(\w*)$/);
  
  if (mentionMatch) {
    mentionStart.value = cursorPosition - mentionMatch[0].length;
    mentionQuery.value = mentionMatch[1];
    suggestions.value = mentionService.searchUsers(mentionQuery.value);
    showSuggestions.value = suggestions.value.length > 0;
    selectedIndex.value = 0;
  } else {
    showSuggestions.value = false;
  }
};

const handleKeydown = (event: KeyboardEvent) => {
  if (!showSuggestions.value) return;

  switch (event.key) {
    case 'ArrowDown':
      event.preventDefault();
      selectedIndex.value = Math.min(selectedIndex.value + 1, suggestions.value.length - 1);
      break;
    case 'ArrowUp':
      event.preventDefault();
      selectedIndex.value = Math.max(selectedIndex.value - 1, 0);
      break;
    case 'Enter':
      event.preventDefault();
      if (suggestions.value[selectedIndex.value]) {
        selectUser(suggestions.value[selectedIndex.value]);
      }
      break;
    case 'Escape':
      showSuggestions.value = false;
      break;
  }
};

const handleKeyup = () => {
  // Handle input is called on keyup as well
};

const handleClick = () => {
  // Handle input is called on click as well
};

const selectUser = (user: MentionUser) => {
  if (!textarea.value) return;

  const cursorPosition = textarea.value.selectionStart || 0;
  const beforeMention = content.value.substring(0, mentionStart.value);
  const afterMention = content.value.substring(cursorPosition);
  
  // Replace the @query with @username
  const mentionText = `@${user.name}`;
  content.value = beforeMention + mentionText + afterMention;
  
  // Move cursor after the mention
  const newCursorPosition = beforeMention.length + mentionText.length;
  
  nextTick(() => {
    if (textarea.value) {
      textarea.value.setSelectionRange(newCursorPosition, newCursorPosition);
      textarea.value.focus();
    }
  });
  
  showSuggestions.value = false;
};

// Close suggestions when clicking outside
const handleClickOutside = (event: MouseEvent) => {
  if (textarea.value && !textarea.value.contains(event.target as Node)) {
    showSuggestions.value = false;
  }
};

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
});
</script>

<style scoped>
.mention {
  @apply bg-blue-100 dark:bg-blue-900/20 text-blue-800 dark:text-blue-200 px-1 py-0.5 rounded text-sm font-medium;
}
</style>
