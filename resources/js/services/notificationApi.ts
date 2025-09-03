import axios from 'axios';

export interface Notification {
  id: number;
  user_id: number;
  type: 'info' | 'warning' | 'error' | 'success';
  title: string;
  message: string;
  data?: any;
  is_read: boolean;
  read_at?: string;
  created_at: string;
  updated_at: string;
}

export interface NotificationResponse {
  data: Notification[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

export interface UnreadCountResponse {
  count: number;
}

class NotificationApiService {
  private baseUrl = '/notifications';

  private async request<T>(method: string, url: string, data?: any): Promise<T> {
    try {
      const response = await axios({
        method,
        url: `${this.baseUrl}${url}`,
        data,
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
      });
      return response.data;
    } catch (error: any) {
      console.error(`Notification API ${method} error:`, error);
      throw new Error(error.response?.data?.message || `Failed to ${method.toLowerCase()} notification`);
    }
  }

  /**
   * Get all notifications for the current user
   */
  async getNotifications(page: number = 1): Promise<NotificationResponse> {
    return this.request<NotificationResponse>('GET', `?page=${page}`);
  }

  /**
   * Get unread notifications count
   */
  async getUnreadCount(): Promise<UnreadCountResponse> {
    return this.request<UnreadCountResponse>('GET', '/unread-count');
  }

  /**
   * Mark a notification as read
   */
  async markAsRead(notificationId: number): Promise<{ message: string }> {
    return this.request<{ message: string }>('PATCH', `/${notificationId}/read`);
  }

  /**
   * Mark all notifications as read
   */
  async markAllAsRead(): Promise<{ message: string }> {
    return this.request<{ message: string }>('PATCH', '/mark-all-read');
  }

  /**
   * Delete a notification
   */
  async deleteNotification(notificationId: number): Promise<{ message: string }> {
    return this.request<{ message: string }>('DELETE', `/${notificationId}`);
  }

  /**
   * Create a new notification (for testing)
   */
  async createNotification(data: {
    user_id: number;
    type: 'info' | 'warning' | 'error' | 'success';
    title: string;
    message: string;
    data?: any;
  }): Promise<Notification> {
    return this.request<Notification>('POST', '', data);
  }
}

export const notificationApi = new NotificationApiService();
