<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ApplicationController extends Controller
{
    public function index(Request $request)
    {
        $applications = $request->user()
            ->applications()
            ->with(['job.company'])
            ->latest()
            ->paginate(10);

        return view('candidate.applications', compact('applications'));
    }
}

