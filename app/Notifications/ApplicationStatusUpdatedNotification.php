<?php

namespace App\Notifications;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApplicationStatusUpdatedNotification extends Notification
{
    use Queueable;

    public function __construct(protected Application $application)
    {
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $job = $this->application->job;
        $companyName = $job->company?->name ?? 'the hiring team';
        $statusLabel = ucfirst($this->application->status->value);

        $mailMessage = (new MailMessage)
            ->subject('Update on your '.$job->title.' application')
            ->greeting('Hi '.$notifiable->name)
            ->line(''.$companyName.' has marked your application for '.$job->title.' as '.$statusLabel.'.');

        // Add notes if they exist
        if ($this->application->notes) {
            $mailMessage->line('Additional notes from the recruiter:')
                ->line('"' . $this->application->notes . '"');
        }

        $applicationsUrl = localized_route('candidate.applications', [], config('app.locale', 'en'));
        
        $mailMessage->line('Sign in to review any notes, next steps, or to send a quick update back to the recruiter.')
            ->action('Review your application', $applicationsUrl)
            ->line('Thank you for trusting Recruivo with your job search.');

        return $mailMessage;
    }

    public function toArray($notifiable): array
    {
        return [
            'job_id' => $this->application->job_id,
            'application_id' => $this->application->id,
            'status' => $this->application->status->value,
        ];
    }
}

