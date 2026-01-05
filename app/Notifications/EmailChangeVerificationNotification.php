<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailChangeVerificationNotification extends Notification
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
        $accountType = $this->user->hasRole('Recruiter') ? 'recruiter' : 'candidate';
        
        // Use the custom email change verification URL
        $verificationUrl = $this->user->getEmailChangeVerificationUrl();

        $mailMessage = (new MailMessage)
            ->subject('Verify Your New Email Address')
            ->greeting('Hello ' . $this->user->name . '!')
            ->line('You have changed your email address. Please verify your new email address to continue using your account.')
            ->action('Verify Email Address', $verificationUrl)
            ->line('After verifying you can:');

        if ($this->user->hasRole('Candidate')) {
            $mailMessage->line('• Continue browsing and applying to jobs')
                ->line('• Manage your applications');
        }

        if ($this->user->hasRole('Recruiter')) {
            $mailMessage->line('• Continue posting job opportunities')
                ->line('• Manage applications');
        }

        return $mailMessage
            ->line('If you did not change your email address, please contact our support team immediately.');
    }

    public function toArray($notifiable): array
    {
        return [
            'user_id' => $this->user->id,
            'account_type' => $this->user->hasRole('Recruiter') ? 'recruiter' : 'candidate',
            'action' => 'email_change_verification',
        ];
    }
}
