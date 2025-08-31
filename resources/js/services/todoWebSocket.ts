import Pusher from 'pusher-js';

export interface WebSocketEvent {
    type: string;
    data: any;
    message: string;
}

export class TodoWebSocketService {
    private pusher: Pusher;
    private channel: Pusher.Channel | null = null;
    private userId: number;

    constructor(userId: number) {
        this.userId = userId;
        
        // Initialize Pusher
        this.pusher = new Pusher(import.meta.env.VITE_PUSHER_APP_KEY || 'your-pusher-key', {
            cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER || 'mt1',
            encrypted: true,
        });

        this.initializeChannel();
    }

    private initializeChannel(): void {
        // Subscribe to user-specific channel
        this.channel = this.pusher.subscribe(`user.${this.userId}`);

        // Handle connection events
        this.pusher.connection.bind('connected', () => {
            console.log('Connected to WebSocket');
        });

        this.pusher.connection.bind('disconnected', () => {
            console.log('Disconnected from WebSocket');
        });

        this.pusher.connection.bind('error', (error: any) => {
            console.error('WebSocket connection error:', error);
        });
    }

    // Subscribe to todo events
    onTodoCreated(callback: (event: WebSocketEvent) => void): void {
        this.channel?.bind('todo.created', (data: any) => {
            callback({
                type: 'todo.created',
                data: data.todo,
                message: data.message
            });
        });
    }

    onTodoUpdated(callback: (event: WebSocketEvent) => void): void {
        this.channel?.bind('todo.updated', (data: any) => {
            callback({
                type: 'todo.updated',
                data: data.todo,
                message: data.message
            });
        });
    }

    onTodoDeleted(callback: (event: WebSocketEvent) => void): void {
        this.channel?.bind('todo.deleted', (data: any) => {
            callback({
                type: 'todo.deleted',
                data: data,
                message: data.message
            });
        });
    }

    onTodoStatusChanged(callback: (event: WebSocketEvent) => void): void {
        this.channel?.bind('todo.status-changed', (data: any) => {
            callback({
                type: 'todo.status-changed',
                data: data.todo,
                message: data.message
            });
        });
    }

    // Subscribe to comment events
    onCommentAdded(callback: (event: WebSocketEvent) => void): void {
        this.channel?.bind('comment.added', (data: any) => {
            callback({
                type: 'comment.added',
                data: data.comment,
                message: data.message
            });
        });
    }

    onCommentUpdated(callback: (event: WebSocketEvent) => void): void {
        this.channel?.bind('comment.updated', (data: any) => {
            callback({
                type: 'comment.updated',
                data: data.comment,
                message: data.message
            });
        });
    }

    onCommentDeleted(callback: (event: WebSocketEvent) => void): void {
        this.channel?.bind('comment.deleted', (data: any) => {
            callback({
                type: 'comment.deleted',
                data: data,
                message: data.message
            });
        });
    }

    // Subscribe to attachment events
    onAttachmentAdded(callback: (event: WebSocketEvent) => void): void {
        this.channel?.bind('attachment.added', (data: any) => {
            callback({
                type: 'attachment.added',
                data: data.attachment,
                message: data.message
            });
        });
    }

    onAttachmentDeleted(callback: (event: WebSocketEvent) => void): void {
        this.channel?.bind('attachment.deleted', (data: any) => {
            callback({
                type: 'attachment.deleted',
                data: data,
                message: data.message
            });
        });
    }

    // Unsubscribe from all events
    unsubscribe(): void {
        if (this.channel) {
            this.pusher.unsubscribe(`user.${this.userId}`);
        }
        this.pusher.disconnect();
    }

    // Get connection status
    isConnected(): boolean {
        return this.pusher.connection.state === 'connected';
    }
}

// Create a singleton instance
let instance: TodoWebSocketService | null = null;

export const createTodoWebSocket = (userId: number): TodoWebSocketService => {
    if (!instance) {
        instance = new TodoWebSocketService(userId);
    }
    return instance;
};

export const getTodoWebSocket = (): TodoWebSocketService | null => {
    return instance;
};

export const destroyTodoWebSocket = (): void => {
    if (instance) {
        instance.unsubscribe();
        instance = null;
    }
};
