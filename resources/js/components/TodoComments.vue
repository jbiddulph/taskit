<template>
  <div class="mt-6">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">{{ t('comments.title') }}</h3>

    <!-- New comment form -->
    <form @submit.prevent="handleAdd" class="mb-4">
      <MentionInput
        v-model="newComment"
        :users="availableUsers"
        :placeholder="t('comments.write_comment')"
        @mention="handleMentions"
        class="mb-2"
      />
      <div class="flex justify-end">
        <button
          type="submit"
          :disabled="adding || !newComment.trim()"
          class="px-4 py-2 bg-blue-600 text-white rounded-md disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {{ adding ? t('comments.adding') : t('comments.add_comment') }}
        </button>
      </div>
    </form>

    <!-- Comments list -->
    <div v-if="loading" class="text-sm text-gray-500">{{ t('comments.loading') }}</div>
    <div v-else>
      <div v-if="comments.length === 0" class="text-sm text-gray-500">{{ t('comments.no_comments') }}</div>
      <ul class="space-y-3">
        <li 
          v-for="comment in comments" 
          :key="comment.id" 
          :data-comment-id="comment.id"
          class="p-3 rounded-md border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 transition-all duration-300"
          :class="{
            'ring-2 ring-blue-500 bg-blue-50 dark:bg-blue-900/20 border-blue-300 dark:border-blue-600': highlightedCommentId === comment.id
          }"
        >
          <div class="flex items-start justify-between gap-3">
            <div class="flex-1">
              <div class="mb-1 text-xs text-gray-600 dark:text-gray-400">
                <span class="font-medium text-gray-800 dark:text-gray-200">{{ comment.user?.name || t('comments.user') }}</span>
                <span class="mx-1">·</span>
                <span>{{ formatDate(comment.created_at) }}</span>
              </div>
              <div v-if="editingId === comment.id" class="flex gap-2">
                <input
                  v-model="editContent"
                  type="text"
                  class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                />
                <button @click="saveEdit(comment)" class="px-3 py-2 bg-green-600 text-white rounded-md">{{ t('common.save') }}</button>
                <button @click="cancelEdit" class="px-3 py-2 bg-gray-300 text-gray-800 rounded-md">{{ t('common.cancel') }}</button>
              </div>
              <div v-else class="text-sm text-gray-800 dark:text-gray-100 whitespace-pre-line">{{ comment.content }}</div>
            </div>
            <div class="flex gap-2">
              <button
                v-if="editingId !== comment.id && comment.user?.id === currentUser?.id"
                @click="startEdit(comment)"
                class="text-gray-500 hover:text-blue-600 text-sm"
              >{{ t('common.edit') }}</button>
              <button
                v-if="comment.user?.id === currentUser?.id"
                @click="remove(comment)"
                class="text-gray-500 hover:text-red-600 text-sm"
              >{{ t('common.delete') }}</button>
            </div>
          </div>
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed, watch, nextTick } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { todoApi, type TodoComment } from '@/services/todoApi';
import MentionInput from '@/components/MentionInput.vue';
import { mentionService } from '@/services/mentionService';
import { useCompanyUsers } from '@/composables/useCompanyUsers';
import { useI18n } from 'vue-i18n';
const { t } = useI18n();

interface Props {
  todoId: number;
}

const props = defineProps<Props>();

const page = usePage();
const currentUser = (page.props as any)?.auth?.user || null;

const loading = ref(false);
const adding = ref(false);
const newComment = ref('');
const comments = ref<TodoComment[]>([]);

const editingId = ref<number | null>(null);
const editContent = ref('');
const highlightedCommentId = ref<number | null>(null);

// Load company users for mentions
const { users: availableUsers, loadUsers } = useCompanyUsers();

// Update mention service when users are loaded
watch(availableUsers, (users) => {
  if (users.length > 0) {
    mentionService.setUsers(users);
  }
}, { immediate: true });

// Handle mentions in comments
const handleMentions = (mentions: { userId: number; userName: string }[]) => {
  console.log('Mentions detected:', mentions);
  // In a real app, this would trigger notifications
};

