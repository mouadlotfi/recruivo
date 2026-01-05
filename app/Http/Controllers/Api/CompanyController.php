<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CompanyDetailResource;
use App\Http\Resources\CompanyResource;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CompanyController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $companies = Company::query()
            ->withCount(['jobs' => fn ($query) => $query->published()])
            ->with(['jobs' => fn ($query) => $query->published()->latest('published_at')->limit(3)])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->input('search');

                $query->where(function ($builder) use ($search) {
                    $builder->where('name', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%")
                        ->orWhere('tagline', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('jobs_count')
            ->paginate(12)
            ->withQueryString();

        return CompanyResource::collection($companies);
    }

    public function show(Company $company): CompanyDetailResource
    {
        $company->load(['jobs' => fn ($query) => $query
            ->published()
            ->withCount('applications')
            ->latest('published_at')
        ]);

        return new CompanyDetailResource($company);
    }
}
