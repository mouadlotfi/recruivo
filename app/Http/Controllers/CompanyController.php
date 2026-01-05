<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function index()
    {
        $companies = Company::withCount('jobs')
            ->with(['jobs' => function ($query) {
                $query->published()
                    ->select('id', 'company_id', 'title', 'published_at')
                    ->latest('published_at')
                    ->take(3);
            }])
            ->latest()
            ->paginate(12);

        return view('companies.index', compact('companies'));
    }

    public function show(string $locale, string $slug)
    {
        $company = Company::where('slug', $slug)->firstOrFail();
        
        $company->load(['jobs' => function ($query) {
            $query->published()
                ->withCount('applications')
                ->latest('published_at');
        }]);

        return view('companies.show', compact('company'));
    }
}

