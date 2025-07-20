<?php

namespace App\Listeners;

use App\Events\UserNotificationEvent;
use Illuminate\Support\Facades\Http;
use Illuminate\Contracts\Queue\ShouldQueue;
class SendPushNotificationToFreelancer implements ShouldQueue
{
    public function handle(UserNotificationEvent $event)
    {
        $freelancer = $event->freelancer;

        if (!$freelancer->onesignal_id) return;

        Http::withHeaders([
            'Authorization' => 'Basic ' . env('ONESIGNAL_REST_API_KEY'),
            'Content-Type' => 'application/json',
        ])->post('https://onesignal.com/api/v1/notifications', [
            'app_id' => env('ONESIGNAL_APP_ID'),
            'include_player_ids' => [$freelancer->onesignal_id],
            'headings' => ['en' => $event->title],
            'contents' => ['en' => $event->message],
        ]);
    }
}

