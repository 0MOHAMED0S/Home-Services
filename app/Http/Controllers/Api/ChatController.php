<?php

namespace App\Http\Controllers\Api;

use App\Events\FreelancerNotificationEvent;
use App\Events\MessageSent;
use App\Events\UserNotificationEvent;
use App\Http\Controllers\Controller;
use App\Jobs\SendEmailJob;
use App\Models\Conversation;
use App\Models\Freelancer;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    // For USER sending message to FREELANCER
    public function sendMessageAsUser(Request $request): JsonResponse
    {
        $request->validate([
            'freelancer_id' => 'required|exists:freelancers,id',
            'message'       => 'required|string|max:255',
        ]);

        try {
            $user = Auth::guard('user')->user();
            $freelancer = Freelancer::findOrFail($request->freelancer_id);

            $conversation = Conversation::firstOrCreate([
                'user_id'       => $user->id,
                'freelancer_id' => $freelancer->id,
            ]);

            $message = $conversation->messages()->create([
                'message'     => $request->message,
                'sender_type' => User::class,
                'sender_id'   => $user->id,
            ]);

            event(new MessageSent($message));

            event(new UserNotificationEvent(
                $freelancer,
                '📨 New Message',
                "{$user->name} sent you a message"
            ));
            SendEmailJob::dispatch(
                $recipientEmail = $freelancer->email,
                $title = '📩 New Message!',
                $message = "{$user->name} sent you a message"
            );
            return response()->json([
                'status'  => 201,
                'message' => 'Message sent successfully.',
            ], 201);
        } catch (\Throwable $e) {
            Log::error('Send message failed', [
                'user_id' => optional(Auth::guard('user')->user())->id,
                'error'   => $e->getMessage(),
            ]);

            return response()->json([
                'status'  => 500,
                'message' => 'Failed to send message.',
                'error'   => config('app.debug') ? $e->getMessage() : null, // hide in production
            ], 500);
        }
    }

    public function getUserConversations(Request $request): JsonResponse
    {
        try {
            $user = Auth::guard('user')->user();

            $conversations = Conversation::with(['freelancerProfile', 'latestMessage'])
                ->where('user_id', $user->id)
                ->latest()
                ->paginate(10);

            return response()->json([
                'status'  => 200,
                'message' => 'Conversations retrieved successfully.',
                'data'    => $conversations,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Failed to fetch user conversations', [
                'user_id' => optional(Auth::guard('user')->user())->id,
                'error'   => $e->getMessage(),
            ]);

            return response()->json([
                'status'  => 500,
                'message' => 'Failed to retrieve conversations.',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function getUserConversationMessages($id, Request $request): JsonResponse
    {
        try {
            $user = Auth::guard('user')->user();

            $conversation = Conversation::where('id', $id)
                ->where('user_id', $user->id)
                ->firstOrFail();

            // Mark messages from freelancer as read
            $conversation->messages()
                ->where('sender_type', 'App\\Models\\Freelancer')
                ->where('is_read', false)
                ->update(['is_read' => true]);

            $messages = $conversation->messages()
                ->orderBy('created_at')
                ->paginate(10);

            return response()->json([
                'status'  => 200,
                'message' => 'Messages retrieved successfully.',
                'data'    => $messages,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('User failed to fetch conversation messages', [
                'user_id'        => optional(Auth::guard('user')->user())->id,
                'conversation_id' => $id,
                'error'          => $e->getMessage(),
            ]);

            return response()->json([
                'status'  => 500,
                'message' => 'Failed to retrieve messages.',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    // For FREELANCER sending message to USER
    public function sendMessageAsFreelancer(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'message' => 'required|string|max:255',
        ]);

        try {
            $freelancer = Auth::guard('freelancer')->user();
            $user = User::findOrFail($request->user_id);

            $conversation = Conversation::firstOrCreate([
                'user_id'       => $user->id,
                'freelancer_id' => $freelancer->id,
            ]);

            $message = $conversation->messages()->create([
                'message'     => $request->message,
                'sender_type' => Freelancer::class,
                'sender_id'   => $freelancer->id,
            ]);

            event(new MessageSent($message));

            event(new FreelancerNotificationEvent(
                $user,
                '📨 New Message',
                "{$freelancer->name} sent you a message"
            ));
            SendEmailJob::dispatch(
                $recipientEmail = $user->email,
                $title = '📩 New Message!',
                $message = "{$freelancer->name} sent you a message"
            );
            return response()->json([
                'status'  => 201,
                'message' => 'Message sent successfully.',
            ], 201);
        } catch (\Throwable $e) {
            Log::error('Send message failed (freelancer)', [
                'freelancer_id' => optional(Auth::guard('freelancer')->user())->id,
                'error'         => $e->getMessage(),
            ]);

            return response()->json([
                'status'  => 500,
                'message' => 'Failed to send message.',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function getFreelancerConversations(Request $request): JsonResponse
    {
        try {
            $freelancer = Auth::guard('freelancer')->user();

            $conversations = Conversation::with(['userProfile', 'latestMessage'])
                ->where('freelancer_id', $freelancer->id)
                ->orderByDesc(
                    Message::select('created_at')
                        ->whereColumn('conversation_id', 'conversations.id')
                        ->latest()
                        ->take(1)
                )
                ->paginate(10); // Change per page if needed

            return response()->json([
                'status' => 200,
                'message' => 'Conversations retrieved successfully.',
                'data' => $conversations,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Failed to fetch freelancer conversations', [
                'freelancer_id' => optional(Auth::guard('freelancer')->user())->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 500,
                'message' => 'Failed to retrieve conversations.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }


    public function getFreelancerConversationMessages($id, Request $request): JsonResponse
    {
        try {
            $freelancer = Auth::guard('freelancer')->user();

            $conversation = Conversation::where('id', $id)
                ->where('freelancer_id', $freelancer->id)
                ->firstOrFail();

            // Mark messages from user as read
            $conversation->messages()
                ->where('sender_type', User::class)
                ->where('is_read', false)
                ->update(['is_read' => true]);

            $messages = $conversation->messages()
                ->orderBy('created_at', 'asc')
                ->paginate(20); // Pagination

            return response()->json([
                'status' => 200,
                'message' => 'Messages retrieved successfully.',
                'data' => $messages,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Freelancer failed to fetch conversation messages', [
                'freelancer_id' => optional(Auth::guard('freelancer')->user())->id,
                'conversation_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 500,
                'message' => 'Failed to retrieve messages.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
