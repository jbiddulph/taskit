import { supabase } from '@/services/supabaseClient';
import type { RealtimeChannel } from '@supabase/supabase-js';

// Get Supabase configuration for debugging
const supabaseUrl = (globalThis as any)?.VITE_SUPABASE_URL || (import.meta as any)?.env?.VITE_SUPABASE_URL;
const supabaseAnonKey = (globalThis as any)?.VITE_SUPABASE_ANON_KEY || (import.meta as any)?.env?.VITE_SUPABASE_ANON_KEY;

interface Message {
  id: number;
  sender_id: number;
  recipient_id: number;
  company_id: number;
  message: string;
  is_read: boolean;
  created_at: string;
}

class RealtimeService {
  private channels: Map<string, RealtimeChannel> = new Map();
  private messageCallbacks: Set<(message: any) => void> = new Set();
  private unreadCountCallbacks: Set<(count: number) => void> = new Set();
  private notificationCallbacks: Set<(notification: any) => void> = new Set();
  private projectCallbacks: Set<(project: any) => void> = new Set();
  private todoCallbacks: Set<(todo: any) => void> = new Set();
  private activityCallbacks: Set<(activity: any) => void> = new Set();
  private currentUserId: number | null = null;
  private currentCompanyId: number | null = null;

  /**
   * Initialize real-time service with user context
   */
  init(userId: number, companyId: number) {
    this.currentUserId = userId;
    this.currentCompanyId = companyId;
    
    // Test Supabase connection
    this.testSupabaseConnection();
    
    this.subscribeToCompanyMessages();
    this.subscribeToNotifications();
    this.subscribeToProjects();
    this.subscribeToTodos();
    this.subscribeToActivities();
  }
  
  /**
   * Test Supabase real-time connection
   */
  private testSupabaseConnection() {
    const testChannel = supabase
      .channel('connection-test')
      .subscribe((status) => {
        if (status === 'SUBSCRIBED') {
          testChannel.unsubscribe();
        }
      });
  }

  /**
   * Subscribe to company messages for real-time updates
   */
  private subscribeToCompanyMessages() {
    if (!this.currentCompanyId) return;

    const channelName = `company_messages_${this.currentCompanyId}`;
    
    // Remove existing channel if it exists
    if (this.channels.has(channelName)) {
      this.channels.get(channelName)?.unsubscribe();
      this.channels.delete(channelName);
    }

    const channel = supabase
      .channel(channelName)
      .on(
        'postgres_changes',
        {
          event: 'INSERT',
          schema: 'public',
          table: 'company_messages',
          filter: `company_id=eq.${this.currentCompanyId}`
        },
        (payload) => {
          this.handleNewMessage(payload.new as Message);
        }
      )
      .on(
        'postgres_changes',
        {
          event: 'UPDATE',
          schema: 'public',
          table: 'company_messages',
          filter: `company_id=eq.${this.currentCompanyId}`
        },
        (payload) => {
          this.handleMessageUpdate(payload.new as Message);
        }
      )
      .subscribe((status) => {
        console.log('Real-time subscription status:', status);
      });

    this.channels.set(channelName, channel);
  }

  /**
   * Subscribe to notifications for real-time updates
   */
  private subscribeToNotifications() {
    if (!this.currentUserId) return;

    const channelName = `user_notifications_${this.currentUserId}`;
    
    // Remove existing channel if it exists
    if (this.channels.has(channelName)) {
      this.channels.get(channelName)?.unsubscribe();
      this.channels.delete(channelName);
    }

    const channel = supabase
      .channel(channelName)
      .on(
        'postgres_changes',
        {
          event: 'INSERT',
          schema: 'public',
          table: 'taskit_notifications',
          filter: `user_id=eq.${this.currentUserId}`
        },
        (payload) => {
          this.handleNewNotification(payload.new as any);
        }
      )
      .subscribe((status) => {
        console.log('Notification real-time subscription status:', status);
      });

    this.channels.set(channelName, channel);
  }

