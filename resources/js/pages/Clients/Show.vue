<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Icon from '@/components/Icon.vue';
import { index, edit, destroy } from '@/routes/clients';
import { ref } from 'vue';

interface Client {
  id: number;
  name: string;
  key_contact_name?: string;
  key_contact_email?: string;
  key_contact_phone?: string;
  address_line_1?: string;
  address_line_2?: string;
  city?: string;
  state_province?: string;
  postal_code?: string;
  country?: string;
  website?: string;
  notes?: string;
  created_by: string;
  created_at: string;
  full_address: string;
  stats: {
    total_projects: number;
    total_todos: number;
    completed_todos: number;
    completion_rate: number;
  };
  projects: Array<{
    id: number;
    name: string;
    key: string;
    color: string;
    stats: {
      total: number;
      completed: number;
      completion_rate: number;
    };
  }>;
}

interface Props {
  client: Client;
  company?: {
    id: number;
    name: string;
    code: string;
    subscription_type: string;
  } | null;
}

const { client } = defineProps<Props>();

// Project filter state (for future expansion if needed)
// Currently this page already shows only this client's projects,
// but we still dispatch a sidebar filter event on mount so the
// left sidebar only shows this client's projects.
if (typeof window !== 'undefined') {
  window.dispatchEvent(
    new CustomEvent('clientFilterChanged', {
      detail: { clientId: client.id, clientName: client.name },
    }),
  );
}

const deleteForm = useForm({});

const deleteClient = () => {
  if (confirm('Are you sure you want to delete this client? This will remove the client association from all projects but will not delete the projects themselves.')) {
deleteForm.delete(destroy.url(client.id));
  }
};
</script>

