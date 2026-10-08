<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Eigen, volledig Nederlandse notificatie voor een nieuw aangemaakte gebruiker (zie
 * UserResource::sendSetPasswordLink()) — niet Filament\Notifications\Auth\ResetPassword
 * hergebruikt, want die tekst ("Reset Password Notification" etc.) komt bij Laravel's eigen
 * Illuminate\Auth\Notifications\ResetPassword vandaan en staat vast in het Engels (Lang::get() met
 * de Engelse tekst zelf als sleutel — zonder een gepubliceerde nl-vertaling van precies die
 * sleutels valt dat altijd terug op het Engels). Bewust géén ShouldQueue: deze app draait zonder
 * actieve queue-worker, dus moet dit altijd synchroon verstuurd kunnen worden.
 */
class SetPasswordNotification extends Notification
{
    public function __construct(private readonly string $url) {}

    /** @return array<int, string> */
    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $minutes = (int) config('auth.passwords.users.expire', 60);

        return (new MailMessage)
            ->subject('Stel je wachtwoord in')
            ->greeting("Hallo {$notifiable->name},")
            ->line('Er is een account voor je aangemaakt in het beheerpaneel van de Woondroomtest.')
            ->line('Klik op de knop hieronder om zelf een wachtwoord in te stellen en in te loggen.')
            ->action('Wachtwoord instellen', $this->url)
            ->line("Deze link is {$minutes} minuten geldig.")
            ->line('Heb je dit niet verwacht? Dan kun je deze e-mail gewoon negeren.');
    }
}
