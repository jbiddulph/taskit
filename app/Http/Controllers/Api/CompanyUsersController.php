<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\CompanyMessage;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CompanyUsersController extends Controller
{
    /**
     * Get all users in the same company as the authenticated user
     */
    public function index(): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user->company_id) {
            return response()->json([
                'success' => true,
                'data' => []
            ]);
        }

        $users = User::where('company_id', $user->company_id)
            ->where('id', '!=', $user->id) // Exclude current user
            ->select('id', 'name', 'email', 'created_at')
            ->orderBy('name')
            ->get()
            ->map(function ($companyUser) use ($user) {
                // Get unread message count from this user
                $unreadCount = CompanyMessage::where('sender_id', $companyUser->id)
                    ->where('recipient_id', $user->id)
                    ->where('is_read', false)
                    ->count();

                return [
                    'id' => $companyUser->id,
                    'name' => $companyUser->name,
                    'email' => $companyUser->email,
                    'joined_at' => $companyUser->created_at->format('M j, Y'),
                    'unread_messages' => $unreadCount,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    /**
     * Get messages between current user and another user
     */
    public function getMessages(Request $request, int $userId): JsonResponse
    {
        $currentUser = Auth::user();
        
        if (!$currentUser->company_id) {
            return response()->json(['success' => false, 'message' => 'No company access'], 403);
        }

        // Verify the other user is in the same company
        $otherUser = User::where('id', $userId)
            ->where('company_id', $currentUser->company_id)
            ->first();

        if (!$otherUser) {
            return response()->json(['success' => false, 'message' => 'User not found or not in same company'], 404);
        }

        $messages = CompanyMessage::betweenUsers($currentUser->id, $userId)
            ->where('company_id', $currentUser->company_id)
            ->with(['sender:id,name', 'recipient:id,name'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($message) {
                return [
                    'id' => $message->id,
                    'message' => $message->message,
                    'sender_id' => $message->sender_id,
                    'sender_name' => $message->sender->name,
                    'recipient_id' => $message->recipient_id,
                    'recipient_name' => $message->recipient->name,
                    'is_read' => $message->is_read,
                    'created_at' => $message->created_at->format('M j, Y g:i A'),
                    'created_at_human' => $message->created_at->diffForHumans(),
                ];
            });

        // Mark messages from the other user as read
        CompanyMessage::where('sender_id', $userId)
            ->where('recipient_id', $currentUser->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'messages' => $messages,
                'other_user' => [
                    'id' => $otherUser->id,
                    'name' => $otherUser->name,
                    'email' => $otherUser->email,
                ],
            ]
        ]);
    }

    /**
     * Send a message to another user
     */
    public function sendMessage(Request $request): JsonResponse
    {
        $currentUser = Auth::user();
        
        if (!$currentUser->company_id) {
            return response()->json(['success' => false, 'message' => 'No company access'], 403);
        }

        $validator = Validator::make($request->all(), [
            'recipient_id' => 'required|integer|exists:taskit_users,id',
            'message' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Verify the recipient is in the same company
        $recipient = User::where('id', $request->recipient_id)
            ->where('company_id', $currentUser->company_id)
            ->first();

        if (!$recipient) {
            return response()->json(['success' => false, 'message' => 'Recipient not found or not in same company'], 404);
        }

        // Prevent sending message to self
        if ($currentUser->id === $recipient->id) {
            return response()->json(['success' => false, 'message' => 'Cannot send message to yourself'], 400);
        }

        $message = CompanyMessage::create([
            'sender_id' => $currentUser->id,
            'recipient_id' => $recipient->id,
            'company_id' => $currentUser->company_id,
            'message' => $request->message,
        ]);

        // Create notification for recipient about new message
        // The frontend will decide whether to show notifications based on chat state
        Notification::create([
            'user_id' => $recipient->id,
            'type' => 'info',
            'title' => 'New Message',
            'message' => "You have a new message from {$currentUser->name}",
            'data' => [
                'message_id' => $message->id,
                'sender_id' => $currentUser->id,
                'sender_name' => $currentUser->name,
                'chat_preview' => substr($request->message, 0, 50) . (strlen($request->message) > 50 ? '...' : ''),
            ],
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $message->id,
                'message' => $message->message,
                'sender_id' => $message->sender_id,
                'sender_name' => $currentUser->name,
                'recipient_id' => $message->recipient_id,
                'recipient_name' => $recipient->name,
                'is_read' => $message->is_read,
                'created_at' => $message->created_at->format('M j, Y g:i A'),
                'created_at_human' => $message->created_at->diffForHumans(),
            ]
        ]);
    }

    /**
     * Get unread message count for current user
     */
    public function getUnreadCount(): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user->company_id) {
            return response()->json([
                'success' => true,
                'data' => ['unread_count' => 0]
            ]);
        }

        $unreadCount = CompanyMessage::where('recipient_id', $user->id)
            ->where('company_id', $user->company_id)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'success' => true,
            'data' => ['unread_count' => $unreadCount]
        ]);
    }
}