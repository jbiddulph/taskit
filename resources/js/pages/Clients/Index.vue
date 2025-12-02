<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Icon from '@/components/Icon.vue';
import { create, show } from '@/routes/clients';
import { ref } from 'vue';

interface Client {
  id: number;
  name: string;
  key_contact_name?: string;
  key_contact_email?: string;
  key_contact_phone?: string;
  full_address: string;
  website?: string;
  notes?: string;
  created_by: string;
  created_at: string;
  stats: {
    total_projects: number;
    total_todos: number;
    completed_todos: number;
    completion_rate: number;
  };
}

interface Props {
  clients: Client[];
  company?: {
    id: number;
    name: string;
    code: string;
    subscription_type: string;
  } | null;
}

defineProps<Props>();
</script>

<template>
  <Head title="Clients" />

  <AppLayout :company="company">
    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 text-gray-900 dark:text-gray-100">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
              <div>
                <div class="flex items-center gap-3 mb-2">
                  <Link
                    href="/dashboard"
                    class="text-black hover:text-gray-900 dark:text-white dark:hover:text-gray-100 flex items-center gap-2"
                  >
                    <Icon name="ArrowLeft" class="w-4 h-4" />
                    <span class="text-sm font-medium">Dashboard</span>
                  </Link>
                </div>
                <h1 class="text-2xl font-semibold">Clients</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">
                  Manage your client relationships and projects
                </p>
              </div>
              <Link
                :href="create.url()"
                class="inline-flex items-center gap-2 rounded-md px-4 py-2 text-sm font-medium border transition-colors cursor-pointer bg-black text-white hover:bg-gray-900 hover:border-gray-900 dark:bg-white dark:text-black dark:hover:bg-gray-100 dark:hover:border-gray-300"
              >
                <Icon name="Plus" class="w-4 h-4" />
                Add Client
              </Link>
            </div>

            <!-- Clients Grid -->
            <div v-if="clients.length > 0" class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
              <div
                v-for="client in clients"
                :key="client.id"
                class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6 border border-gray-200 dark:border-gray-600"
              >
                <div class="flex justify-between items-start mb-4">
                  <h3 class="text-lg font-semibold">{{ client.name }}</h3>
                  <Link
                    :href="show.url(client.id)"
                    class="text-black hover:text-gray-900 dark:text-white dark:hover:text-gray-100"
                  >
                    <Icon name="ExternalLink" class="w-4 h-4" />
                  </Link>
                </div>

                <div class="space-y-2 text-sm">
                  <div v-if="client.key_contact_name" class="flex items-center gap-2">
                    <Icon name="User" class="w-4 h-4 text-gray-500" />
                    <span>{{ client.key_contact_name }}</span>
                  </div>
                  
                  <div v-if="client.key_contact_email" class="flex items-center gap-2">
                    <Icon name="Mail" class="w-4 h-4 text-gray-500" />
                    <span>{{ client.key_contact_email }}</span>
                  </div>
                  
                  <div v-if="client.key_contact_phone" class="flex items-center gap-2">
                    <Icon name="Phone" class="w-4 h-4 text-gray-500" />
                    <span>{{ client.key_contact_phone }}</span>
                  </div>
                  
                  <div v-if="client.full_address" class="flex items-start gap-2">
                    <Icon name="MapPin" class="w-4 h-4 text-gray-500 mt-0.5" />
                    <span class="text-gray-600 dark:text-gray-400">{{ client.full_address }}</span>
                  </div>
                  
                  <div v-if="client.website" class="flex items-center gap-2">
                    <Icon name="Globe" class="w-4 h-4 text-gray-500" />
                    <a 
                      :href="client.website" 
                      target="_blank" 
                      class="text-black hover:text-gray-900 dark:text-white dark:hover:text-gray-100"
                    >
                      {{ client.website.replace(/^https?:\/\//, '') }}
                    </a>
                  </div>
                </div>

                <!-- Stats -->
                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-600">
                  <div class="grid grid-cols-3 gap-4 text-center">
                    <div>
                      <div class="text-xl font-semibold">{{ client.stats.total_projects }}</div>
                      <div class="text-xs text-gray-500">Projects</div>
                    </div>
                    <div>
                      <div class="text-xl font-semibold">{{ client.stats.total_todos }}</div>
                      <div class="text-xs text-gray-500">Tasks</div>
                    </div>
                    <div>
                      <div class="text-xl font-semibold">{{ client.stats.completion_rate }}%</div>
                      <div class="text-xs text-gray-500">Complete</div>
                    </div>
                  </div>
                </div>

                <!-- Footer -->
                <div class="mt-4 pt-3 border-t border-gray-200 dark:border-gray-600 flex justify-between items-center text-xs text-gray-500">
                  <span>Added by {{ client.created_by }}</span>
                  <span>{{ client.created_at }}</span>
                </div>
              </div>
            </div>

            <!-- Empty State -->
            <div v-else class="text-center py-12">
              <Icon name="Users" class="w-16 h-16 text-gray-400 mx-auto mb-4" />
              <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
                No clients yet
              </h3>
              <p class="text-gray-600 dark:text-gray-400 mb-6">
                Start by adding your first client to organize your projects better.
              </p>
              <Link
                :href="create.url()"
                class="inline-flex items-center gap-2 rounded-md px-4 py-2 text-sm font-medium border transition-colors cursor-pointer bg-black text-white hover:bg-gray-900 hover:border-gray-900 dark:bg-white dark:text-black dark:hover:bg-gray-100 dark:hover:border-gray-300"
              >
                <Icon name="Plus" class="w-4 h-4" />
                Add Your First Client
              </Link>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
