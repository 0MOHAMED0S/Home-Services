<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Http;
use App\Models\Freelancer;
use Illuminate\Support\Facades\Event;

class UserNotificationEvent implements ShouldQueue
{
    use Dispatchable, SerializesModels;
    public $freelancer;
    public $title;
    public $message;

    public function __construct($freelancer, $title, $message)
    {
        $this->freelancer = $freelancer;
        $this->title = $title;
        $this->message = $message;
    }
}