const load = async () => {
  // Skip loading if todoId is 0 (new todo)
  if (props.todoId === 0) {
    comments.value = [];
    loading.value = false;
    return;
  }
  
  try {
    loading.value = true;
    const loadedComments = await todoApi.getComments(props.todoId);
    
    // Sort comments by creation date (newest first)
    comments.value = loadedComments.sort((a, b) => 
      new Date(b.created_at).getTime() - new Date(a.created_at).getTime()
    );
    
    // Check for highlight parameter in URL
    const urlParams = new URLSearchParams(window.location.search);
    const highlightId = urlParams.get('highlight');
    if (highlightId) {
      highlightedCommentId.value = parseInt(highlightId);
      // Scroll to highlighted comment after a short delay
      await nextTick();
      setTimeout(() => {
        const commentElement = document.querySelector(`[data-comment-id="${highlightId}"]`);
        if (commentElement) {
          commentElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
      }, 100);
      
      // Clear highlight after 5 seconds and remove URL parameter
      setTimeout(() => {
        highlightedCommentId.value = null;
        // Remove highlight parameter from URL without page reload
        const newUrl = new URL(window.location.href);
        newUrl.searchParams.delete('highlight');
        window.history.replaceState({}, '', newUrl.toString());
      }, 5000);
    }
  } catch (e) {
    console.error('Failed to load comments', e);
  } finally {
    loading.value = false;
  }
};

const handleAdd = async () => {
  if (!newComment.value.trim()) return;
  
  // For new todos (todoId = 0), just add to local state
  if (props.todoId === 0) {
    const newCommentObj = {
      id: Date.now(), // Temporary ID
      content: newComment.value.trim(),
      user: currentUser ? { id: currentUser.id, name: currentUser.name, email: currentUser.email } : null,
      created_at: new Date().toISOString(),
      updated_at: new Date().toISOString()
    };
    comments.value.unshift(newCommentObj);
    newComment.value = '';
    return;
  }
  
  try {
    adding.value = true;
    const created = await todoApi.addComment(props.todoId, newComment.value.trim());
    // Ensure user info is present for immediate display
    if (!created.user && currentUser) {
      created.user = { id: currentUser.id, name: currentUser.name, email: currentUser.email };
    }
    comments.value.unshift(created);
    newComment.value = '';
    (window as any).$notify?.({ type: 'success', title: 'Comment Added', message: 'Your comment was added.' });
  } catch (e) {
    console.error('Failed to add comment', e);
    (window as any).$notify?.({ type: 'error', title: 'Add Failed', message: 'Could not add comment.' });
  } finally {
    adding.value = false;
  }
};

const startEdit = (c: TodoComment) => {
  editingId.value = c.id as unknown as number;
  editContent.value = c.content;
};

const cancelEdit = () => {
  editingId.value = null;
  editContent.value = '';
};

const saveEdit = async (c: TodoComment) => {
  if (!editContent.value.trim()) return;
  
  // For new todos (todoId = 0), just update local state
  if (props.todoId === 0) {
    const idx = comments.value.findIndex(x => String(x.id) === String(c.id));
    if (idx !== -1) {
      comments.value[idx].content = editContent.value.trim();
      comments.value[idx].updated_at = new Date().toISOString();
      comments.value = [...comments.value];
    }
    editingId.value = null;
    editContent.value = '';
    return;
  }
  
  try {
    const updated = await todoApi.updateComment(props.todoId, c.id as unknown as number, editContent.value.trim());
    const idx = comments.value.findIndex(x => String(x.id) === String(c.id));
    if (idx !== -1) {
      comments.value[idx] = updated;
      comments.value = [...comments.value];
    }
    editingId.value = null;
    editContent.value = '';
    (window as any).$notify?.({ type: 'success', title: 'Comment Updated', message: 'Your comment was updated.' });
  } catch (e) {
    console.error('Failed to update comment', e);
    (window as any).$notify?.({ type: 'error', title: 'Update Failed', message: 'Could not update comment.' });
  }
};

const remove = async (c: TodoComment) => {
  if (!confirm('Delete this comment?')) return;
  
  // For new todos (todoId = 0), just remove from local state
  if (props.todoId === 0) {
    comments.value = comments.value.filter(x => String(x.id) !== String(c.id));
    return;
  }
  
  try {
    await todoApi.deleteComment(props.todoId, c.id as unknown as number);
    comments.value = comments.value.filter(x => String(x.id) !== String(c.id));
    (window as any).$notify?.({ type: 'success', title: 'Comment Deleted', message: 'Your comment was deleted.' });
  } catch (e) {
    console.error('Failed to delete comment', e);
    (window as any).$notify?.({ type: 'error', title: 'Delete Failed', message: 'Could not delete comment.' });
  }
};

const formatDate = (dt: string) => {
  try {
    return new Date(dt).toLocaleString();
  } catch {
    return dt;
  }
};

onMounted(load);
</script>


