<?php

namespace App\Notifications;

use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReservationAcceptee extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public readonly string $name,
        public readonly string $email,
        public readonly CarbonInterface $datetime)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->subject('Reservation acceptée.')
                    ->greeting("Votre demande de réservation a été acceptée.")
                    ->line(" Bonjour {$this->name} votre demande de réservation a été acceptée par le conducteur.")
                    ->line("Connectez vous afin de discuter du trajet.")
                    ->line("Merci d'utiliser notre application!");
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }

    public function toDatabase(object $notifiable): array
    {
        $contenu = "Reservation acceptée.
                    Votre demande de réservation a été acceptée.
                    Bonjour votre demande de réservation a été acceptée par le conducteur.
                    Connectez vous afin de discuter du trajet.
                    Merci d'utiliser notre application!";
        return ['contenu'=>$contenu];
    }

}
