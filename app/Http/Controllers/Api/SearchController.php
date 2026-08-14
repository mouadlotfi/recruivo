<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SmartSearchService;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function suggestions(Request $request, SmartSearchService $searchService)
    {
        $query = $searchService->normalize($request->input('q', ''));
        
        // Allow search from 1 character instead of 2
        if (strlen($query) < 1) {
            return response()->json([
                'query' => '',
                'sections' => [],
                'search_url' => localized_route('search'),
            ]);
        }

        $locale = app()->getLocale();

        // Search jobs - enhanced to match main search
        $jobs = $searchService->jobs($query)->take(5)
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
        $companies = $searchService->companies($query)->take(5)
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

        $sections = collect([
            ['type' => 'jobs', 'label' => __('common.jobs'), 'items' => $jobs->values()],
            ['type' => 'companies', 'label' => __('common.companies'), 'items' => $companies->values()],
        ])->filter(fn ($section) => $section['items']->isNotEmpty())->values();

        return response()->json([
            'query' => $query,
            'sections' => $sections,
            'search_url' => localized_route('search', ['search' => $query], $locale),
        ]);
    }
}