  /**
   * Subscribe to projects for real-time updates
   */
  private subscribeToProjects() {
    if (!this.currentCompanyId) {
      console.log('🚨 Cannot subscribe to projects - no company ID');
      return;
    }

    const channelName = `company_projects_${this.currentCompanyId}`;
    console.log('🔥 Subscribing to projects channel:', channelName);
    
    // Remove existing channel if it exists
    if (this.channels.has(channelName)) {
      console.log('🔄 Removing existing projects channel');
      this.channels.get(channelName)?.unsubscribe();
      this.channels.delete(channelName);
    }

    const channel = supabase
      .channel(channelName)
      .on(
        'postgres_changes',
        {
          event: 'INSERT',
          schema: 'public',
          table: 'taskit_projects',
          filter: `company_id=eq.${this.currentCompanyId}`
        },
        (payload) => {
          console.log('🆕 Project INSERT event received:', payload);
          this.handleNewProject(payload.new as any);
        }
      )
      .on(
        'postgres_changes',
        {
          event: 'UPDATE',
          schema: 'public',
          table: 'taskit_projects',
          filter: `company_id=eq.${this.currentCompanyId}`
        },
        (payload) => {
          console.log('📝 Project UPDATE event received:', payload);
          this.handleProjectUpdate(payload.new as any);
        }
      )
      .on(
        'postgres_changes',
        {
          event: 'DELETE',
          schema: 'public',
          table: 'taskit_projects',
          filter: `company_id=eq.${this.currentCompanyId}`
        },
        (payload) => {
          console.log('🗑️ Project DELETE event received:', payload);
          console.log('🗑️ Project DELETE payload.old:', payload.old);
          this.handleProjectDelete(payload.old as any);
        }
      )
      .subscribe((status) => {
        console.log('🔥 Projects real-time subscription status:', status);
        if (status === 'SUBSCRIBED') {
          console.log('✅ Projects real-time subscription successful');
        } else if (status === 'CHANNEL_ERROR') {
          console.error('❌ Projects real-time subscription error');
        } else if (status === 'TIMED_OUT') {
          console.error('⏰ Projects real-time subscription timed out');
        } else if (status === 'CLOSED') {
          console.warn('🔒 Projects real-time subscription closed');
        }
      });

    this.channels.set(channelName, channel);
  }

  /**
   * Subscribe to todos for real-time updates
   */
  private subscribeToTodos() {
    if (!this.currentCompanyId) {
      return;
    }

    const channelName = `company_todos_${this.currentCompanyId}`;
    
    // Remove existing channel if it exists
    if (this.channels.has(channelName)) {
      this.channels.get(channelName)?.unsubscribe();
      this.channels.delete(channelName);
    }

    const channel = supabase
      .channel(channelName)
      .on(
        'postgres_changes',
        {
          event: 'INSERT',
          schema: 'public',
          table: 'taskit_todos',
          filter: `company_id=eq.${this.currentCompanyId}`
        },
        (payload) => {
          this.handleNewTodo(payload.new as any);
        }
      )
      .on(
        'postgres_changes',
        {
          event: 'UPDATE',
          schema: 'public',
          table: 'taskit_todos',
          filter: `company_id=eq.${this.currentCompanyId}`
        },
        (payload) => {
          this.handleTodoUpdate(payload.new as any);
        }
      )
      .on(
        'postgres_changes',
        {
          event: 'DELETE',
          schema: 'public',
          table: 'taskit_todos',
          filter: `company_id=eq.${this.currentCompanyId}`
        },
        (payload) => {
          this.handleTodoDelete(payload.old as any);
        }
      )
      .subscribe((status) => {
        // Subscription status handled silently
      });

    this.channels.set(channelName, channel);
  }

