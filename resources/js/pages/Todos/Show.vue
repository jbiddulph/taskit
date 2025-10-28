<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue'
import TodoBoard from '@/components/TodoBoard.vue'
import TodoComments from '@/components/TodoComments.vue'
import { ref, onMounted } from 'vue'
import { todoApi, type Todo } from '@/services/todoApi'

interface Props {
  todoId: number
  highlight?: number | null
}

const props = defineProps<Props>()

const todo = ref<Todo | null>(null)
const loading = ref(false)

const load = async () => {
  loading.value = true
  try {
    todo.value = await todoApi.getTodo(props.todoId)
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>

<template>
  <Head :title="todo ? todo.title : 'Task'" />
  <AppSidebarLayout>
    <div class="p-4 md:p-6 max-w-5xl mx-auto">
      <div v-if="loading" class="text-gray-500">Loading...</div>
      <div v-else-if="!todo" class="text-gray-500">Task not found</div>
      <div v-else class="space-y-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 md:p-6">
          <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ todo.title }}</h1>
          <p v-if="todo.description" class="mt-2 text-gray-600 dark:text-gray-300 whitespace-pre-line">{{ todo.description }}</p>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 md:p-6">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Comments</h2>
          <TodoComments :todo-id="todo.id" />
        </div>
      </div>
    </div>
  </AppSidebarLayout>
  
</template>


