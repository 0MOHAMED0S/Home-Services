<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use App\Mail\GeneralNotificationMail;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, Queueable, SerializesModels;

    protected $email;
    protected $title;
    protected $message;

    public function __construct($email, $title, $message)
    {
        $this->email = $email;
        $this->title = $title;
        $this->message = $message;
    }

    public function handle()
    {
        Mail::to($this->email)->send(new GeneralNotificationMail($this->title, $this->message));
    }
}
