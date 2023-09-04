<?php

namespace App\Notifications;

use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReservationRecue extends Notification implements ShouldQueue
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
                    ->subject('Reservation obtenue.')
                    ->greeting("Réception d'une réservation.")
                    ->line(" Bonjour {$this->name} vous avez recue une demande de réservation concernant une de vos offres de trajet.")
                    ->line("Connectez vous afin de pouvoir traiter cette offre.")
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
        $contenu = "Réservation obtenue.
                    Réception d'une réservation.
                    Bonjour vous avez recue une demande de réservation concernant une de vos offres de trajet.
                    Connectez vous afin de pouvoir traiter cette offre.
                    Merci d'utiliser notre application!";
        return ['contenu'=>$contenu];
    }

}
