@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <!-- Header -->
    <div>
        <h1 class="text-3xl font-bold text-stone-900 dark:text-white">
            {{ __('companies.browse_companies_title') }}
        </h1>
        <p class="mt-2 text-stone-600 dark:text-stone-400">
            {{ __('companies.browse_companies_subtitle') }}
        </p>
    </div>

    <!-- Companies Grid -->
    <div>
        @if(count($companies) > 0)
            <div class="grid gap-6 md:grid-cols-2">
                @foreach($companies as $company)
                    <x-company-card :company="$company" />
                @endforeach
            </div>
        @else
            <div class="rounded-xl border border-stone-200/60 bg-white/60 p-12 text-center backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/40">
                <h3 class="text-lg font-semibold text-stone-900 dark:text-white">{{ __('companies.no_companies_found_index') }}</h3>
                <p class="mt-2 text-stone-600 dark:text-stone-400">
                    {{ __('companies.check_back_for_companies') }}
                </p>
            </div>
        @endif

        <!-- Pagination -->
        @if($companies->hasPages())
            <div class="mt-8">
                <x-pagination :paginator="$companies" />
            </div>
        @endif
    </div>
</div>
@endsection

