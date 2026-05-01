<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MessageController extends Controller
{
    private function getAuthenticatedUser(Request $request): ?User
    {
        $token = $request->bearerToken();

        if (!$token) {
            return null;
        }

        $hashedToken = hash('sha256', $token);

        return User::where('api_token', $hashedToken)->first();
    }

    private function unauthorizedResponse()
    {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized'
        ], 401);
    }

    public function bootstrap(Request $request)
    {
        $user = $this->getAuthenticatedUser($request);
        if (!$user) {
            return $this->unauthorizedResponse();
        }

        $conversationUserIds = DB::table('chat_messages')
            ->where('is_global', false)
            ->where(function ($q) use ($user) {
                $q->where('sender_id', $user->id)
                    ->orWhere('receiver_id', $user->id);
            })
            ->orderByDesc('updated_at')
            ->get(['sender_id', 'receiver_id'])
            ->flatMap(function ($row) use ($user) {
                return [
                    (int) $row->sender_id,
                    (int) $row->receiver_id,
                ];
            })
            ->filter(fn($id) => $id !== (int) $user->id)
            ->unique()
            ->values()
            ->all();

        $users = collect();
        if (!empty($conversationUserIds)) {
            $users = User::query()
                ->whereIn('id', $conversationUserIds)
                ->orderBy('name')
                ->get(['id', 'name', 'user_code', 'user_type', 'email_verified_at', 'profile_photo_url']);
        }

        return response()->json([
            'success' => true,
            'currentUser' => [
                'id' => $user->id,
                'name' => $user->name,
                'user_type' => $user->user_type,
                'user_code' => $user->user_code,
                'email_verified_at' => $user->email_verified_at,
                'profile_photo_url' => $user->profile_photo_url,
            ],
            'users' => $users,
        ]);
    }

    public function searchUsers(Request $request)
    {
        $user = $this->getAuthenticatedUser($request);
        if (!$user) {
            return $this->unauthorizedResponse();
        }

        $validated = $request->validate([
            'query' => 'required|string|min:1|max:100',
        ]);

        $query = trim($validated['query']);

        $users = User::query()
            ->where('id', '!=', $user->id)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', '%' . $query . '%')
                    ->orWhere('user_code', 'like', '%' . $query . '%');
            })
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'user_code', 'user_type', 'email_verified_at', 'profile_photo_url']);

        return response()->json([
            'success' => true,
            'users' => $users,
        ]);
    }

    public function getGlobalMessages(Request $request)
    {
        $user = $this->getAuthenticatedUser($request);
        if (!$user) {
            return $this->unauthorizedResponse();
        }

        $messages = DB::table('chat_messages as m')
            ->join('users as u', 'u.id', '=', 'm.sender_id')
            ->where('m.is_global', true)
            ->whereNull('m.receiver_id')
            ->orderBy('m.created_at', 'asc')
            ->select(
                'm.id',
                'm.body',
                'm.sender_id',
                'm.created_at',
                'u.name as sender_name',
                'u.user_code as sender_code',
                'u.email_verified_at as sender_email_verified_at',
                'u.profile_photo_url as sender_profile_photo_url'
            )
            ->limit(250)
            ->get();

        $messageIds = $messages->pluck('id')->all();
        $reactionRows = [];

        if (!empty($messageIds)) {
            $reactionRows = DB::table('message_reactions as r')
                ->join('users as u', 'u.id', '=', 'r.user_id')
                ->whereIn('r.message_id', $messageIds)
                ->select('r.message_id', 'r.emoji', 'r.user_id', 'u.name as user_name')
                ->get();
        }

        $reactionMap = [];
        foreach ($reactionRows as $reaction) {
            if (!isset($reactionMap[$reaction->message_id])) {
                $reactionMap[$reaction->message_id] = [];
            }
            $reactionMap[$reaction->message_id][] = [
                'emoji' => $reaction->emoji,
                'user_id' => $reaction->user_id,
                'user_name' => $reaction->user_name,
            ];
        }

        $result = [];
        foreach ($messages as $message) {
            $result[] = [
                'id' => $message->id,
                'body' => $message->body,
                'sender_id' => $message->sender_id,
                'sender_name' => $message->sender_name,
                'sender_code' => $message->sender_code,
                'sender_email_verified_at' => $message->sender_email_verified_at,
                'sender_profile_photo_url' => $message->sender_profile_photo_url,
                'created_at' => $message->created_at,
                'reactions' => $reactionMap[$message->id] ?? [],
            ];
        }

        return response()->json([
            'success' => true,
            'messages' => $result,
        ]);
    }

    public function sendGlobalMessage(Request $request)
    {
        $user = $this->getAuthenticatedUser($request);
        if (!$user) {
            return $this->unauthorizedResponse();
        }

        $validated = $request->validate([
            'body' => 'required|string|max:2000',
        ]);

        $id = DB::table('chat_messages')->insertGetId([
            'sender_id' => $user->id,
            'receiver_id' => null,
            'is_global' => true,
            'body' => trim($validated['body']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Message sent',
            'id' => $id,
        ]);
    }

    public function getPrivateMessages(Request $request, int $userId)
    {
        $currentUser = $this->getAuthenticatedUser($request);
        if (!$currentUser) {
            return $this->unauthorizedResponse();
        }

        $otherUser = User::find($userId);
        if (!$otherUser) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        $messages = DB::table('chat_messages as m')
            ->join('users as u', 'u.id', '=', 'm.sender_id')
            ->where('m.is_global', false)
            ->where(function ($q) use ($currentUser, $userId) {
                $q->where(function ($sub) use ($currentUser, $userId) {
                    $sub->where('m.sender_id', $currentUser->id)
                        ->where('m.receiver_id', $userId);
                })->orWhere(function ($sub) use ($currentUser, $userId) {
                    $sub->where('m.sender_id', $userId)
                        ->where('m.receiver_id', $currentUser->id);
                });
            })
            ->orderBy('m.created_at', 'asc')
            ->select(
                'm.id',
                'm.body',
                'm.sender_id',
                'm.receiver_id',
                'm.created_at',
                'u.name as sender_name',
                'u.user_code as sender_code',
                'u.email_verified_at as sender_email_verified_at',
                'u.profile_photo_url as sender_profile_photo_url'
            )
            ->limit(250)
            ->get();

        return response()->json([
            'success' => true,
            'messages' => $messages,
            'targetUser' => [
                'id' => $otherUser->id,
                'name' => $otherUser->name,
                'user_code' => $otherUser->user_code,
                'user_type' => $otherUser->user_type,
                'email_verified_at' => $otherUser->email_verified_at,
                'profile_photo_url' => $otherUser->profile_photo_url,
            ]
        ]);
    }

    public function sendPrivateMessage(Request $request, int $userId)
    {
        $currentUser = $this->getAuthenticatedUser($request);
        if (!$currentUser) {
            return $this->unauthorizedResponse();
        }

        $otherUser = User::find($userId);
        if (!$otherUser) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        $validated = $request->validate([
            'body' => 'required|string|max:2000',
        ]);

        $id = DB::table('chat_messages')->insertGetId([
            'sender_id' => $currentUser->id,
            'receiver_id' => $otherUser->id,
            'is_global' => false,
            'body' => trim($validated['body']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Message sent',
            'id' => $id,
        ]);
    }

    public function toggleReaction(Request $request, int $messageId)
    {
        $user = $this->getAuthenticatedUser($request);
        if (!$user) {
            return $this->unauthorizedResponse();
        }

        $validated = $request->validate([
            'emoji' => 'required|string|max:10',
        ]);

        $message = DB::table('chat_messages')->where('id', $messageId)->first();
        if (!$message) {
            return response()->json([
                'success' => false,
                'message' => 'Message not found',
            ], 404);
        }

        $existing = DB::table('message_reactions')
            ->where('message_id', $messageId)
            ->where('user_id', $user->id)
            ->where('emoji', $validated['emoji'])
            ->first();

        if ($existing) {
            DB::table('message_reactions')->where('id', $existing->id)->delete();
        } else {
            DB::table('message_reactions')->insert([
                'message_id' => $messageId,
                'user_id' => $user->id,
                'emoji' => $validated['emoji'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Reaction updated',
        ]);
    }
}
