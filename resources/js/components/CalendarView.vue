<template>
  <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
    <div class="flex items-center justify-between mb-4">
      <div class="text-lg font-semibold text-gray-900 dark:text-gray-100">
        {{ viewType === 'weekly' ? weekLabel : monthYearLabel }}
      </div>
      <div class="flex gap-2">
        <!-- View Toggle (hidden on mobile) -->
        <button 
          v-if="!isMobile"
          @click="viewType = viewType === 'weekly' ? 'monthly' : 'weekly'"
          class="px-3 py-1 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-100 hover:bg-gray-50 dark:hover:bg-gray-600 text-xs sm:text-sm"
          :aria-label="`Switch to ${viewType === 'weekly' ? 'monthly' : 'weekly'} view`"
        >
          {{ viewType === 'weekly' ? 'Month' : 'Week' }}
        </button>
        <button 
          @click="viewType === 'weekly' ? prevWeek() : prevMonth()" 
          class="px-3 py-1 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-100 hover:bg-gray-50 dark:hover:bg-gray-600 min-w-[44px] min-h-[44px] flex items-center justify-center text-xs sm:text-sm"
        >
          Prev
        </button>
        <button 
          @click="today" 
          class="px-3 py-1 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-100 hover:bg-gray-50 dark:hover:bg-gray-600 min-w-[44px] min-h-[44px] flex items-center justify-center text-xs sm:text-sm"
        >
          Today
        </button>
        <button 
          @click="viewType === 'weekly' ? nextWeek() : nextMonth()" 
          class="px-3 py-1 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-100 hover:bg-gray-50 dark:hover:bg-gray-600 min-w-[44px] min-h-[44px] flex items-center justify-center text-xs sm:text-sm"
        >
          Next
        </button>
      </div>
    </div>

    <!-- Monthly View -->
    <template v-if="viewType === 'monthly'">
      <div class="grid grid-cols-7 gap-2 text-xs font-medium text-gray-500 dark:text-gray-400 mb-2">
        <div v-for="d in weekDays" :key="d" class="text-center">{{ d }}</div>
      </div>

      <div class="grid grid-cols-7 gap-2">
        <div v-for="cell in calendarCells" :key="cell.key" class="min-h-28 rounded-lg border border-gray-200 dark:border-gray-700 p-2 bg-gray-50 dark:bg-gray-900/40">
          <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-semibold" :class="{ 'text-gray-900 dark:text-gray-100': cell.inMonth, 'text-gray-400': !cell.inMonth }">{{ cell.date.getDate() }}</span>
            <div class="flex items-center gap-1">
              <!-- Add button for future days only - always positioned on the right -->
              <button
                v-if="isFutureOnly(cell.date) && !props.isReadOnly"
                @click="$emit('addTodo', cell.date)"
                class="text-[10px] px-1.5 py-0.5 rounded bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 text-blue-600 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-colors flex items-center justify-center gap-0.5"
                title="Add todo for this date"
              >
                <Icon name="Plus" class="w-2.5 h-2.5" />
                Add
              </button>
            </div>
          </div>
          
          <!-- Today badge - positioned in center below the date -->
          <div v-if="isToday(cell.date)" class="flex justify-center mb-1">
            <span class="text-[10px] px-2 py-0.5 rounded-full bg-blue-600 text-white">Today</span>
          </div>
          <div class="space-y-1 overflow-y-auto max-h-20 relative">
            <div 
              v-for="item in itemsByDate[cell.iso]" 
              :key="item.id" 
              class="truncate text-[11px] px-2 py-1 rounded bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 cursor-pointer hover:shadow-md transition-shadow relative group"
              @click="$emit('editTodo', item)"
            >
              <div class="flex items-center gap-1">
                <!-- Status indicator dot -->
                <div 
                  v-if="item.status === 'in-progress'"
                  class="w-2 h-2 rounded-full bg-green-500 flex-shrink-0"
                  title="In Progress"
                ></div>
                <span class="font-medium">{{ item.title }}</span>
              </div>
              <span v-if="item.project?.key" class="ml-1 text-gray-500">({{ item.project.key }})</span>
              
              <!-- Hover Tooltip -->
              <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-gray-900 dark:bg-gray-700 text-white text-xs rounded-lg shadow-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-[9999]">
                <div class="flex items-center gap-2">
                  <div 
                    class="w-3 h-3 rounded-full" 
                    :style="{ backgroundColor: item.project?.color || '#6b7280' }"
                  ></div>
                  <span class="font-medium">{{ item.project?.name || 'No Project' }}</span>
                </div>
                <div class="text-gray-300 mt-1">{{ item.title }}</div>
                <div v-if="item.status === 'in-progress'" class="text-green-300 text-[10px] mt-1">● In Progress</div>
                <!-- Arrow -->
                <div class="absolute top-full left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-gray-900 dark:border-t-gray-700"></div>
              </div>
            </div>
            
            <div v-if="!itemsByDate[cell.iso] || itemsByDate[cell.iso].length === 0" class="text-[11px] text-gray-400">No todos</div>
          </div>
        </div>
      </div>
    </template>

    <!-- Weekly View -->
    <template v-else>
      <!-- Desktop: 7 columns, Mobile: 1 column -->
      <div class="hidden sm:grid grid-cols-7 gap-2 text-xs font-medium text-gray-500 dark:text-gray-400 mb-2">
        <div v-for="d in weekDays" :key="d" class="text-center">{{ d }}</div>
      </div>

      <!-- Desktop: 7 column grid -->
      <div class="hidden sm:grid grid-cols-7 gap-2">
        <div v-for="cell in weeklyCells" :key="cell.key" class="min-h-32 rounded-lg border border-gray-200 dark:border-gray-700 p-2 bg-gray-50 dark:bg-gray-900/40">
          <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">
              {{ cell.date.getDate() }}
              <span v-if="isToday(cell.date)" class="ml-1 text-[10px] px-2 py-0.5 rounded-full bg-blue-600 text-white">Today</span>
            </span>
            <div class="flex items-center gap-1">
              <!-- Add button for future days only - always positioned on the right -->
              <button
                v-if="isFutureOnly(cell.date) && !props.isReadOnly"
                @click="$emit('addTodo', cell.date)"
                class="text-[10px] px-1.5 py-0.5 rounded bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 text-blue-600 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-colors flex items-center justify-center gap-0.5 min-w-[30px] min-h-[30px]"
                title="Add todo for this date"
                aria-label="Add todo for this date"
              >
                <Icon name="Plus" class="w-3 h-3" />
              </button>
            </div>
          </div>
          
          <div class="space-y-1 overflow-y-auto max-h-32 sm:max-h-40 relative">
            <div 
              v-for="item in itemsByDate[cell.iso]" 
              :key="item.id" 
              class="text-xs px-2 py-1.5 rounded bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 cursor-pointer hover:shadow-md transition-shadow relative group"
              @click="$emit('editTodo', item)"
            >
              <div class="flex items-center gap-1">
                <!-- Status indicator dot -->
                <div 
                  v-if="item.status === 'in-progress'"
                  class="w-2 h-2 rounded-full bg-green-500 flex-shrink-0"
                  title="In Progress"
                ></div>
                <span class="font-medium truncate">{{ item.title }}</span>
              </div>
              <span v-if="item.project?.key" class="ml-1 text-gray-500 text-[10px]">({{ item.project.key }})</span>
              
              <!-- Hover Tooltip -->
              <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-gray-900 dark:bg-gray-700 text-white text-xs rounded-lg shadow-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-[9999]">
                <div class="flex items-center gap-2">
                  <div 
                    class="w-3 h-3 rounded-full" 
                    :style="{ backgroundColor: item.project?.color || '#6b7280' }"
                  ></div>
                  <span class="font-medium">{{ item.project?.name || 'No Project' }}</span>
                </div>
                <div class="text-gray-300 mt-1">{{ item.title }}</div>
                <div v-if="item.status === 'in-progress'" class="text-green-300 text-[10px] mt-1">● In Progress</div>
                <!-- Arrow -->
                <div class="absolute top-full left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-gray-900 dark:border-t-gray-700"></div>
              </div>
            </div>
            
            <div v-if="!itemsByDate[cell.iso] || itemsByDate[cell.iso].length === 0" class="text-[11px] text-gray-400">No todos</div>
          </div>
        </div>
      </div>

      <!-- Mobile: Single column stacked -->
      <div class="sm:hidden space-y-3">
        <div v-for="cell in weeklyCells" :key="cell.key" class="rounded-lg border border-gray-200 dark:border-gray-700 p-3 bg-gray-50 dark:bg-gray-900/40">
          <!-- Mobile: Full width day header -->
          <div class="flex items-center justify-between mb-3 pb-2 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-2">
              <span class="text-base font-semibold text-gray-900 dark:text-gray-100">
                {{ getDayName(cell.date) }}, {{ cell.date.toLocaleDateString(undefined, { month: 'short', day: 'numeric' }) }}
              </span>
              <span v-if="isToday(cell.date)" class="text-xs px-2 py-0.5 rounded-full bg-blue-600 text-white">Today</span>
            </div>
            <div class="flex items-center gap-1">
              <!-- Add button for future days only -->
              <button
                v-if="isFutureOnly(cell.date) && !props.isReadOnly"
                @click="$emit('addTodo', cell.date)"
                class="px-3 py-1.5 rounded bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 text-blue-600 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-colors flex items-center justify-center gap-1 min-w-[30px] min-h-[30px]"
                title="Add todo for this date"
                aria-label="Add todo for this date"
              >
                <Icon name="Plus" class="w-4 h-4" />
                <span class="text-xs font-medium">Add</span>
              </button>
            </div>
          </div>
          
          <div class="space-y-2 relative">
            <!-- Mobile: Larger todo items -->
            <div 
              v-for="item in itemsByDate[cell.iso]" 
              :key="item.id" 
              class="px-3 py-2 rounded bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 cursor-pointer hover:shadow-md transition-shadow"
              @click="$emit('editTodo', item)"
            >
              <div class="flex items-center gap-2">
                <!-- Status indicator dot -->
                <div 
                  v-if="item.status === 'in-progress'"
                  class="w-2.5 h-2.5 rounded-full bg-green-500 flex-shrink-0"
                  title="In Progress"
                ></div>
                <div class="flex-1 min-w-0">
                  <div class="flex items-center gap-2">
                    <span class="font-medium text-sm text-gray-900 dark:text-gray-100 truncate">{{ item.title }}</span>
                    <span v-if="item.project?.key" class="text-xs text-gray-500 flex-shrink-0">({{ item.project.key }})</span>
                  </div>
                  <div v-if="item.project?.name" class="flex items-center gap-1.5 mt-1">
                    <div 
                      class="w-2 h-2 rounded-full flex-shrink-0" 
                      :style="{ backgroundColor: item.project?.color || '#6b7280' }"
                    ></div>
                    <span class="text-xs text-gray-500">{{ item.project.name }}</span>
                  </div>
                </div>
              </div>
            </div>
            
            <div v-if="!itemsByDate[cell.iso] || itemsByDate[cell.iso].length === 0" class="text-sm text-gray-400 text-center py-4">No todos</div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, onMounted } from 'vue';