  /**
   * Subscribe to activities for real-time updates
   */
  private subscribeToActivities() {
    if (!this.currentCompanyId) {
      return;
    }

    const channelName = `company_activities_${this.currentCompanyId}`;
    
    // Remove existing channel if it exists
    if (this.channels.has(channelName)) {
      this.channels.get(channelName)?.unsubscribe();
      this.channels.delete(channelName);
    }

    const channel = supabase
      .channel(channelName)
      .on(
        'postgres_changes',
        {
          event: 'INSERT',
          schema: 'public',
          table: 'taskit_activities',
          filter: `company_id=eq.${this.currentCompanyId}`
        },
        (payload) => {
          this.handleNewActivity(payload.new as any);
        }
      )
      .on(
        'postgres_changes',
        {
          event: 'UPDATE',
          schema: 'public',
          table: 'taskit_activities',
          filter: `company_id=eq.${this.currentCompanyId}`
        },
        (payload) => {
          this.handleActivityUpdate(payload.new as any);
        }
      )
      .on(
        'postgres_changes',
        {
          event: 'DELETE',
          schema: 'public',
          table: 'taskit_activities',
          filter: `company_id=eq.${this.currentCompanyId}`
        },
        (payload) => {
          this.handleActivityDelete(payload.old as any);
        }
      )
      .subscribe((status) => {
        // Subscription status handled silently
      });

    this.channels.set(channelName, channel);
  }

  /**
   * Handle new notification received
   */
  private handleNewNotification(notification: any) {
    console.log('New notification received:', notification);
    
    // Check if this is a chat message notification and if chat is currently open with sender
    const isMessageNotification = notification.data?.message_id && notification.data?.sender_id;
    const isChatOpen = isMessageNotification && this.isChatOpenWithUser(notification.data.sender_id);
    
    // Only show notification if chat is not open with the sender
    if (!isChatOpen) {
      // Notify all notification callbacks
      this.notificationCallbacks.forEach(callback => {
        callback({
          type: 'new_notification',
          data: notification
        });
      });

      // Show browser notification if chat message and chat is closed
      if (isMessageNotification) {
        // Show a toast notification
        if ((window as any).$notify) {
          (window as any).$notify({
            type: 'info',
            title: notification.title,
            message: notification.message,
            duration: 5000
          });
        }
      }
    } else {
      console.log('Chat is open with sender, skipping notification');
    }
  }

  /**
   * Check if chat is currently open with a specific user
   */
  private isChatOpenWithUser(userId: number): boolean {
    // Check if there's an active chat state event
    const chatState = (window as any).currentChatState;
    return chatState?.isOpen && chatState?.otherUserId === userId;
  }

  /**
   * Handle new project created
   */
  private handleNewProject(project: any) {
    console.log('🚀 New project created:', project);
    console.log('🚀 Project callbacks count:', this.projectCallbacks.size);
    
    // Notify all project callbacks
    this.projectCallbacks.forEach((callback, index) => {
      console.log('🚀 Calling project callback', index + 1);
      callback({
        type: 'project_created',
        data: project
      });
    });
  }

  /**
   * Handle project update
   */
  private handleProjectUpdate(project: any) {
    console.log('Project updated:', project);
    
    // Notify all project callbacks
    this.projectCallbacks.forEach(callback => {
      callback({
        type: 'project_updated',
        data: project
      });
    });
  }

  /**
   * Handle project delete
   */
  private handleProjectDelete(project: any) {
    console.log('🗑️ Project deleted:', project);
    console.log('🗑️ Project callbacks count:', this.projectCallbacks.size);
    
    // Notify all project callbacks
    this.projectCallbacks.forEach(callback => {
      console.log('🗑️ Notifying project callback of deletion');
      callback({
        type: 'project_deleted',
        data: project
      });
    });
  }

  /**
   * Handle new todo created
   */
  private handleNewTodo(todo: any) {
    // Notify all todo callbacks
    this.todoCallbacks.forEach((callback) => {
      callback({
        type: 'todo_created',
        data: todo
      });
    });
  }

