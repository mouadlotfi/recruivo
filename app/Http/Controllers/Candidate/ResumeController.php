<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class ResumeController extends Controller
{
    /**
     * Display the candidate's resume in a new tab/window.
     */
    public function view()
    {
        $user = auth()->user();

        if (! $user->candidateProfile || ! $user->candidateProfile->resume_path) {
            abort(404, __('profile.resume_not_found'));
        }

        $resumePath = $user->candidateProfile->resume_path;

        if (! Storage::disk('private')->exists($resumePath)) {
            abort(404, __('profile.resume_file_not_found'));
        }

        $file = Storage::disk('private')->get($resumePath);
        $mimeType = Storage::disk('private')->mimeType($resumePath);
        $fileName = basename($resumePath);

        return response($file, 200)
            ->header('Content-Type', $mimeType)
            ->header('Content-Disposition', 'inline; filename="'.$fileName.'"');
    }
}
