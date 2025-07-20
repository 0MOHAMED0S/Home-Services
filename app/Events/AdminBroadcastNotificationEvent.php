<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AdminBroadcastNotificationEvent
{
    use Dispatchable, SerializesModels;

    public $title;
    public $message;
    public $targets; // clients | freelancers | all

    public function __construct($title, $message, $targets)
    {
        $this->title = $title;
        $this->message = $message;
        $this->targets = $targets;
    }
}
