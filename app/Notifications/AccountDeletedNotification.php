<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountDeletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected string $name,
        protected bool $initiatedByUser
    ) {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $mailMessage = (new MailMessage)
            ->subject('Your Recruivo account has been deleted')
            ->greeting('Hi ' . $this->name)
            ->line($this->initiatedByUser
                ? 'This email confirms that your Recruivo account has been deleted.'
                : 'This email is to let you know that an administrator has removed your Recruivo account.');

        if (!$this->initiatedByUser) {
            $mailMessage->line('If you believe this was a mistake, please contact support to review the action.');
        }

        return $mailMessage
            ->line('We appreciate the time you spent on Recruivo and you are welcome back any time.')
            ->salutation('— The Recruivo Team');
    }

    public function toArray($notifiable): array
    {
        return [
            'initiated_by_user' => $this->initiatedByUser,
        ];
    }
}
