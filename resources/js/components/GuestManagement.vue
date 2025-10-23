<template>
  <div class="guest-management">
    <!-- Guest Management Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">Guest Access</h2>
        <p class="text-sm text-gray-600 dark:text-gray-400">Manage external collaborators and their project access</p>
      </div>
      <button
        @click="showInviteModal = true"
        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
      >
        <Icon name="UserPlus" class="w-4 h-4 inline mr-2" />
        Invite Guest
      </button>
    </div>

    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
      <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
        <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stats.total }}</div>
        <div class="text-sm text-gray-600 dark:text-gray-400">Total Guests</div>
      </div>
      <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
        <div class="text-2xl font-bold text-green-600">{{ stats.active }}</div>
        <div class="text-sm text-gray-600 dark:text-gray-400">Active</div>
      </div>
      <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
        <div class="text-2xl font-bold text-yellow-600">{{ stats.pending }}</div>
        <div class="text-sm text-gray-600 dark:text-gray-400">Pending</div>
      </div>
      <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
        <div class="text-2xl font-bold text-red-600">{{ stats.expired }}</div>
        <div class="text-sm text-gray-600 dark:text-gray-400">Expired</div>
      </div>
    </div>

    <!-- Guests List -->
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
      <div class="p-4 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Guests & Invitations</h3>
      </div>
      
      <div class="divide-y divide-gray-200 dark:divide-gray-700">
        <!-- Active Guests -->
        <div
          v-for="guest in activeGuests"
          :key="guest.id"
          class="p-4 flex items-center justify-between"
        >
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-green-100 dark:bg-green-900/20 flex items-center justify-center">
              <Icon name="User" class="w-5 h-5 text-green-600 dark:text-green-400" />
            </div>
            <div>
              <div class="font-medium text-gray-900 dark:text-gray-100">{{ guest.name }}</div>
              <div class="text-sm text-gray-600 dark:text-gray-400">{{ guest.email }}</div>
              <div class="text-xs text-gray-500 dark:text-gray-400">
                Access to {{ guest.projects.length }} project{{ guest.projects.length !== 1 ? 's' : '' }}
              </div>
            </div>
          </div>
          
          <div class="flex items-center gap-2">
            <span class="text-xs px-2 py-1 bg-green-100 dark:bg-green-900/20 text-green-800 dark:text-green-200 rounded-full">
              Active
            </span>
            <button
              @click="editGuest(guest)"
              class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
            >
              <Icon name="Settings" class="w-4 h-4" />
            </button>
            <button
              @click="revokeGuest(guest.id)"
              class="p-1 text-red-400 hover:text-red-600"
            >
              <Icon name="UserX" class="w-4 h-4" />
            </button>
          </div>
        </div>

        <!-- Pending Invitations -->
        <div
          v-for="invitation in pendingInvitations"
          :key="invitation.id"
          class="p-4 flex items-center justify-between"
        >
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-yellow-100 dark:bg-yellow-900/20 flex items-center justify-center">
              <Icon name="Mail" class="w-5 h-5 text-yellow-600 dark:text-yellow-400" />
            </div>
            <div>
              <div class="font-medium text-gray-900 dark:text-gray-100">{{ invitation.email }}</div>
              <div class="text-sm text-gray-600 dark:text-gray-400">
                Invited {{ formatDate(invitation.invited_at) }}
              </div>
              <div class="text-xs text-gray-500 dark:text-gray-400">
                Access to {{ invitation.projects.length }} project{{ invitation.projects.length !== 1 ? 's' : '' }}
              </div>
            </div>
          </div>
          
          <div class="flex items-center gap-2">
            <span class="text-xs px-2 py-1 bg-yellow-100 dark:bg-yellow-900/20 text-yellow-800 dark:text-yellow-200 rounded-full">
              Pending
            </span>
            <button
              @click="resendInvitation(invitation.id)"
              class="p-1 text-blue-400 hover:text-blue-600"
            >
              <Icon name="RefreshCw" class="w-4 h-4" />
            </button>
            <button
              @click="cancelInvitation(invitation.id)"
              class="p-1 text-red-400 hover:text-red-600"
            >
              <Icon name="X" class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Invite Guest Modal -->
    <div v-if="showInviteModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="closeInviteModal">
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-hidden">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
          <div class="flex justify-between items-center">
            <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">Invite Guest</h2>
            <button @click="closeInviteModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
              <Icon name="X" class="w-6 h-6" />
            </button>
          </div>
        </div>

        <div class="p-6 overflow-y-auto max-h-[60vh]">
          <form @submit.prevent="sendInvitation" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email Address</label>
              <input
                v-model="inviteForm.email"
                type="email"
                required
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                placeholder="guest@example.com"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name (Optional)</label>
              <input
                v-model="inviteForm.name"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                placeholder="Guest Name"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Projects</label>
              <div class="space-y-2 max-h-32 overflow-y-auto border border-gray-300 dark:border-gray-600 rounded-lg p-2">
                <label
                  v-for="project in availableProjects"
                  :key="project.id"
                  class="flex items-center gap-2 cursor-pointer"
                >
                  <input
                    v-model="inviteForm.projects"
                    :value="project.id"
                    type="checkbox"
                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                  />
                  <span class="text-sm text-gray-700 dark:text-gray-300">{{ project.name }}</span>
                </label>
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Permissions</label>
              <div class="space-y-2">
                <label
                  v-for="permission in availablePermissions"
                  :key="permission.id"
                  class="flex items-center gap-2 cursor-pointer"
                >
                  <input
                    v-model="inviteForm.permissions"
                    :value="permission.id"
                    type="checkbox"
                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                  />
                  <div>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ permission.name }}</span>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ permission.description }}</p>
                  </div>
                </label>
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Message (Optional)</label>
              <textarea
                v-model="inviteForm.message"
                rows="3"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                placeholder="Welcome message for the guest..."
              />
            </div>
          </form>
        </div>

        <div class="p-6 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3">
          <button
            @click="closeInviteModal"
            class="px-4 py-2 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
          >
            Cancel
          </button>
          <button
            @click="sendInvitation"
            :disabled="!inviteForm.email || inviteForm.projects.length === 0"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          >
            Send Invitation
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import Icon from '@/components/Icon.vue';
import { guestAccessService, type GuestUser, type GuestInvitation } from '@/services/guestAccessService';

