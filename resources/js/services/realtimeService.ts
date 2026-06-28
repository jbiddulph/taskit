import { isRealtimeAvailable, supabase } from '@/services/supabaseClient';
import type { RealtimeChannel } from '@supabase/supabase-js';

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

    if (!isRealtimeAvailable()) {
      return;
    }

    this.subscribeToCompanyMessages();
    this.subscribeToNotifications();
    this.subscribeToProjects();
    this.subscribeToTodos();
    this.subscribeToActivities();
  }
  
  /**
   * Test Supabase real-time connection (private)
   */
  private testSupabaseConnectionPrivate() {
    const testChannel = supabase
      .channel('connection-test')
      .subscribe((status) => {
        if (status === 'SUBSCRIBED') {
          testChannel.unsubscribe();
        }
      });
  }

  /**
   * Public method to test Supabase connection
   */
  public testSupabaseConnection() {
    const testChannel = supabase
      .channel('connection-test-public')
      .subscribe((status) => {
        if (status === 'SUBSCRIBED') {
          testChannel.unsubscribe();
        } else if (status === 'CHANNEL_ERROR') {
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
      });

    this.channels.set(channelName, channel);
  }

  /**
   * Subscribe to notifications for real-time updates
   */
  private subscribeToNotifications() {
    if (!this.currentUserId) {
      return;
    }

    const channelName = `user_notifications_${this.currentUserId}`;
    
    // Remove existing channel if it exists
    if (this.channels.has(channelName)) {
      this.channels.get(channelName)?.unsubscribe();
      this.channels.delete(channelName);
    }

    // Ensure user_id is an integer for proper filtering
    const userId = parseInt(String(this.currentUserId), 10);
    
    // Primary subscription with filtered channel
    const channel = supabase
      .channel(channelName)
      .on(
        'postgres_changes',
        {
          event: 'INSERT',
          schema: 'public',
          table: 'taskit_notifications',
          filter: `user_id=eq.${userId}`
        },
        (payload) => {
          this.handleNewNotification(payload.new as any);
        }
      )
      .subscribe((status) => {
        if (status === 'SUBSCRIBED') {
        } else if (status === 'CHANNEL_ERROR') {
          // Fallback: try unfiltered subscription with client-side filtering
          this.setupFallbackNotificationSubscription(userId);
        } else if (status === 'TIMED_OUT') {
          this.setupFallbackNotificationSubscription(userId);
        } else if (status === 'CLOSED') {
        }
      });

    this.channels.set(channelName, channel);
    
    // Also set up a fallback subscription that listens to all notifications
    // and filters on the client side. This helps debug and provides redundancy.
    // Note: This should be removed in production if the filtered subscription works reliably.
    this.setupFallbackNotificationSubscription(userId);
  }

  /**
   * Fallback notification subscription that listens to all notifications
   * and filters on the client side. Useful when the filtered subscription fails.
   */
  private setupFallbackNotificationSubscription(userId: number) {
    const fallbackChannelName = `user_notifications_fallback_${userId}`;
    
    // Don't create duplicate fallback channels
    if (this.channels.has(fallbackChannelName)) {
      return;
    }

    
    const fallbackChannel = supabase
      .channel(fallbackChannelName)
      .on(
        'postgres_changes',
        {
          event: 'INSERT',
          schema: 'public',
          table: 'taskit_notifications'
          // No filter - we'll filter on client side
        },
        (payload) => {
          const notification = payload.new as any;
          const notificationUserId = parseInt(String(notification.user_id || notification.userId || 0), 10);
          
          
          // Filter on client side - only process if it matches current user
          if (notificationUserId === userId) {
            this.handleNewNotification(notification);
          } else {
          }
        }
      )
      .subscribe((status) => {
        if (status === 'SUBSCRIBED') {
        } else if (status === 'CHANNEL_ERROR') {
        }
      });

    this.channels.set(fallbackChannelName, fallbackChannel);
  }

  /**
   * Subscribe to projects for real-time updates
   */
  private subscribeToProjects() {
    if (!this.currentCompanyId) {
      return;
    }

    const channelName = `company_projects_${this.currentCompanyId}`;
    
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
          table: 'taskit_projects',
          filter: `company_id=eq.${this.currentCompanyId}`
        },
        (payload) => {
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
          this.handleProjectDelete(payload.old as any);
        }
      )
      .subscribe((status) => {
        if (status === 'SUBSCRIBED') {
        } else if (status === 'CHANNEL_ERROR') {
        } else if (status === 'TIMED_OUT') {
        } else if (status === 'CLOSED') {
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
        if (status === 'SUBSCRIBED') {
        } else if (status === 'CHANNEL_ERROR') {
        }
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
      .subscribe(() => {
        // Subscription status handled silently
      });

    this.channels.set(channelName, channel);
  }

  /**
   * Handle new notification received
   */
  private handleNewNotification(notification: any) {
    
    // Check if this is a chat message notification and if chat is currently open with sender
    const isMessageNotification = notification.data?.message_id && notification.data?.sender_id;
    const isChatOpen = isMessageNotification && this.isChatOpenWithUser(notification.data.sender_id);
    
    
    // Only show notification if chat is not open with the sender
    if (!isChatOpen) {
      // Notify all notification callbacks
      this.notificationCallbacks.forEach((callback, index) => {
        callback({
          type: 'new_notification',
          data: notification
        });
      });

      // Show toast for chat messages and meeting notes
      if (isMessageNotification) {
        if ((window as any).$notify) {
          (window as any).$notify({
            type: 'info',
            title: notification.title,
            message: notification.message,
            duration: 5000
          });
        }
      }

      if (notification.type === 'meeting_notes' && notification.data?.proposal_id) {
        if ((window as any).$notify) {
          (window as any).$notify({
            type: 'info',
            title: notification.title,
            message: notification.message,
            duration: 8000
          });
        }

        window.dispatchEvent(new CustomEvent('openMeetingNoteApproval', {
          detail: { proposalId: notification.data.proposal_id }
        }));
      }
    } else {
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
    
    // Notify all project callbacks
    this.projectCallbacks.forEach((callback, index) => {
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
    
    // Notify all project callbacks
    this.projectCallbacks.forEach(callback => {
      callback({
        type: 'project_deleted',
        data: project
      });
    });
  }

  /**
   * Emit todo_created locally (e.g. after meeting notes approval) without waiting for Supabase.
   */
  emitTodosCreated(todos: any[]) {
    todos.forEach((todo) => this.handleNewTodo(todo));
  }

  /**
   * Handle new todo created
   */
  private handleNewTodo(todo: any) {
    
    // Notify all todo callbacks
    this.todoCallbacks.forEach((callback, index) => {
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
    this.todoCallbacks.forEach((callback, index) => {
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
    
    // Notify all todo callbacks
    this.todoCallbacks.forEach((callback, index) => {
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
    }
  }

  /**
   * Update unread count only if chat is not open with the message sender
   */
  private updateUnreadCountConditional(senderId?: number) {
    if (senderId) {
      const isChatOpen = this.isChatOpenWithUser(senderId);
      if (isChatOpen) {
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
    this.projectCallbacks.add(callback);
    
    // Return unsubscribe function
    return () => {
      this.projectCallbacks.delete(callback);
    };
  }

  /**
   * Subscribe to todo events
   */
  onTodo(callback: (todo: any) => void) {
    this.todoCallbacks.add(callback);
    
    // Return unsubscribe function
    return () => {
      this.todoCallbacks.delete(callback);
    };
  }

  /**
   * Subscribe to activity events
   */
  onActivity(callback: (activity: any) => void) {
    this.activityCallbacks.add(callback);
    
    // Return unsubscribe function
    return () => {
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
    
    // Test if we can create a test channel
    const testChannel = supabase
      .channel('manual-test')
      .subscribe((status) => {
        if (status === 'SUBSCRIBED') {
          testChannel.unsubscribe();
        }
      });
  }

  /**
   * Test real-time functionality by manually triggering events
   */
  testRealtimeEvents() {
    
    // Test todo creation
    const testTodo = {
      id: 999999,
      title: 'Test Todo',
      project_id: 1,
      company_id: this.currentCompanyId
    };
    
    this.handleNewTodo(testTodo);
    
    // Test todo update
    setTimeout(() => {
      this.handleTodoUpdate({...testTodo, title: 'Updated Test Todo'});
    }, 1000);
    
    // Test todo deletion
    setTimeout(() => {
      this.handleTodoDelete(testTodo);
    }, 2000);
  }

  /**
   * Test database realtime subscription
   */
  testDatabaseRealtime() {
    
    if (!this.currentCompanyId) {
      return;
    }

    supabase
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
        }
      )
      .subscribe((status) => {
        if (status === 'SUBSCRIBED') {
          // Keep the subscription for testing
        } else if (status === 'CHANNEL_ERROR') {
        }
      });
  }

  /**
   * Test DELETE events specifically
   */
  testDeleteEvents() {
    
    if (!this.currentCompanyId) {
      return;
    }

    supabase
      .channel('delete-test')
      .on(
        'postgres_changes',
        {
          event: 'DELETE',
          schema: 'public',
          table: 'taskit_todos',
          filter: `company_id=eq.${this.currentCompanyId}`
        },
        (payload) => {
        }
      )
      .subscribe((status) => {
        if (status === 'SUBSCRIBED') {
        } else if (status === 'CHANNEL_ERROR') {
        }
      });
  }
}

// Export singleton instance
export const realtimeService = new RealtimeService();
