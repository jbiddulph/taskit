<template>
  <div class="time-tracker">
    <!-- Timer Display -->
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Time Tracking</h3>
        <div class="flex items-center gap-2">
          <span class="text-sm text-gray-500 dark:text-gray-400">Total: {{ formatDuration(totalTime) }}</span>
          <button
            @click="showReport = !showReport"
            class="text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300"
          >
            <Icon name="BarChart3" class="w-4 h-4" />
          </button>
        </div>
      </div>

      <!-- Active Timer -->
      <div v-if="activeTimer" class="mb-4 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-lg">
        <div class="flex items-center justify-between">
          <div>
            <div class="text-sm font-medium text-green-800 dark:text-green-200">Timer Running</div>
            <div class="text-lg font-mono text-green-900 dark:text-green-100">{{ formatDuration(runningDuration) }}</div>
          </div>
          <button
            @click="stopTimer"
            class="px-3 py-1 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors"
          >
            Stop
          </button>
        </div>
      </div>

      <!-- Start Timer Form -->
      <div v-else class="mb-4">
        <div class="flex gap-2">
          <input
            v-model="timerDescription"
            type="text"
            placeholder="What are you working on?"
            class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
          />
          <button
            @click="startTimer"
            :disabled="!timerDescription.trim()"
            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          >
            Start
          </button>
        </div>
      </div>

      <!-- Time Entries -->
      <div class="space-y-2 max-h-48 overflow-y-auto">
        <div
          v-for="entry in timeEntries"
          :key="entry.id"
          class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-900/50 rounded-lg"
        >
          <div class="flex-1">
            <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
              {{ entry.description || 'No description' }}
            </div>
            <div class="text-xs text-gray-500 dark:text-gray-400">
              {{ formatDate(entry.start_time) }}
              <span v-if="entry.end_time"> - {{ formatDate(entry.end_time) }}</span>
            </div>
          </div>
          <div class="flex items-center gap-2">
            <span class="text-sm font-mono text-gray-700 dark:text-gray-300">
              {{ formatDuration(entry.duration_minutes) }}
            </span>
            <button
              @click="deleteEntry(entry.id)"
              class="text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300"
            >
              <Icon name="Trash2" class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>

      <!-- No entries message -->
      <div v-if="timeEntries.length === 0" class="text-center py-4 text-gray-500 dark:text-gray-400">
        <Icon name="Clock" class="w-8 h-8 mx-auto mb-2" />
        <p class="text-sm">No time entries yet</p>
      </div>
    </div>

    <!-- Time Report Modal -->
    <div v-if="showReport" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="showReport = false">
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-hidden">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
          <div class="flex justify-between items-center">
            <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">Time Report</h2>
            <button @click="showReport = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
              <Icon name="X" class="w-6 h-6" />
            </button>
          </div>
        </div>

        <div class="p-6 overflow-y-auto max-h-[60vh]">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="text-center p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
              <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ formatDuration(report.total_time) }}</div>
              <div class="text-sm text-blue-800 dark:text-blue-200">Total Time</div>
            </div>
            <div class="text-center p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
              <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ report.entries_count }}</div>
              <div class="text-sm text-green-800 dark:text-green-200">Sessions</div>
            </div>
            <div class="text-center p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
              <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ formatDuration(report.average_session) }}</div>
              <div class="text-sm text-purple-800 dark:text-purple-200">Avg Session</div>
            </div>
          </div>

          <!-- Daily breakdown -->
          <div class="space-y-2">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">Daily Breakdown</h3>
            <div
              v-for="(time, date) in report.by_date"
              :key="date"
              class="flex justify-between items-center p-2 bg-gray-50 dark:bg-gray-900/50 rounded-lg"
            >
              <span class="text-sm text-gray-700 dark:text-gray-300">{{ formatDate(date) }}</span>
              <span class="text-sm font-mono text-gray-900 dark:text-gray-100">{{ formatDuration(time) }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import Icon from '@/components/Icon.vue';
import { timeTrackingService, type TimeEntry } from '@/services/timeTrackingService';

interface Props {
  todoId: number;
  userId: number;
}

const props = defineProps<Props>();

const timerDescription = ref('');
const showReport = ref(false);
const updateInterval = ref<number | null>(null);

const timeEntries = computed(() => timeTrackingService.getTodoEntries(props.todoId));
const activeTimer = computed(() => timeTrackingService.getActiveTimer());
const totalTime = computed(() => timeTrackingService.getTodoTotalTime(props.todoId));
const runningDuration = computed(() => timeTrackingService.getRunningDuration());

const report = computed(() => {
  const endDate = new Date().toISOString().split('T')[0];
  const startDate = new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
  return timeTrackingService.getTimeReport(startDate, endDate);
});

const startTimer = async () => {
  if (!timerDescription.value.trim()) return;
  
  await timeTrackingService.startTimer(props.todoId, props.userId, timerDescription.value);
  timerDescription.value = '';
  startUpdateInterval();
};

const stopTimer = async () => {
  await timeTrackingService.stopTimer();
  stopUpdateInterval();
};

const deleteEntry = async (entryId: number) => {
  if (confirm('Are you sure you want to delete this time entry?')) {
    await timeTrackingService.deleteEntry(entryId);
  }
};

const formatDuration = (minutes: number): string => {
  return timeTrackingService.formatDuration(minutes);
};

const formatDate = (dateString: string): string => {
  const date = new Date(dateString);
  return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
};

const startUpdateInterval = () => {
  if (updateInterval.value) return;
  
  updateInterval.value = window.setInterval(() => {
    // Force reactivity update for running duration
  }, 1000);
};

const stopUpdateInterval = () => {
  if (updateInterval.value) {
    clearInterval(updateInterval.value);
    updateInterval.value = null;
  }
};

onMounted(() => {
  if (activeTimer.value) {
    startUpdateInterval();
  }
});

onUnmounted(() => {
  stopUpdateInterval();
});
</script>
