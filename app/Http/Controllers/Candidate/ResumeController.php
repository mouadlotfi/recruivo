<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class ResumeController extends Controller
{
    /**
     * Display the candidate's resume in a new tab/window.
     */
    public function view()
    {
        $user = auth()->user();
        
        if (!$user->candidateProfile || !$user->candidateProfile->resume_path) {
            abort(404, __('profile.resume_not_found'));
        }
        
        $resumePath = $user->candidateProfile->resume_path;
        
        // Check if file exists
        if (!Storage::disk('public')->exists($resumePath)) {
            abort(404, __('profile.resume_file_not_found'));
        }
        
        // Get file content and MIME type
        $file = Storage::disk('public')->get($resumePath);
        $mimeType = Storage::disk('public')->mimeType($resumePath);
        $fileName = basename($resumePath);
        
        // Return file as inline (viewable in browser)
        return response($file, 200)
            ->header('Content-Type', $mimeType)
            ->header('Content-Disposition', 'inline; filename="' . $fileName . '"');
    }
}

