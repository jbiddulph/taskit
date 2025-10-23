import { ref, computed } from 'vue';

export interface ActivityItem {
  id: number;
  type: 'todo_created' | 'todo_updated' | 'todo_deleted' | 'todo_commented' | 'todo_assigned' | 'todo_status_changed' | 'project_created' | 'project_updated' | 'user_joined' | 'mention';
  actor_id: number;
  actor_name: string;
  actor_avatar?: string;
  target_id?: number;
  target_name?: string;
  description: string;
  metadata?: any;
  created_at: string;
  project_id?: number;
  company_id: number;
}

export interface ActivityFilter {
  type?: string;
  actor_id?: number;
  project_id?: number;
  date_from?: string;
  date_to?: string;
}

class ActivityFeedService {
  private activities = ref<ActivityItem[]>([]);

  // Get all activities for a company
  getCompanyActivities(companyId: number, limit: number = 50): ActivityItem[] {
    return this.activities.value
      .filter(activity => activity.company_id === companyId)
      .sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime())
      .slice(0, limit);
  }

  // Get activities for a specific project
  getProjectActivities(projectId: number, limit: number = 50): ActivityItem[] {
    return this.activities.value
      .filter(activity => activity.project_id === projectId)
      .sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime())
      .slice(0, limit);
  }

  // Get activities for a specific user
  getUserActivities(userId: number, limit: number = 50): ActivityItem[] {
    return this.activities.value
      .filter(activity => activity.actor_id === userId)
      .sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime())
      .slice(0, limit);
  }

  // Filter activities
  filterActivities(filters: ActivityFilter, limit: number = 50): ActivityItem[] {
    let filtered = this.activities.value;

    if (filters.type) {
      filtered = filtered.filter(activity => activity.type === filters.type);
    }

    if (filters.actor_id) {
      filtered = filtered.filter(activity => activity.actor_id === filters.actor_id);
    }

    if (filters.project_id) {
      filtered = filtered.filter(activity => activity.project_id === filters.project_id);
    }

    if (filters.date_from) {
      filtered = filtered.filter(activity => activity.created_at >= filters.date_from!);
    }

    if (filters.date_to) {
      filtered = filtered.filter(activity => activity.created_at <= filters.date_to!);
    }

    return filtered
      .sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime())
      .slice(0, limit);
  }

  // Add a new activity
  addActivity(activity: Omit<ActivityItem, 'id' | 'created_at'>): ActivityItem {
    const newActivity: ActivityItem = {
      ...activity,
      id: Date.now(), // In real app, this would come from API
      created_at: new Date().toISOString()
    };

    this.activities.value.unshift(newActivity);
    return newActivity;
  }

  // Create activity for todo creation
  createTodoActivity(todo: any, actor: any): ActivityItem {
    return this.addActivity({
      type: 'todo_created',
      actor_id: actor.id,
      actor_name: actor.name,
      actor_avatar: actor.avatar,
      target_id: todo.id,
      target_name: todo.title,
      description: `${actor.name} created todo "${todo.title}"`,
      metadata: { todo },
      project_id: todo.project_id,
      company_id: actor.company_id
    });
  }

  // Create activity for todo update
  createTodoUpdateActivity(todo: any, actor: any, changes: any): ActivityItem {
    return this.addActivity({
      type: 'todo_updated',
      actor_id: actor.id,
      actor_name: actor.name,
      actor_avatar: actor.avatar,
      target_id: todo.id,
      target_name: todo.title,
      description: `${actor.name} updated todo "${todo.title}"`,
      metadata: { todo, changes },
      project_id: todo.project_id,
      company_id: actor.company_id
    });
  }

  // Create activity for todo status change
  createStatusChangeActivity(todo: any, actor: any, oldStatus: string, newStatus: string): ActivityItem {
    return this.addActivity({
      type: 'todo_status_changed',
      actor_id: actor.id,
      actor_name: actor.name,
      actor_avatar: actor.avatar,
      target_id: todo.id,
      target_name: todo.title,
      description: `${actor.name} moved "${todo.title}" from ${oldStatus} to ${newStatus}`,
      metadata: { todo, oldStatus, newStatus },
      project_id: todo.project_id,
      company_id: actor.company_id
    });
  }

  // Create activity for todo assignment
  createAssignmentActivity(todo: any, actor: any, assignee: string): ActivityItem {
    return this.addActivity({
      type: 'todo_assigned',
      actor_id: actor.id,
      actor_name: actor.name,
      actor_avatar: actor.avatar,
      target_id: todo.id,
      target_name: todo.title,
      description: `${actor.name} assigned "${todo.title}" to ${assignee}`,
      metadata: { todo, assignee },
      project_id: todo.project_id,
      company_id: actor.company_id
    });
  }

  // Create activity for comment
  createCommentActivity(todo: any, actor: any, comment: string): ActivityItem {
    return this.addActivity({
      type: 'todo_commented',
      actor_id: actor.id,
      actor_name: actor.name,
      actor_avatar: actor.avatar,
      target_id: todo.id,
      target_name: todo.title,
      description: `${actor.name} commented on "${todo.title}"`,
      metadata: { todo, comment },
      project_id: todo.project_id,
      company_id: actor.company_id
    });
  }

  // Create activity for mention
  createMentionActivity(todo: any, actor: any, mentionedUser: any): ActivityItem {
    return this.addActivity({
      type: 'mention',
      actor_id: actor.id,
      actor_name: actor.name,
      actor_avatar: actor.avatar,
      target_id: todo.id,
      target_name: todo.title,
      description: `${actor.name} mentioned ${mentionedUser.name} in "${todo.title}"`,
      metadata: { todo, mentionedUser },
      project_id: todo.project_id,
      company_id: actor.company_id
    });
  }

  // Get activity types
  getActivityTypes(): string[] {
    return [
      'todo_created',
      'todo_updated',
      'todo_deleted',
      'todo_commented',
      'todo_assigned',
      'todo_status_changed',
      'project_created',
      'project_updated',
      'user_joined',
      'mention'
    ];
  }

  // Get recent activities (last 24 hours)
  getRecentActivities(companyId: number): ActivityItem[] {
    const yesterday = new Date(Date.now() - 24 * 60 * 60 * 1000).toISOString();
    return this.activities.value
      .filter(activity => 
        activity.company_id === companyId && 
        activity.created_at >= yesterday
      )
      .sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime());
  }

  // Set activities (for loading from API)
  setActivities(activities: ActivityItem[]) {
    this.activities.value = activities;
  }

  // Get activity icon
  getActivityIcon(type: string): string {
    const icons: { [key: string]: string } = {
      'todo_created': 'Plus',
      'todo_updated': 'Edit',
      'todo_deleted': 'Trash2',
      'todo_commented': 'MessageCircle',
      'todo_assigned': 'UserCheck',
      'todo_status_changed': 'ArrowRight',
      'project_created': 'FolderPlus',
      'project_updated': 'FolderEdit',
      'user_joined': 'UserPlus',
      'mention': 'AtSign'
    };
    return icons[type] || 'Activity';
  }

  // Get activity color
  getActivityColor(type: string): string {
    const colors: { [key: string]: string } = {
      'todo_created': 'text-green-600',
      'todo_updated': 'text-blue-600',
      'todo_deleted': 'text-red-600',
      'todo_commented': 'text-purple-600',
      'todo_assigned': 'text-orange-600',
      'todo_status_changed': 'text-indigo-600',
      'project_created': 'text-green-600',
      'project_updated': 'text-blue-600',
      'user_joined': 'text-green-600',
      'mention': 'text-pink-600'
    };
    return colors[type] || 'text-gray-600';
  }
}

export const activityFeedService = new ActivityFeedService();
