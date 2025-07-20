<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
Broadcast::channel('chat.{conversationId}', function ($authUser, $conversationId) {
    $conversation = \App\Models\Conversation::find($conversationId);

    if (! $conversation) {
        return false;
    }

    // Handle both guards: user and freelancer
    if ($authUser instanceof \App\Models\User) {
        return $conversation->user_id === $authUser->id;
    }

    if ($authUser instanceof \App\Models\Freelancer) {
        return $conversation->freelancer_id === $authUser->id;
    }

    return false;
});

