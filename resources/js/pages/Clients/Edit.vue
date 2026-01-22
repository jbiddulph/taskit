<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Icon from '@/components/Icon.vue';
import { update, show } from '@/routes/clients/index.ts';
import { ref } from 'vue';
import SeoHead from '@/components/SeoHead.vue';

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

const form = useForm({
  name: client.name || '',
  key_contact_name: client.key_contact_name || '',
  key_contact_email: client.key_contact_email || '',
  key_contact_phone: client.key_contact_phone || '',
  address_line_1: client.address_line_1 || '',
  address_line_2: client.address_line_2 || '',
  city: client.city || '',
  state_province: client.state_province || '',
  postal_code: client.postal_code || '',
  country: client.country || '',
  website: client.website || '',
  notes: client.notes || ''
});

const submit = () => {
  form.patch(update.url(client.id), {
    onSuccess: () => {
      // Redirect will be handled by the controller
    }
  });
};

// Dashboard action states
const showCalendar = ref(false);
const showActivityFeed = ref(false);
const isSelectMode = ref(false);

const toggleCalendar = () => {
  showCalendar.value = !showCalendar.value;
};

const toggleActivityFeed = () => {
  showActivityFeed.value = !showActivityFeed.value;
};

const toggleSelectMode = () => {
  isSelectMode.value = !isSelectMode.value;
};
</script>

