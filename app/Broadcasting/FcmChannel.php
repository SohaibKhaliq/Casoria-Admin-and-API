<?php

namespace App\Broadcasting;

use App\Models\User;
use Illuminate\Notifications\Notification;

class FcmChannel
{
    /**
     * Create a new channel instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Authenticate the user's access to the channel.
     *
     * @return array|bool
     */
    public function join(User $user)
    {
        //
    }

    public function send($notifiable, Notification $notification)
    {
        $notification->tofcm($notifiable, $notification);
    }
}