interface Props {
  companyId: number;
  projects: Array<{ id: number; name: string }>;
}

const props = defineProps<Props>();

const showInviteModal = ref(false);
const inviteForm = ref({
  email: '',
  name: '',
  projects: [] as number[],
  permissions: [] as number[],
  message: ''
});

const guests = ref<GuestUser[]>([]);
const invitations = ref<GuestInvitation[]>([]);
const availablePermissions = ref([
  { id: 1, name: 'View Only', description: 'Can view todos and comments', actions: ['view'] },
  { id: 2, name: 'Comment', description: 'Can view and add comments', actions: ['view', 'comment'] },
  { id: 3, name: 'Edit', description: 'Can view, comment, and edit todos', actions: ['view', 'comment', 'edit'] }
]);

const activeGuests = computed(() => guestAccessService.getActiveGuests(props.companyId));
const pendingInvitations = computed(() => guestAccessService.getPendingInvitations(props.companyId));
const stats = computed(() => guestAccessService.getGuestStats(props.companyId));
const availableProjects = computed(() => props.projects);

const sendInvitation = async () => {
  if (!inviteForm.value.email || inviteForm.value.projects.length === 0) return;

  const invitation = await guestAccessService.createInvitation({
    email: inviteForm.value.email,
    name: inviteForm.value.name,
    company_id: props.companyId,
    invited_by: 1, // Current user ID
    expires_at: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString(), // 7 days
    permissions: availablePermissions.value.filter(p => inviteForm.value.permissions.includes(p.id)),
    projects: inviteForm.value.projects,
    message: inviteForm.value.message
  });

  if (invitation) {
    closeInviteModal();
    loadData();
  }
};

const closeInviteModal = () => {
  showInviteModal.value = false;
  inviteForm.value = {
    email: '',
    name: '',
    projects: [],
    permissions: [],
    message: ''
  };
};

const editGuest = (guest: GuestUser) => {
  // TODO: Implement guest editing
  console.log('Edit guest:', guest);
};

const revokeGuest = async (guestId: number) => {
  if (confirm('Are you sure you want to revoke this guest\'s access?')) {
    await guestAccessService.revokeGuestAccess(guestId);
    loadData();
  }
};

const resendInvitation = async (invitationId: number) => {
  await guestAccessService.resendInvitation(invitationId);
};

const cancelInvitation = async (invitationId: number) => {
  if (confirm('Are you sure you want to cancel this invitation?')) {
    await guestAccessService.cancelInvitation(invitationId);
    loadData();
  }
};

const formatDate = (dateString: string): string => {
  const date = new Date(dateString);
  return date.toLocaleDateString();
};

const loadData = () => {
  guests.value = guestAccessService.getCompanyGuests(props.companyId);
  invitations.value = guestAccessService.getPendingInvitations(props.companyId);
};

onMounted(() => {
  loadData();
});
</script>
