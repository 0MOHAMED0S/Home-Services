<?php

namespace App\Http\Controllers\Api\Admin;

use App\Events\AdminBroadcastNotificationEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SendNotificationRequest;
use Illuminate\Http\Request;

class AdminNotificationController extends Controller
{
    public function sendNotification(SendNotificationRequest $request)
    {
        event(new AdminBroadcastNotificationEvent(
            $request->title,
            $request->message,
            $request->targets
        ));

        return response()->json([
            'status' => 200,
            'message' => 'Notification sent successfully.'
        ]);
    }
}
