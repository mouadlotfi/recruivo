<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordChangedNotification extends Notification
{
    use Queueable;

    public function __construct(protected User $user)
    {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $loginUrl = localized_route('login', [], config('app.locale', 'en'));
        
        return (new MailMessage)
            ->subject('Your password has been changed')
            ->greeting('Hello ' . $this->user->name)
            ->line('This is to confirm that your password has been successfully changed.')
            ->line('If you did not make this change, please contact our support team immediately.')
            ->action('Sign in to your account', $loginUrl)
            ->line('For security reasons, if you did not make this change, we recommend changing your password again.');
    }

    public function toArray($notifiable): array
    {
        return [
            'user_id' => $this->user->id,
            'action' => 'password_changed',
        ];
    }
}
