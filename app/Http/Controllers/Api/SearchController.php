<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Job;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function suggestions(Request $request)
    {
        $query = $request->input('q', ''); // Fixed: was 'query', should be 'q'
        
        // Allow search from 1 character instead of 2
        if (strlen($query) < 1) {
            return response()->json([]);
        }

        $locale = app()->getLocale();

        // Search jobs - enhanced to match main search
        $jobs = Job::published()
            ->with('company:id,name,slug,logo_path')
            ->where(function ($builder) use ($query) {
                $builder->where('title', 'like', "{$query}%") // Start with query for first letter
                    ->orWhere('location', 'like', "{$query}%")
                    ->orWhere('category', 'like', "{$query}%")
                    ->orWhere('description', 'like', "%{$query}%")
                    ->orWhere('remote_type', 'like', "{$query}%")
                    ->orWhereHas('company', function ($q) use ($query) {
                        $q->where('name', 'like', "{$query}%");
                    });
            })
            ->limit(5)
            ->get()
            ->map(function ($job) use ($locale) {
                return [
                    'id' => $job->id,
                    'type' => 'job',
                    'title' => $job->title,
                    'subtitle' => $job->company->name . ' • ' . $job->location,
                    'url' => localized_route('jobs.show', ['job' => $job->id], $locale),
                    'logo' => $job->company->logo_url,
                ];
            });

        // Search companies - enhanced
        $companies = Company::where(function ($builder) use ($query) {
                $builder->where('name', 'like', "{$query}%") // Start with query for first letter
                    ->orWhere('location', 'like', "{$query}%")
                    ->orWhere('tagline', 'like', "%{$query}%")
                    ->orWhere('mission', 'like', "%{$query}%");
            })
            ->limit(5)
            ->get()
            ->map(function ($company) use ($locale) {
                return [
                    'id' => $company->id,
                    'type' => 'company',
                    'title' => $company->name,
                    'subtitle' => $company->location . ($company->tagline ? ' • ' . $company->tagline : ''),
                    'url' => localized_route('companies.show', ['slug' => $company->slug], $locale),
                    'logo' => $company->logo_url,
                ];
            });

        // Interleave jobs and companies for better mix
        $results = collect();
        $maxLength = max($jobs->count(), $companies->count());
        
        for ($i = 0; $i < $maxLength; $i++) {
            if (isset($companies[$i])) {
                $results->push($companies[$i]); // Companies first
            }
            if (isset($jobs[$i])) {
                $results->push($jobs[$i]);
            }
        }
        
        return response()->json($results->take(10));
    }
}