import Icon from '@/components/Icon.vue';
// import { usePage } from '@inertiajs/vue3';
import type { Todo } from '@/services/todoApi';

interface Props {
  todos: Todo[];
  isReadOnly?: boolean;
}

const props = defineProps<Props>();

defineEmits<{
  editTodo: [todo: Todo];
  addTodo: [date: Date];
}>();

// const page = usePage();
// const currentUser = (page.props as any)?.auth?.user || null;

const current = ref(new Date());
// Detect mobile device and default to weekly view
const isMobile = ref(false);
const viewType = ref<'monthly' | 'weekly'>('monthly');

onMounted(() => {
  // Detect mobile device
  isMobile.value = window.innerWidth < 768 || 'ontouchstart' in window;
  // Default to weekly view on mobile
  if (isMobile.value) {
    viewType.value = 'weekly';
  }
});

const weekDays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

const startOfMonth = (d: Date) => new Date(d.getFullYear(), d.getMonth(), 1);
// const endOfMonth = (d: Date) => new Date(d.getFullYear(), d.getMonth() + 1, 0);
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
const toISODate = (d: Date) => {
  const year = d.getFullYear();
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
};

const monthYearLabel = computed(() =>
  current.value.toLocaleDateString(undefined, { month: 'long', year: 'numeric' }),
);

