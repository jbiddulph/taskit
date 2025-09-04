<template>
  <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
    <div class="flex items-center justify-between mb-4">
      <div class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ monthYearLabel }}</div>
      <div class="flex gap-2">
        <button @click="prevMonth" class="px-3 py-1 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-100 hover:bg-gray-50 dark:hover:bg-gray-600">Prev</button>
        <button @click="today" class="px-3 py-1 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-100 hover:bg-gray-50 dark:hover:bg-gray-600">Today</button>
        <button @click="nextMonth" class="px-3 py-1 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-100 hover:bg-gray-50 dark:hover:bg-gray-600">Next</button>
      </div>
    </div>

    <div class="grid grid-cols-7 gap-2 text-xs font-medium text-gray-500 dark:text-gray-400 mb-2">
      <div v-for="d in weekDays" :key="d" class="text-center">{{ d }}</div>
    </div>

    <div class="grid grid-cols-7 gap-2">
      <div v-for="cell in calendarCells" :key="cell.key" class="min-h-28 rounded-lg border border-gray-200 dark:border-gray-700 p-2 bg-gray-50 dark:bg-gray-900/40">
        <div class="flex items-center justify-between mb-2">
          <span class="text-xs font-semibold" :class="{ 'text-gray-900 dark:text-gray-100': cell.inMonth, 'text-gray-400': !cell.inMonth }">{{ cell.date.getDate() }}</span>
          <span v-if="isToday(cell.date)" class="text-[10px] px-2 py-0.5 rounded-full bg-blue-600 text-white">Today</span>
        </div>
        <div class="space-y-1 overflow-y-auto max-h-20 relative">
          <div 
            v-for="item in itemsByDate[cell.iso]" 
            :key="item.id" 
            class="truncate text-[11px] px-2 py-1 rounded bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 cursor-pointer hover:shadow-md transition-shadow relative group"
            @click="$emit('editTodo', item)"
          >
            <span class="font-medium">{{ item.title }}</span>
            <span v-if="item.project?.key" class="ml-1 text-gray-500">({{ item.project.key }})</span>
            
            <!-- Hover Tooltip -->
            <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-gray-900 dark:bg-gray-700 text-white text-xs rounded-lg shadow-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-50">
              <div class="flex items-center gap-2">
                <div 
                  class="w-3 h-3 rounded-full" 
                  :style="{ backgroundColor: item.project?.color || '#6b7280' }"
                ></div>
                <span class="font-medium">{{ item.project?.name || 'No Project' }}</span>
              </div>
              <div class="text-gray-300 mt-1">{{ item.title }}</div>
              <!-- Arrow -->
              <div class="absolute top-full left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-gray-900 dark:border-t-gray-700"></div>
            </div>
          </div>
          <div v-if="!itemsByDate[cell.iso] || itemsByDate[cell.iso].length === 0" class="text-[11px] text-gray-400">No todos</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import type { Todo } from '@/services/todoApi';

interface Props {
  todos: Todo[];
}

const props = defineProps<Props>();

const emit = defineEmits<{
  editTodo: [todo: Todo];
}>();

const current = ref(new Date());

const weekDays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

const startOfMonth = (d: Date) => new Date(d.getFullYear(), d.getMonth(), 1);
const endOfMonth = (d: Date) => new Date(d.getFullYear(), d.getMonth() + 1, 0);
const startOfWeek = (d: Date) => {
  const dd = new Date(d);
  const day = dd.getDay(); // 0=Sun,1=Mon,...
  const diff = (day + 6) % 7; // 0 for Mon, 6 for Sun
  dd.setDate(dd.getDate() - diff);
  dd.setHours(0, 0, 0, 0);
  return dd;
};
const addDays = (d: Date, days: number) => {
  const dd = new Date(d);
  dd.setDate(dd.getDate() + days);
  return dd;
};
const toISODate = (d: Date) => d.toISOString().slice(0, 10);

const monthYearLabel = computed(() =>
  current.value.toLocaleDateString(undefined, { month: 'long', year: 'numeric' }),
);

const itemsByDate = computed<Record<string, Todo[]>>(() => {
  const map: Record<string, Todo[]> = {};
  for (const t of props.todos || []) {
    if (!t.due_date) continue;
    const key = t.due_date.slice(0, 10);
    if (!map[key]) map[key] = [];
    map[key].push(t);
  }
  return map;
});

const calendarCells = computed(() => {
  const start = startOfMonth(current.value);
  const end = endOfMonth(current.value);
  const gridStart = startOfWeek(start);
  const cells: Array<{ key: string; date: Date; iso: string; inMonth: boolean }> = [];
  let d = new Date(gridStart);
  // 6 weeks grid to cover all months
  for (let i = 0; i < 42; i++) {
    const iso = toISODate(d);
    cells.push({ key: iso, date: new Date(d), iso, inMonth: d.getMonth() === current.value.getMonth() });
    d = addDays(d, 1);
  }
  return cells;
});

const prevMonth = () => {
  current.value = new Date(current.value.getFullYear(), current.value.getMonth() - 1, 1);
};
const nextMonth = () => {
  current.value = new Date(current.value.getFullYear(), current.value.getMonth() + 1, 1);
};
const today = () => {
  current.value = new Date();
};

const isToday = (date: Date) => {
  const now = new Date();
  return (
    date.getFullYear() === now.getFullYear() &&
    date.getMonth() === now.getMonth() &&
    date.getDate() === now.getDate()
  );
};
</script>

<style scoped>
.min-h-28 { min-height: 7rem; }
</style>


