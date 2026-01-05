<?php

namespace App\Http\Controllers\Api\Candidate;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApplicationResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApplicationController extends Controller
{
    /**
     * Get all applications for the authenticated candidate.
     */
    public function index(Request $request)
    {
        $candidate = Auth::user();
        
        $applications = $candidate->applications()
            ->with(['job.company'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
        return ApplicationResource::collection($applications);
    }
}