  /**
   * Handle todo update
   */
  private handleTodoUpdate(todo: any) {
    // Notify all todo callbacks
    this.todoCallbacks.forEach(callback => {
      callback({
        type: 'todo_updated',
        data: todo
      });
    });
  }

  /**
   * Handle todo delete
   */
  private handleTodoDelete(todo: any) {
    console.log('🔥 RealtimeService: Todo deletion received:', todo);
    // Notify all todo callbacks
    this.todoCallbacks.forEach(callback => {
      callback({
        type: 'todo_deleted',
        data: todo
      });
    });
  }

  /**
   * Handle new activity created
   */
  private handleNewActivity(activity: any) {
    console.log('RealtimeService: New activity received:', activity);
    console.log('RealtimeService: Activity callbacks count:', this.activityCallbacks.size);
    // Notify all activity callbacks
    this.activityCallbacks.forEach(callback => {
      callback({
        type: 'activity_created',
        data: activity
      });
    });
  }

  /**
   * Handle activity update
   */
  private handleActivityUpdate(activity: any) {
    // Notify all activity callbacks
    this.activityCallbacks.forEach(callback => {
      callback({
        type: 'activity_updated',
        data: activity
      });
    });
  }

  /**
   * Handle activity delete
   */
  private handleActivityDelete(activity: any) {
    // Notify all activity callbacks
    this.activityCallbacks.forEach(callback => {
      callback({
        type: 'activity_deleted',
        data: activity
      });
    });
  }

  /**
   * Handle new message received
   */
  private handleNewMessage(message: Message) {
    // Only process messages not sent by current user
    if (message.sender_id !== this.currentUserId) {
      // Notify all message callbacks
      this.messageCallbacks.forEach(callback => {
        callback({
          type: 'new_message',
          data: message
        });
      });

      // Update unread count if message is for current user and chat is not open with sender
      if (message.recipient_id === this.currentUserId) {
        this.updateUnreadCountConditional(message.sender_id);
      }
    }
  }

  /**
   * Handle message update (e.g., read status)
   */
  private handleMessageUpdate(message: Message) {
    this.messageCallbacks.forEach(callback => {
      callback({
        type: 'message_updated',
        data: message
      });
    });

    // Update unread count if relevant and chat is not open
    if (message.recipient_id === this.currentUserId) {
      this.updateUnreadCountConditional(message.sender_id);
    } else if (message.sender_id === this.currentUserId) {
      // Always update if we sent the message (for read status changes)
      this.updateUnreadCount();
    }
  }

