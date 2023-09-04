<?php

namespace App\Http\Traits;

use App\Models\User;
use App\Notifications\ReservationRecue;
use App\Notifications\ReservationAcceptee;

class NotificationService
{
    public function sendReceivedReservationNotification(User $user)
    {
        $prenom = $user->prenom;
        $email = $user->email;
        $date = now();
        $user->notify(new ReservationRecue($prenom, $email, $date));
    }

    public function sendAcceptedReservationNotification(User $user)
    {
        $prenom = $user->prenom;
        $email = $user->email;
        $date = now();
        $user->notify(new ReservationAcceptee($prenom, $email, $date));
    }

}
