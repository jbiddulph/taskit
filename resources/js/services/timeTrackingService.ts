import { ref, computed } from 'vue';

export interface TimeEntry {
  id: number;
  todo_id: number;
  user_id: number;
  description: string;
  start_time: string;
  end_time?: string;
  duration_minutes: number;
  is_running: boolean;
  created_at: string;
  updated_at: string;
}

export interface TimeReport {
  total_time: number;
  entries_count: number;
  average_session: number;
  by_user: { [userId: number]: number };
  by_todo: { [todoId: number]: number };
  by_date: { [date: string]: number };
}

class TimeTrackingService {
  private timeEntries = ref<TimeEntry[]>([]);
  private activeTimer = ref<TimeEntry | null>(null);

  // Get all time entries for a todo
  getTodoEntries(todoId: number): TimeEntry[] {
    return this.timeEntries.value.filter(entry => entry.todo_id === todoId);
  }

  // Get all time entries for a user
  getUserEntries(userId: number): TimeEntry[] {
    return this.timeEntries.value.filter(entry => entry.user_id === userId);
  }

  // Get time entries for a date range
  getEntriesInRange(startDate: string, endDate: string): TimeEntry[] {
    return this.timeEntries.value.filter(entry => {
      const entryDate = entry.start_time.split('T')[0];
      return entryDate >= startDate && entryDate <= endDate;
    });
  }

  // Start a new time entry
  async startTimer(todoId: number, userId: number, description: string = ''): Promise<TimeEntry> {
    // Stop any existing timer
    if (this.activeTimer.value) {
      await this.stopTimer();
    }

    const newEntry: TimeEntry = {
      id: Date.now(), // In real app, this would come from API
      todo_id: todoId,
      user_id: userId,
      description,
      start_time: new Date().toISOString(),
      duration_minutes: 0,
      is_running: true,
      created_at: new Date().toISOString(),
      updated_at: new Date().toISOString()
    };

    this.timeEntries.value.push(newEntry);
    this.activeTimer.value = newEntry;
    return newEntry;
  }

  // Stop the current timer
  async stopTimer(): Promise<TimeEntry | null> {
    if (!this.activeTimer.value) return null;

    const endTime = new Date();
    const startTime = new Date(this.activeTimer.value.start_time);
    const durationMinutes = Math.floor((endTime.getTime() - startTime.getTime()) / (1000 * 60));

    this.activeTimer.value.end_time = endTime.toISOString();
    this.activeTimer.value.duration_minutes = durationMinutes;
    this.activeTimer.value.is_running = false;
    this.activeTimer.value.updated_at = new Date().toISOString();

    const stoppedEntry = { ...this.activeTimer.value };
    this.activeTimer.value = null;

    return stoppedEntry;
  }

  // Get current active timer
  getActiveTimer(): TimeEntry | null {
    return this.activeTimer.value;
  }

  // Check if user has an active timer
  hasActiveTimer(userId: number): boolean {
    return this.activeTimer.value?.user_id === userId;
  }

  // Get total time for a todo
  getTodoTotalTime(todoId: number): number {
    return this.timeEntries.value
      .filter(entry => entry.todo_id === todoId)
      .reduce((total, entry) => total + entry.duration_minutes, 0);
  }

  // Get total time for a user
  getUserTotalTime(userId: number): number {
    return this.timeEntries.value
      .filter(entry => entry.user_id === userId)
      .reduce((total, entry) => total + entry.duration_minutes, 0);
  }

  // Format duration in minutes to readable string
  formatDuration(minutes: number): string {
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    
    if (hours > 0) {
      return `${hours}h ${mins}m`;
    }
    return `${mins}m`;
  }

  // Get time report for a date range
  getTimeReport(startDate: string, endDate: string): TimeReport {
    const entries = this.getEntriesInRange(startDate, endDate);
    
    const totalTime = entries.reduce((total, entry) => total + entry.duration_minutes, 0);
    const entriesCount = entries.length;
    const averageSession = entriesCount > 0 ? totalTime / entriesCount : 0;

    const byUser: { [userId: number]: number } = {};
    const byTodo: { [todoId: number]: number } = {};
    const byDate: { [date: string]: number } = {};

    entries.forEach(entry => {
      // By user
      byUser[entry.user_id] = (byUser[entry.user_id] || 0) + entry.duration_minutes;
      
      // By todo
      byTodo[entry.todo_id] = (byTodo[entry.todo_id] || 0) + entry.duration_minutes;
      
      // By date
      const date = entry.start_time.split('T')[0];
      byDate[date] = (byDate[date] || 0) + entry.duration_minutes;
    });

    return {
      total_time: totalTime,
      entries_count: entriesCount,
      average_session: averageSession,
      by_user: byUser,
      by_todo: byTodo,
      by_date: byDate
    };
  }

  // Update an existing time entry
  async updateEntry(entryId: number, updates: Partial<TimeEntry>): Promise<TimeEntry | null> {
    const index = this.timeEntries.value.findIndex(entry => entry.id === entryId);
    if (index === -1) return null;

    this.timeEntries.value[index] = {
      ...this.timeEntries.value[index],
      ...updates,
      updated_at: new Date().toISOString()
    };

    return this.timeEntries.value[index];
  }

  // Delete a time entry
  async deleteEntry(entryId: number): Promise<boolean> {
    const index = this.timeEntries.value.findIndex(entry => entry.id === entryId);
    if (index === -1) return false;

    this.timeEntries.value.splice(index, 1);
    return true;
  }

  // Set time entries (for loading from API)
  setTimeEntries(entries: TimeEntry[]) {
    this.timeEntries.value = entries;
  }

  // Get running timer duration
  getRunningDuration(): number {
    if (!this.activeTimer.value) return 0;
    
    const now = new Date();
    const startTime = new Date(this.activeTimer.value.start_time);
    return Math.floor((now.getTime() - startTime.getTime()) / (1000 * 60));
  }
}

export const timeTrackingService = new TimeTrackingService();