const weekLabel = computed(() => {
  const weekStart = startOfWeek(current.value);
  const weekEnd = addDays(weekStart, 6);
  const sameMonth = weekStart.getMonth() === weekEnd.getMonth();
  const sameYear = weekStart.getFullYear() === weekEnd.getFullYear();
  
  if (sameMonth && sameYear) {
    return `${weekStart.getDate()}-${weekEnd.getDate()} ${weekStart.toLocaleDateString(undefined, { month: 'long', year: 'numeric' })}`;
  } else if (sameYear) {
    return `${weekStart.toLocaleDateString(undefined, { day: 'numeric', month: 'short' })} - ${weekEnd.toLocaleDateString(undefined, { day: 'numeric', month: 'short', year: 'numeric' })}`;
  } else {
    return `${weekStart.toLocaleDateString(undefined, { day: 'numeric', month: 'short', year: 'numeric' })} - ${weekEnd.toLocaleDateString(undefined, { day: 'numeric', month: 'short', year: 'numeric' })}`;
  }
});

const itemsByDate = computed<Record<string, Todo[]>>(() => {
  const map: Record<string, Todo[]> = {};
  for (const t of props.todos || []) {
    // Only show To Do and In Progress tasks from all company users
    if (!t.due_date || (t.status !== 'todo' && t.status !== 'in-progress')) continue;
    const key = t.due_date.slice(0, 10);
    if (!map[key]) map[key] = [];
    map[key].push(t);
  }
  return map;
});

const calendarCells = computed(() => {
  const start = startOfMonth(current.value);
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

const weeklyCells = computed(() => {
  const weekStart = startOfWeek(current.value);
  const cells: Array<{ key: string; date: Date; iso: string }> = [];
  let d = new Date(weekStart);
  // 7 days for a week
  for (let i = 0; i < 7; i++) {
    const iso = toISODate(d);
    cells.push({ key: iso, date: new Date(d), iso });
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
const prevWeek = () => {
  const weekStart = startOfWeek(current.value);
  current.value = addDays(weekStart, -7);
};
const nextWeek = () => {
  const weekStart = startOfWeek(current.value);
  current.value = addDays(weekStart, 7);
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

const isFutureOnly = (date: Date) => {
  const now = new Date();
  const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
  const cellDate = new Date(date.getFullYear(), date.getMonth(), date.getDate());
  return cellDate > today;
};

const getDayName = (date: Date) => {
  return date.toLocaleDateString(undefined, { weekday: 'long' });
};
</script>

<style scoped>
.min-h-28 { min-height: 7rem; }
</style>


