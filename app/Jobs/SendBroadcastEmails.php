<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Client;
use App\Models\Freelancer;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use App\Mail\AdminBroadcastEmail;

class SendBroadcastEmails implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $title;
    public $message;
    public $targets;

    public function __construct($title, $message, $targets)
    {
        $this->title = $title;
        $this->message = $message;
        $this->targets = $targets;
    }

    public function handle()
    {
        $recipients = [];

        if ($this->targets === 'clients') {
            $recipients = User::pluck('email')->toArray();
        } elseif ($this->targets === 'freelancers') {
            $recipients = Freelancer::pluck('email')->toArray();
        } elseif ($this->targets === 'all') {
            $clients = User::pluck('email')->toArray();
            $freelancers = Freelancer::pluck('email')->toArray();
            $recipients = array_merge($clients, $freelancers);
        }

        foreach ($recipients as $email) {
            Mail::to($email)->send(new AdminBroadcastEmail($this->title, $this->message));
        }
    }
}