<template>
  <Head :title="client.name" />

  <AppLayout :company="company">
    <div class="py-12">
      <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-6">
          <div class="flex items-center gap-4 mb-4">
            <Link
              :href="index.url()"
              class="flex items-center gap-2 text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100"
            >
              <Icon name="ArrowLeft" class="w-4 h-4" />
              Back to Clients
            </Link>
          </div>
          
          <div class="flex justify-between items-start">
            <div>
              <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ client.name }}</h1>
              <p class="text-gray-600 dark:text-gray-400 mt-1">
                Added by {{ client.created_by }} on {{ client.created_at }}
              </p>
            </div>
            
            <div class="flex gap-3">
              <Link
                :href="edit.url(client.id)"
                class="inline-flex items-center gap-2 rounded-md px-4 py-2 text-sm font-medium border transition-colors cursor-pointer bg-black text-white hover:bg-gray-900 hover:border-gray-900 dark:bg-white dark:text-black dark:hover:bg-gray-100 dark:hover:border-gray-300"
              >
                <Icon name="Edit" class="w-4 h-4" />
                Edit
              </Link>
              
              <button
                @click="deleteClient"
                :disabled="deleteForm.processing"
                class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <Icon name="Trash2" class="w-4 h-4" />
                <span v-if="deleteForm.processing">Deleting...</span>
                <span v-else>Delete</span>
              </button>
            </div>
          </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
          <!-- Client Details -->
          <div class="lg:col-span-2 space-y-6">
            <!-- Contact Information -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
              <div class="p-6">
                <h2 class="text-lg font-semibold mb-4">Contact Information</h2>
                
                <div class="space-y-4">
                  <div v-if="client.key_contact_name" class="flex items-center gap-3">
                    <Icon name="User" class="w-5 h-5 text-gray-500" />
                    <div>
                      <div class="text-sm text-gray-500">Key Contact</div>
                      <div class="font-medium">{{ client.key_contact_name }}</div>
                    </div>
                  </div>
                  
                  <div v-if="client.key_contact_email" class="flex items-center gap-3">
                    <Icon name="Mail" class="w-5 h-5 text-gray-500" />
                    <div>
                      <div class="text-sm text-gray-500">Email</div>
                      <a 
                        :href="`mailto:${client.key_contact_email}`"
                        class="font-medium text-black hover:text-gray-900 dark:text-white dark:hover:text-gray-100"
                      >
                        {{ client.key_contact_email }}
                      </a>
                    </div>
                  </div>
                  
                  <div v-if="client.key_contact_phone" class="flex items-center gap-3">
                    <Icon name="Phone" class="w-5 h-5 text-gray-500" />
                    <div>
                      <div class="text-sm text-gray-500">Phone</div>
                      <a 
                        :href="`tel:${client.key_contact_phone}`"
                        class="font-medium text-black hover:text-gray-900 dark:text-white dark:hover:text-gray-100"
                      >
                        {{ client.key_contact_phone }}
                      </a>
                    </div>
                  </div>
                  
                  <div v-if="client.full_address" class="flex items-start gap-3">
                    <Icon name="MapPin" class="w-5 h-5 text-gray-500 mt-0.5" />
                    <div>
                      <div class="text-sm text-gray-500">Address</div>
                      <div class="font-medium whitespace-pre-line">{{ client.full_address }}</div>
                    </div>
                  </div>
                  
                  <div v-if="client.website" class="flex items-center gap-3">
                    <Icon name="Globe" class="w-5 h-5 text-gray-500" />
                    <div>
                      <div class="text-sm text-gray-500">Website</div>
                      <a 
                        :href="client.website" 
                        target="_blank" 
                        class="font-medium text-black hover:text-gray-900 dark:text-white dark:hover:text-gray-100"
                      >
                        {{ client.website.replace(/^https?:\/\//, '') }}
                        <Icon name="ExternalLink" class="w-3 h-3 inline ml-1" />
                      </a>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Notes -->
            <div v-if="client.notes" class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
              <div class="p-6">
                <h2 class="text-lg font-semibold mb-4">Notes</h2>
                <div class="prose dark:prose-invert max-w-none">
                  <p class="whitespace-pre-wrap">{{ client.notes }}</p>
                </div>
              </div>
            </div>

            <!-- Projects -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
              <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                  <h2 class="text-lg font-semibold">Projects</h2>
                  <!-- Project Filter aligned right (currently fixed to this client) -->
                  <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                    <span>Project Filter:</span>
                    <select
                      class="rounded-md border border-gray-300 bg-white px-2 py-1 text-xs text-gray-900 shadow-sm focus:border-gray-900 focus:outline-none focus:ring-1 focus:ring-gray-900 dark:bg-gray-800 dark:text-gray-100 dark:border-gray-600 dark:focus:border-gray-100 dark:focus:ring-gray-100"
                      :value="client.id"
                      @change="() => {
                        if (typeof window !== 'undefined') {
                          window.dispatchEvent(new CustomEvent('clientFilterChanged', {
                            detail: { clientId: client.id, clientName: client.name },
                          }));
                        }
                      }"
                    >
                      <option :value="client.id">{{ client.name }}</option>
                    </select>
                  </div>
                </div>
                
                <div v-if="client.projects.length > 0" class="space-y-3">
                  <div
                    v-for="project in client.projects"
                    :key="project.id"
                    class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg"
                  >
                    <div class="flex items-center gap-3">
                      <div 
                        class="w-3 h-3 rounded-full"
                        :style="{ backgroundColor: project.color }"
                      ></div>
                      <div>
                        <div class="font-medium">{{ project.name }}</div>
                        <div class="text-sm text-gray-500">{{ project.key }}</div>
                      </div>
                    </div>
                    
                    <div class="text-right text-sm">
                      <div>{{ project.stats.completed }}/{{ project.stats.total }} tasks</div>
                      <div class="text-gray-500">{{ project.stats.completion_rate }}% complete</div>
                    </div>
                  </div>
                </div>
                
                <div v-else class="text-center py-8 text-gray-500">
                  No projects assigned to this client yet.
                </div>
              </div>
            </div>
          </div>

          <!-- Stats Sidebar -->
          <div class="space-y-6">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
              <div class="p-6">
                <h2 class="text-lg font-semibold mb-4">Statistics</h2>
                
                <div class="space-y-4">
                  <div class="text-center">
                    <div class="text-3xl font-bold text-blue-600">{{ client.stats.total_projects }}</div>
                    <div class="text-sm text-gray-500">Projects</div>
                  </div>
                  
                  <div class="text-center">
                    <div class="text-3xl font-bold text-green-600">{{ client.stats.total_todos }}</div>
                    <div class="text-sm text-gray-500">Total Tasks</div>
                  </div>
                  
                  <div class="text-center">
                    <div class="text-3xl font-bold text-purple-600">{{ client.stats.completed_todos }}</div>
                    <div class="text-sm text-gray-500">Completed</div>
                  </div>
                  
                  <div class="text-center">
                    <div class="text-3xl font-bold text-orange-600">{{ client.stats.completion_rate }}%</div>
                    <div class="text-sm text-gray-500">Completion Rate</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
