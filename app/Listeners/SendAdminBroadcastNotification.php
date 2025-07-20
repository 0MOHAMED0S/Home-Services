<?php

namespace App\Listeners;

use App\Events\AdminBroadcastNotificationEvent;
use App\Jobs\SendBroadcastEmails;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Http;
use App\Models\Client;
use App\Models\Freelancer;
use App\Models\User;

class SendAdminBroadcastNotification implements ShouldQueue
{
    public function handle(AdminBroadcastNotificationEvent $event)
    {
        $recipients = [];

        if ($event->targets === 'clients') {
            $recipients = User::whereNotNull('onesignal_id')->pluck('onesignal_id')->toArray();
        } elseif ($event->targets === 'freelancers') {
            $recipients = Freelancer::whereNotNull('onesignal_id')->pluck('onesignal_id')->toArray();
        } elseif ($event->targets === 'all') {
            $clients = User::whereNotNull('onesignal_id')->pluck('onesignal_id')->toArray();
            $freelancers = Freelancer::whereNotNull('onesignal_id')->pluck('onesignal_id')->toArray();
            $recipients = array_merge($clients, $freelancers);
        }

        if (empty($recipients)) return;

        // Send push notification
        Http::withHeaders([
            'Authorization' => 'Basic ' . env('ONESIGNAL_REST_API_KEY'),
            'Content-Type'  => 'application/json',
        ])->post('https://onesignal.com/api/v1/notifications', [
            'app_id' => env('ONESIGNAL_APP_ID'),
            'include_player_ids' => $recipients,
            'headings' => ['en' => $event->title],
            'contents' => ['en' => $event->message],
        ]);

        // Dispatch email job
        SendBroadcastEmails::dispatch($event->title, $event->message, $event->targets);
    }
}
