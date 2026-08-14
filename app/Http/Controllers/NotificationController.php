<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function open(Request $request, string $locale, string $notification): RedirectResponse
    {
        /** @var DatabaseNotification $databaseNotification */
        $databaseNotification = $request->user()
            ->notifications()
            ->whereKey($notification)
            ->firstOrFail();

        $databaseNotification->markAsRead();

        return redirect($this->destination($request, $databaseNotification));
    }

    public function markAllAsRead(Request $request, string $locale): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back();
    }

    private function destination(Request $request, DatabaseNotification $notification): string
    {
        $data = $notification->data;

        if (($data['kind'] ?? null) === 'new_application' && $request->user()->hasRole('Recruiter')) {
            return localized_route('recruiter.jobs.applications', ['job' => $data['job_id']]);
        }

        if (($data['kind'] ?? null) === 'application_status_updated' && $request->user()->hasRole('Candidate')) {
            return localized_route('candidate.applications');
        }

        return $request->user()->hasRole('Recruiter')
            ? localized_route('recruiter.dashboard')
            : localized_route('candidate.dashboard');
    }
}