<template>
  <SeoHead
    :title="`Edit ${client.name}`"
    :description="`Update details for ${client.name} in ZapTask.`"
    image="/zap_icon.png"
  />

  <AppLayout :company="company">
    <template #dashboardActions>
      <!-- Calendar Button -->
      <button
        @click="toggleCalendar"
        :title="showCalendar ? 'Hide Calendar' : 'Show Calendar'"
        :class="[
          'inline-flex items-center justify-center p-2 transition-colors',
          showCalendar 
            ? 'text-blue-600 dark:text-blue-400' 
            : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100'
        ]"
      >
        <Icon name="Calendar" class="w-5 h-5" />
      </button>

      <!-- Activity Feed Toggle Button -->
      <button
        @click="toggleActivityFeed"
        :title="showActivityFeed ? 'Hide Activity Feed' : 'Show Activity Feed'"
        :class="[
          'inline-flex items-center justify-center p-2 transition-colors',
          showActivityFeed 
            ? 'text-blue-600 dark:text-blue-400' 
            : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100'
        ]"
      >
        <Icon name="Activity" class="w-5 h-5" />
      </button>

      <!-- Select for Bulk Update Button -->
      <button
        @click="toggleSelectMode"
        :title="isSelectMode ? 'Exit Select Mode' : 'Select for Bulk Update'"
        :class="[
          'inline-flex items-center justify-center p-2 transition-colors',
          isSelectMode 
            ? 'text-blue-600 dark:text-blue-400' 
            : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100'
        ]"
      >
        <Icon name="CheckSquare" class="w-5 h-5" />
      </button>
    </template>
    <div class="py-12">
      <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 text-gray-900 dark:text-gray-100">
            <!-- Header -->
            <div class="mb-6">
              <div class="flex items-center gap-4 mb-4">
                <Link
                  :href="show.url(client.id)"
                  class="flex items-center gap-2 text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100"
                >
                  <Icon name="ArrowLeft" class="w-4 h-4" />
                  Back to {{ client.name }}
                </Link>
              </div>
              
              <h1 class="text-2xl font-semibold">Edit Client</h1>
              <p class="text-gray-600 dark:text-gray-400 mt-1">
                Update {{ client.name }}'s information
              </p>
            </div>

            <!-- Form -->
            <form @submit.prevent="submit" class="space-y-6">
              <!-- Client Name -->
              <div>
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Client Name *
                </label>
                <input
                  id="name"
                  v-model="form.name"
                  type="text"
                  required
                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                  placeholder="Enter client company name"
                />
                <div v-if="form.errors.name" class="text-red-500 text-sm mt-1">{{ form.errors.name }}</div>
              </div>

              <!-- Contact Information -->
              <div class="grid md:grid-cols-2 gap-4">
                <div>
                  <label for="key_contact_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Key Contact Name
                  </label>
                  <input
                    id="key_contact_name"
                    v-model="form.key_contact_name"
                    type="text"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                    placeholder="Primary contact person"
                  />
                  <div v-if="form.errors.key_contact_name" class="text-red-500 text-sm mt-1">{{ form.errors.key_contact_name }}</div>
                </div>

                <div>
                  <label for="key_contact_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Contact Email
                  </label>
                  <input
                    id="key_contact_email"
                    v-model="form.key_contact_email"
                    type="email"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                    placeholder="contact@company.com"
                  />
                  <div v-if="form.errors.key_contact_email" class="text-red-500 text-sm mt-1">{{ form.errors.key_contact_email }}</div>
                </div>
              </div>

              <div>
                <label for="key_contact_phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Contact Phone
                </label>
                <input
                  id="key_contact_phone"
                  v-model="form.key_contact_phone"
                  type="tel"
                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                  placeholder="+1 (555) 123-4567"
                />
                <div v-if="form.errors.key_contact_phone" class="text-red-500 text-sm mt-1">{{ form.errors.key_contact_phone }}</div>
              </div>

              <!-- Address -->
              <fieldset class="border border-gray-200 dark:border-gray-600 rounded-md p-4">
                <legend class="text-sm font-medium text-gray-700 dark:text-gray-300 px-2">Address</legend>
                
                <div class="space-y-4">
                  <div>
                    <label for="address_line_1" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                      Address Line 1
                    </label>
                    <input
                      id="address_line_1"
                      v-model="form.address_line_1"
                      type="text"
                      class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                      placeholder="Street address"
                    />
                    <div v-if="form.errors.address_line_1" class="text-red-500 text-sm mt-1">{{ form.errors.address_line_1 }}</div>
                  </div>

                  <div>
                    <label for="address_line_2" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                      Address Line 2
                    </label>
                    <input
                      id="address_line_2"
                      v-model="form.address_line_2"
                      type="text"
                      class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                      placeholder="Apartment, suite, etc. (optional)"
                    />
                    <div v-if="form.errors.address_line_2" class="text-red-500 text-sm mt-1">{{ form.errors.address_line_2 }}</div>
                  </div>

                  <div class="grid md:grid-cols-3 gap-4">
                    <div>
                      <label for="city" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        City
                      </label>
                      <input
                        id="city"
                        v-model="form.city"
                        type="text"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                        placeholder="City"
                      />
                      <div v-if="form.errors.city" class="text-red-500 text-sm mt-1">{{ form.errors.city }}</div>
                    </div>

                    <div>
                      <label for="state_province" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        State/Province
                      </label>
                      <input
                        id="state_province"
                        v-model="form.state_province"
                        type="text"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                        placeholder="State/Province"
                      />
                      <div v-if="form.errors.state_province" class="text-red-500 text-sm mt-1">{{ form.errors.state_province }}</div>
                    </div>

                    <div>
                      <label for="postal_code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Postal Code
                      </label>
                      <input
                        id="postal_code"
                        v-model="form.postal_code"
                        type="text"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                        placeholder="Postal code"
                      />
                      <div v-if="form.errors.postal_code" class="text-red-500 text-sm mt-1">{{ form.errors.postal_code }}</div>
                    </div>
                  </div>

                  <div>
                    <label for="country" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                      Country
                    </label>
                    <input
                      id="country"
                      v-model="form.country"
                      type="text"
                      class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                      placeholder="Country"
                    />
                    <div v-if="form.errors.country" class="text-red-500 text-sm mt-1">{{ form.errors.country }}</div>
                  </div>
                </div>
              </fieldset>

              <!-- Website -->
              <div>
                <label for="website" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Website
                </label>
                <input
                  id="website"
                  v-model="form.website"
                  type="url"
                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                  placeholder="https://company.com"
                />
                <div v-if="form.errors.website" class="text-red-500 text-sm mt-1">{{ form.errors.website }}</div>
              </div>

              <!-- Notes -->
              <div>
                <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Notes
                </label>
                <textarea
                  id="notes"
                  v-model="form.notes"
                  rows="4"
                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                  placeholder="Additional notes about this client..."
                ></textarea>
                <div v-if="form.errors.notes" class="text-red-500 text-sm mt-1">{{ form.errors.notes }}</div>
              </div>

              <!-- Actions -->
              <div class="flex gap-4 pt-4">
                <button
                  type="submit"
                  :disabled="form.processing"
                  class="flex-1 inline-flex items-center justify-center gap-2 rounded-md px-4 py-2 text-sm font-medium border transition-colors cursor-pointer bg-black text-white hover:bg-gray-900 hover:border-gray-900 dark:bg-white dark:text-black dark:hover:bg-gray-100 dark:hover:border-gray-300 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  <span v-if="form.processing">Updating...</span>
                  <span v-else>Update Client</span>
                </button>
                
                <button
                  type="button"
                  @click="$inertia.visit(show.url(client.id))"
                  class="inline-flex items-center justify-center gap-2 rounded-md px-4 py-2 text-sm font-medium border transition-colors cursor-pointer bg-black/30 text-black dark:bg-white/30 dark:text-white"
                >
                  Cancel
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
