<script setup lang="ts">
import { Link, usePage, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import { dashboard, login, register } from '@/routes';

const page = usePage();
const user = computed(() => (page.props.auth as any)?.user);

const logout = () => {
  router.post('/logout', {
    onSuccess: () => router.visit('/'),
  });
};
</script>

<template>
  <div class="flex items-center space-x-4">
    <template v-if="user">
      <Link
        :href="dashboard()"
        class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition-colors"
      >
        Dashboard
      </Link>
      <button
        @click="logout"
        class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 transition-colors"
      >
        Logout
      </button>
    </template>
    <template v-else>
      <Link
        :href="login()"
        class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition-colors"
      >
        Sign In
      </Link>
      <Link
        :href="register()"
        class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors"
      >
        Get Started
      </Link>
    </template>
  </div>
</template>
