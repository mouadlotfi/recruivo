<?php

namespace App\Notifications;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApplicationStatusUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected Application $application) {}

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

        if ($this->application->status === ApplicationStatus::Interview && $this->application->interview_at) {
            $mailMessage->line('Interview scheduled for '.$this->application->interview_at->translatedFormat('l, F j, Y \a\t g:i A'));
            if ($this->application->interview_location) {
                $mailMessage->line('Location: '.$this->application->interview_location);
            }
            if ($this->application->interview_url) {
                $mailMessage->line('Meeting link: '.$this->application->interview_url);
            }
            if ($this->application->interview_instructions) {
                $mailMessage->line($this->application->interview_instructions);
            }
        }

        if ($this->application->notes) {
            $mailMessage->line('Additional notes from the recruiter:')
                ->line('"'.$this->application->notes.'"');
        }

        $applicationsUrl = localized_route('candidate.applications', [], config('app.locale', 'en'));

        $mailMessage->line('Sign in to review any notes, next steps, or to send a quick update back to the recruiter.')
            ->action('Review your application', $applicationsUrl)
            ->line('Thank you for trusting Recruivo with your job search.');

        return $mailMessage;
    }

    public function toArray($notifiable): array
    {
        $this->application->loadMissing('job.company');

        $data = [
            'kind' => 'application_status_updated',
            'job_id' => $this->application->job_id,
            'application_id' => $this->application->id,
            'status' => $this->application->status->value,
            'job_title' => $this->application->job->title,
            'company_name' => $this->application->job->company?->name,
        ];

        if ($this->application->status === ApplicationStatus::Interview) {
            $data['interview_at'] = $this->application->interview_at?->format('Y-m-d H:i');
            $data['interview_location'] = $this->application->interview_location;
            $data['interview_url'] = $this->application->interview_url;
            $data['interview_instructions'] = $this->application->interview_instructions;
        }

        return $data;
    }
}
