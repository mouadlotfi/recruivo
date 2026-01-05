<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SignupConfirmationNotification extends Notification
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
        
        // Use Laravel's built-in verification URL generation
        $verificationUrl = $notifiable->getEmailVerificationUrl();

        $mailMessage = (new MailMessage)
            ->subject('Welcome to Recruivo!')
            ->greeting('Welcome to Recruivo, ' . $this->user->name . '!')
            ->line('Thank you for signing up as a ' . $accountType . '.')
            ->line('Please verify your email address to activate your account and unlock every feature.')
            ->action('Verify your email', $verificationUrl)
            ->line('After verifying you can:');

        if ($this->user->hasRole('Candidate')) {
            $mailMessage->line('• Browsing and applying to jobs')
                ->line('• Managing your applications');
        }

        if ($this->user->hasRole('Recruiter')) {
            $mailMessage->line('• Posting job opportunities')
                ->line('• Managing applications');
        }

        return $mailMessage
            ->line('If you have any questions, feel free to reach out to our support team.');
    }

    public function toArray($notifiable): array
    {
        return [
            'user_id' => $this->user->id,
            'account_type' => $this->user->hasRole('Recruiter') ? 'recruiter' : 'candidate',
        ];
    }
}
