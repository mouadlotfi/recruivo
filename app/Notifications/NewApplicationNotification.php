<?php

namespace App\Notifications;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewApplicationNotification extends Notification
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
        $applicationsUrl = localized_route('recruiter.jobs.applications', ['job' => $job->id], config('app.locale', 'en'));
        
        return (new MailMessage)
            ->subject('New application for '.$job->title)
            ->greeting('Hello '.$notifiable->name)
            ->line($this->application->candidate->name.' has applied to '.$job->title)
            ->action('View application', $applicationsUrl)
            ->line('Log in to review the candidate.');
    }

    public function toArray($notifiable): array
    {
        return [
            'job_id' => $this->application->job_id,
            'application_id' => $this->application->id,
            'candidate_id' => $this->application->candidate_id,
        ];
    }
}
