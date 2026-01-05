@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-stone-900 dark:text-white">{{ __('recruiter.create_job_title') }}</h1>
            <p class="mt-2 text-stone-600 dark:text-stone-400">{{ __('recruiter.create_job_subtitle') }}</p>
        </div>
        <a 
            href="{{ localized_route('recruiter.jobs.index') }}" 
            class="inline-flex items-center justify-center rounded-2xl bg-stone-100 px-6 py-3 text-sm font-semibold text-stone-700 shadow-sm transition hover:bg-stone-200 dark:bg-stone-800 dark:text-stone-300 dark:hover:bg-stone-700"
        >
            <svg class="mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
            {{ __('recruiter.back_to_jobs') }}
        </a>
    </div>

    @if(session('success'))
        <x-alert type="success">
            {{ session('success') }}
        </x-alert>
    @endif

    @if($errors->any())
        <x-alert type="error">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </x-alert>
    @endif

    <div class="rounded-xl border border-stone-200/60 bg-white/80 p-8 backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/60">
        <form method="POST" action="{{ localized_route('recruiter.jobs.store') }}" class="space-y-6">
            @csrf

            <div class="grid gap-6 md:grid-cols-2">
                <div class="space-y-2">
                    <label for="title" class="text-sm font-medium text-stone-700 dark:text-stone-200">
                        {{ __('recruiter.job_title') }}
                    </label>
                    <input
                        id="title"
                        name="title"
                        type="text"
                        value="{{ old('title') }}"
                        required
                        class="w-full rounded-2xl border border-stone-200/80 bg-white/80 px-4 py-3 text-sm text-stone-700 shadow-sm transition focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-700 dark:bg-stone-900/70 dark:text-stone-100 dark:focus:border-amber-500"
                        placeholder="{{ __('recruiter.job_title_placeholder') }}"
                    />
                </div>

                <div class="space-y-2">
                    <label for="location" class="text-sm font-medium text-stone-700 dark:text-stone-200">
                        {{ __('recruiter.location') }}
                    </label>
                    <input
                        id="location"
                        name="location"
                        type="text"
                        value="{{ old('location') }}"
                        class="w-full rounded-2xl border border-stone-200/80 bg-white/80 px-4 py-3 text-sm text-stone-700 shadow-sm transition focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-700 dark:bg-stone-900/70 dark:text-stone-100 dark:focus:border-amber-500"
                        placeholder="{{ __('recruiter.location_placeholder') }}"
                    />
                </div>
            </div>

            <div class="space-y-2">
                <label for="description" class="text-sm font-medium text-stone-700 dark:text-stone-200">
                    {{ __('recruiter.job_description') }}
                </label>
                <textarea
                    id="description"
                    name="description"
                    rows="6"
                    required
                    class="w-full rounded-2xl border border-stone-200/80 bg-white/80 px-4 py-3 text-sm text-stone-700 shadow-sm transition focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-700 dark:bg-stone-900/70 dark:text-stone-100 dark:focus:border-amber-500"
                    placeholder="{{ __('recruiter.description_placeholder') }}"
                >{{ old('description') }}</textarea>
            </div>

            <div class="grid gap-6 md:grid-cols-3">
                <div class="space-y-2">
                    <label for="category" class="text-sm font-medium text-stone-700 dark:text-stone-200">
                        {{ __('recruiter.category') }}
                    </label>
                    <select
                        id="category"
                        name="category"
                        class="w-full rounded-2xl border border-stone-200/80 bg-white/80 px-4 py-3 text-sm text-stone-700 shadow-sm transition focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-700 dark:bg-stone-900/70 dark:text-stone-100 dark:focus:border-amber-500"
                    >
                        <option value="">{{ __('recruiter.select_category') }}</option>
                        <option value="Engineering" {{ old('category') === 'Engineering' ? 'selected' : '' }}>{{ __('recruiter.engineering') }}</option>
                        <option value="Design" {{ old('category') === 'Design' ? 'selected' : '' }}>{{ __('recruiter.design') }}</option>
                        <option value="Product" {{ old('category') === 'Product' ? 'selected' : '' }}>{{ __('recruiter.product') }}</option>
                        <option value="Marketing" {{ old('category') === 'Marketing' ? 'selected' : '' }}>{{ __('recruiter.marketing') }}</option>
                        <option value="Sales" {{ old('category') === 'Sales' ? 'selected' : '' }}>{{ __('recruiter.sales') }}</option>
                        <option value="Operations" {{ old('category') === 'Operations' ? 'selected' : '' }}>{{ __('recruiter.operations') }}</option>
                    </select>
                </div>

                <div class="space-y-2">
                    <label for="remote_type" class="text-sm font-medium text-stone-700 dark:text-stone-200">
                        {{ __('recruiter.remote_type') }}
                    </label>
                    <select
                        id="remote_type"
                        name="remote_type"
                        class="w-full rounded-2xl border border-stone-200/80 bg-white/80 px-4 py-3 text-sm text-stone-700 shadow-sm transition focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-700 dark:bg-stone-900/70 dark:text-stone-100 dark:focus:border-amber-500"
                    >
                        <option value="">{{ __('recruiter.select_remote_type') }}</option>
                        <option value="remote" {{ old('remote_type') === 'remote' ? 'selected' : '' }}>{{ __('recruiter.remote') }}</option>
                        <option value="hybrid" {{ old('remote_type') === 'hybrid' ? 'selected' : '' }}>{{ __('recruiter.hybrid') }}</option>
                        <option value="onsite" {{ old('remote_type') === 'onsite' ? 'selected' : '' }}>{{ __('recruiter.onsite') }}</option>
                    </select>
                </div>

                <div class="space-y-2">
                    <label for="status" class="text-sm font-medium text-stone-700 dark:text-stone-200">
                        {{ __('recruiter.status') }}
                    </label>
                    <select
                        id="status"
                        name="status"
                        class="w-full rounded-2xl border border-stone-200/80 bg-white/80 px-4 py-3 text-sm text-stone-700 shadow-sm transition focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-700 dark:bg-stone-900/70 dark:text-stone-100 dark:focus:border-amber-500"
                    >
                        <option value="draft" {{ old('status') === 'draft' ? 'selected' : '' }}>{{ __('recruiter.draft') }}</option>
                        <option value="published" {{ old('status') === 'published' ? 'selected' : '' }}>{{ __('recruiter.published') }}</option>
                    </select>
                </div>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <div class="space-y-2">
                    <label for="salary_min" class="text-sm font-medium text-stone-700 dark:text-stone-200">
                        {{ __('recruiter.minimum_salary') }}
                    </label>
                    <input
                        id="salary_min"
                        name="salary_min"
                        type="number"
                        value="{{ old('salary_min') }}"
                        class="w-full rounded-2xl border border-stone-200/80 bg-white/80 px-4 py-3 text-sm text-stone-700 shadow-sm transition focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-700 dark:bg-stone-900/70 dark:text-stone-100 dark:focus:border-amber-500"
                        placeholder="{{ __('recruiter.salary_placeholder_min') }}"
                    />
                </div>

                <div class="space-y-2">
                    <label for="salary_max" class="text-sm font-medium text-stone-700 dark:text-stone-200">
                        {{ __('recruiter.maximum_salary') }}
                    </label>
                    <input
                        id="salary_max"
                        name="salary_max"
                        type="number"
                        value="{{ old('salary_max') }}"
                        class="w-full rounded-2xl border border-stone-200/80 bg-white/80 px-4 py-3 text-sm text-stone-700 shadow-sm transition focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-700 dark:bg-stone-900/70 dark:text-stone-100 dark:focus:border-amber-500"
                        placeholder="{{ __('recruiter.salary_placeholder_max') }}"
                    />
                </div>
            </div>

            <div class="flex justify-end gap-4">
                <a 
                    href="{{ localized_route('recruiter.jobs.index') }}" 
                    class="inline-flex items-center justify-center rounded-2xl bg-stone-100 px-6 py-3 text-sm font-semibold text-stone-700 shadow-sm transition hover:bg-stone-200 dark:bg-stone-800 dark:text-stone-300 dark:hover:bg-stone-700"
                >
                    {{ __('recruiter.cancel') }}
                </a>
                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-2xl bg-amber-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-amber-500/30 transition hover:bg-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200"
                >
                    {{ __('recruiter.create_job') }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
