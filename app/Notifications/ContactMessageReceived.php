<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Delivers a message posted on the public contact page to the address in
 * config('mail.contact_address'). Reply-To is the sender, so replying from the
 * mailbox answers the visitor directly.
 */
class ContactMessageReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected string $senderName,
        protected string $senderEmail,
        protected string $body
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $lines = preg_split('/\r\n|\n|\r/', $this->body) ?: [];

        return (new MailMessage)
            ->subject('Contact form: '.$this->senderName)
            ->replyTo($this->senderEmail, $this->senderName)
            ->greeting('New message from the contact page')
            ->line('From: '.$this->senderName.' <'.$this->senderEmail.'>')
            ->line('Message:')
            ->lines($lines)
            ->salutation('— Recruivo');
    }

    /**
     * @return array<string, string>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'sender_name' => $this->senderName,
            'sender_email' => $this->senderEmail,
        ];
    }
}
