<?php

namespace App\Services;

use App\Models\Todo;
use App\Models\TodoComment;
use App\Models\TodoAttachment;
use Illuminate\Support\Facades\Log;
use Pusher\Pusher;

class TodoWebSocketService
{
    protected ?Pusher $pusher = null;

    public function __construct()
    {
        // Only initialize Pusher if broadcasting is configured
        if (config('broadcasting.default') === 'pusher' && 
            config('broadcasting.connections.pusher.key') &&
            config('broadcasting.connections.pusher.secret') &&
            config('broadcasting.connections.pusher.app_id')) {
            
            try {
                $this->pusher = new Pusher(
                    config('broadcasting.connections.pusher.key'),
                    config('broadcasting.connections.pusher.secret'),
                    config('broadcasting.connections.pusher.app_id'),
                    config('broadcasting.connections.pusher.options', [])
                );
            } catch (\Exception $e) {
                Log::warning('Failed to initialize Pusher: ' . $e->getMessage());
                $this->pusher = null;
            }
        }
    }

    /**
     * Notify when a todo is created
     */
    public function todoCreated(Todo $todo): void
    {
        if ($this->pusher) {
            $this->pusher->trigger(
                "user.{$todo->user_id}",
                'todo.created',
                [
                    'todo' => $todo->load(['comments', 'attachments']),
                    'message' => 'New todo created: ' . $todo->title
                ]
            );
        }
    }

    /**
     * Notify when a todo is updated
     */
    public function todoUpdated(Todo $todo): void
    {
        if ($this->pusher) {
            $this->pusher->trigger(
                "user.{$todo->user_id}",
                'todo.updated',
                [
                    'todo' => $todo->load(['comments', 'attachments']),
                    'message' => 'Todo updated: ' . $todo->title
                ]
            );
        }
    }

    /**
     * Notify when a todo is deleted
     */
    public function todoDeleted(int $userId, string $todoTitle): void
    {
        if ($this->pusher) {
            $this->pusher->trigger(
                "user.{$userId}",
                'todo.deleted',
                [
                    'message' => 'Todo deleted: ' . $todoTitle
                ]
            );
        }
    }

    /**
     * Notify when a comment is added
     */
    public function commentAdded(TodoComment $comment): void
    {
        if ($this->pusher) {
            $this->pusher->trigger(
                "user.{$comment->todo->user_id}",
                'comment.added',
                [
                    'comment' => $comment->load('user'),
                    'todo_id' => $comment->todo_id,
                    'message' => 'New comment added to: ' . $comment->todo->title
                ]
            );
        }
    }

    /**
     * Notify when a user is mentioned in a comment
     */
    public function mentionAdded($notification, int $userId): void
    {
        if ($this->pusher) {
            $this->pusher->trigger(
                "user.{$userId}",
                'mention.added',
                [
                    'notification' => $notification,
                    'message' => $notification->message ?? 'You were mentioned in a comment'
                ]
            );
        }
    }

    /**
     * Notify when a comment is updated
     */
    public function commentUpdated(TodoComment $comment): void
    {
        if ($this->pusher) {
            $this->pusher->trigger(
                "user.{$comment->todo->user_id}",
                'comment.updated',
                [
                    'comment' => $comment->load('user'),
                    'todo_id' => $comment->todo_id,
                    'message' => 'Comment updated on: ' . $comment->todo->title
                ]
            );
        }
    }

    /**
     * Notify when a comment is deleted
     */
    public function commentDeleted(int $userId, int $todoId, string $todoTitle): void
    {
        if ($this->pusher) {
            $this->pusher->trigger(
                "user.{$userId}",
                'comment.deleted',
                [
                    'todo_id' => $todoId,
                    'message' => 'Comment deleted from: ' . $todoTitle
                ]
            );
        }
    }

    /**
     * Notify when an attachment is added
     */
    public function attachmentAdded(TodoAttachment $attachment): void
    {
        if ($this->pusher) {
            $this->pusher->trigger(
                "user.{$attachment->todo->user_id}",
                'attachment.added',
                [
                    'attachment' => $attachment->load('user'),
                    'todo_id' => $attachment->todo_id,
                    'message' => 'New attachment added to: ' . $attachment->todo->title
                ]
            );
        }
    }

    /**
     * Notify when an attachment is deleted
     */
    public function attachmentDeleted(int $userId, int $todoId, string $todoTitle, string $filename): void
    {
        if ($this->pusher) {
            $this->pusher->trigger(
                "user.{$userId}",
                'attachment.deleted',
                [
                    'todo_id' => $todoId,
                    'message' => "Attachment '{$filename}' deleted from: {$todoTitle}"
                ]
            );
        }
    }

    /**
     * Notify when todo status changes (drag & drop)
     */
    public function todoStatusChanged(Todo $todo, string $oldStatus): void
    {
        if ($this->pusher) {
            $this->pusher->trigger(
                "user.{$todo->user_id}",
                'todo.status-changed',
                [
                    'todo' => $todo->load(['comments', 'attachments']),
                    'old_status' => $oldStatus,
                    'new_status' => $todo->status,
                    'message' => "Todo '{$todo->title}' moved from {$oldStatus} to {$todo->status}"
                ]
            );
        }
    }
}