  /**
   * Update unread message count
   */
  private async updateUnreadCount() {
    try {
      const response = await fetch('/api/company-messages/unread-count', {
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }
      });

      if (response.ok) {
        const data = await response.json();
        const count = data.data.unread_count;
        
        this.unreadCountCallbacks.forEach(callback => {
          callback(count);
        });
      }
    } catch (error) {
      console.error('Failed to fetch unread count:', error);
    }
  }

  /**
   * Update unread count only if chat is not open with the message sender
   */
  private updateUnreadCountConditional(senderId?: number) {
    if (senderId) {
      const isChatOpen = this.isChatOpenWithUser(senderId);
      if (isChatOpen) {
        console.log('Chat is open with sender, skipping unread count update');
        return;
      }
    }
    
    this.updateUnreadCount();
  }

  /**
   * Subscribe to message events
   */
  onMessage(callback: (message: any) => void) {
    this.messageCallbacks.add(callback);
    
    // Return unsubscribe function
    return () => {
      this.messageCallbacks.delete(callback);
    };
  }

  /**
   * Subscribe to unread count changes
   */
  onUnreadCountChange(callback: (count: number) => void) {
    this.unreadCountCallbacks.add(callback);
    
    // Return unsubscribe function
    return () => {
      this.unreadCountCallbacks.delete(callback);
    };
  }

  /**
   * Subscribe to notification events
   */
  onNotification(callback: (notification: any) => void) {
    this.notificationCallbacks.add(callback);
    
    // Return unsubscribe function
    return () => {
      this.notificationCallbacks.delete(callback);
    };
  }

  /**
   * Subscribe to project events
   */
  onProject(callback: (project: any) => void) {
    console.log('🔥 Component subscribing to project events. Total callbacks:', this.projectCallbacks.size + 1);
    this.projectCallbacks.add(callback);
    
    // Return unsubscribe function
    return () => {
      console.log('🔥 Component unsubscribing from project events');
      this.projectCallbacks.delete(callback);
    };
  }

  /**
   * Subscribe to todo events
   */
  onTodo(callback: (todo: any) => void) {
    console.log('🔥 Component subscribing to todo events. Total callbacks:', this.todoCallbacks.size + 1);
    this.todoCallbacks.add(callback);
    
    // Return unsubscribe function
    return () => {
      console.log('🔥 Component unsubscribing from todo events');
      this.todoCallbacks.delete(callback);
    };
  }

  /**
   * Subscribe to activity events
   */
  onActivity(callback: (activity: any) => void) {
    console.log('RealtimeService: Adding activity callback. Total callbacks:', this.activityCallbacks.size + 1);
    this.activityCallbacks.add(callback);
    
    // Return unsubscribe function
    return () => {
      console.log('RealtimeService: Removing activity callback');
      this.activityCallbacks.delete(callback);
    };
  }

  /**
   * Disconnect all subscriptions
   */
  disconnect() {
    this.channels.forEach(channel => {
      channel.unsubscribe();
    });
    this.channels.clear();
    this.messageCallbacks.clear();
    this.unreadCountCallbacks.clear();
    this.notificationCallbacks.clear();
    this.projectCallbacks.clear();
    this.todoCallbacks.clear();
    this.activityCallbacks.clear();
    this.currentUserId = null;
    this.currentCompanyId = null;
  }

  /**
   * Reconnect subscriptions (useful for page navigation)
   */
  reconnect() {
    if (this.currentUserId && this.currentCompanyId) {
      this.disconnect();
      this.init(this.currentUserId, this.currentCompanyId);
    }
  }

  /**
   * Test realtime functionality manually
   */
  testRealtime() {
    console.log('🧪 Testing realtime functionality...');
    console.log('🧪 Current user ID:', this.currentUserId);
    console.log('🧪 Current company ID:', this.currentCompanyId);
    console.log('🧪 Active channels:', Array.from(this.channels.keys()));
    console.log('🧪 Todo callbacks registered:', this.todoCallbacks.size);
    console.log('🧪 Activity callbacks registered:', this.activityCallbacks.size);
    
    // Test if we can create a test channel
    const testChannel = supabase
      .channel('manual-test')
      .subscribe((status) => {
        console.log('🧪 Manual test channel status:', status);
        if (status === 'SUBSCRIBED') {
          console.log('✅ Manual test channel connected successfully!');
          testChannel.unsubscribe();
        }
      });
  }

  /**
   * Test database realtime subscription
   */
  testDatabaseRealtime() {
    console.log('🧪 Testing database realtime subscription...');
    
    if (!this.currentCompanyId) {
      console.log('🚨 No company ID available for testing');
      return;
    }

    const testChannel = supabase
      .channel('database-test')
      .on(
        'postgres_changes',
        {
          event: 'INSERT',
          schema: 'public',
          table: 'taskit_todos',
          filter: `company_id=eq.${this.currentCompanyId}`
        },
        (payload) => {
          console.log('🧪 Database realtime test - INSERT event:', payload);
        }
      )
      .subscribe((status) => {
        console.log('🧪 Database realtime test status:', status);
        if (status === 'SUBSCRIBED') {
          console.log('✅ Database realtime subscription successful!');
          // Keep the subscription for testing
        } else if (status === 'CHANNEL_ERROR') {
          console.error('❌ Database realtime subscription failed!');
        }
      });
  }
}

// Export singleton instance
export const realtimeService = new RealtimeService();
