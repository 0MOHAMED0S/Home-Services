<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;

class FreelancerNotificationEvent implements ShouldQueue
{
    use Dispatchable, SerializesModels;

    public $user;
    public $title;
    public $message;

    public function __construct(User $user, $title, $message)
    {
        $this->user = $user;
        $this->title = $title;
        $this->message = $message;
    }
}
