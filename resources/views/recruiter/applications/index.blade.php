@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-stone-900 dark:text-white">{{ __('recruiter.applications_for', ['job' => $job->title]) }}</h1>
            <p class="mt-2 text-stone-600 dark:text-stone-400">{{ trans_choice('recruiter.applications_received', $applications->total(), ['count' => $applications->total()]) }}</p>
        </div>
        <a 
            href="{{ localized_route('recruiter.jobs.index') }}" 
            class="inline-flex items-center justify-center rounded-2xl bg-stone-100 px-6 py-3 text-sm font-semibold text-stone-700 shadow-sm transition hover:bg-stone-200 dark:bg-stone-800 dark:text-stone-300 dark:hover:bg-stone-700"
        >
            <svg class="mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
            {{ __('recruiter.back_to_jobs_list') }}
        </a>
    </div>

    @if(session('success'))
        <x-alert type="success">
            {{ session('success') }}
        </x-alert>
    @endif

    @if(session('error'))
        <x-alert type="error">
            {{ session('error') }}
        </x-alert>
    @endif

    @if($applications->isEmpty())
        <div class="rounded-xl border border-stone-200/60 bg-white/80 p-12 text-center backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/60">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-stone-100 dark:bg-stone-800">
                <svg class="h-8 w-8 text-stone-600 dark:text-stone-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-stone-900 dark:text-white mb-2">{{ __('recruiter.no_applications_received') }}</h3>
            <p class="text-stone-600 dark:text-stone-400">{{ __('recruiter.applications_appear_message') }}</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach($applications as $application)
                <div class="rounded-xl border border-stone-200/60 bg-white/80 p-6 backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/60 transition hover:border-amber-200 dark:hover:border-amber-800">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-amber-100 text-lg font-semibold text-amber-700 dark:bg-amber-500/10 dark:text-amber-400">
                                    {{ substr($application->candidate->name, 0, 1) }}
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-stone-900 dark:text-white">
                                        {{ $application->candidate->name }}
                                    </h3>
                                    <p class="text-sm text-stone-600 dark:text-stone-400">{{ $application->candidate->email }}</p>
                                </div>
                                @if($application->status->value === 'pending')
                                    <span class="inline-flex items-center rounded-full bg-yellow-100 px-3 py-1 text-xs font-medium text-yellow-700 dark:bg-yellow-500/10 dark:text-yellow-400">
                                        {{ __('recruiter.pending') }}
                                    </span>
                                @elseif($application->status->value === 'accepted')
                                    <span class="inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-700 dark:bg-green-500/10 dark:text-green-400">
                                        {{ __('recruiter.accepted') }}
                                    </span>
                                @elseif($application->status->value === 'rejected')
                                    <span class="inline-flex items-center rounded-full bg-red-100 px-3 py-1 text-xs font-medium text-red-700 dark:bg-red-500/10 dark:text-red-400">
                                        {{ __('recruiter.rejected') }}
                                    </span>
                                @endif
                            </div>

                            <div class="mb-4">
                                <p class="text-sm text-stone-600 dark:text-stone-400 mb-2">
                                    {{ __('recruiter.applied_time', ['time' => $application->created_at->diffForHumans()]) }}
                                </p>
                                @if($application->candidate->candidateProfile)
                                    <div class="text-sm text-stone-600 dark:text-stone-400">
                                        <p><strong>{{ __('recruiter.phone') }}</strong> {{ $application->candidate->phone ?? __('recruiter.not_provided') }}</p>
                                        @if($application->candidate->candidateProfile->resume_path)
                                            <p><strong>{{ __('recruiter.resume') }}</strong> 
                                                <a href="{{ localized_route('recruiter.applications.resume', $application) }}" 
                                                   class="text-amber-600 hover:text-amber-500 dark:text-amber-400 dark:hover:text-amber-300">
                                                    {{ __('recruiter.download_resume') }}
                                                </a>
                                            </p>
                                        @endif
                                    </div>
                                @endif
                            </div>

                            @if($application->cover_letter)
                                <div class="mb-4">
                                    <h4 class="text-sm font-medium text-stone-700 dark:text-stone-300 mb-2">{{ __('recruiter.cover_letter') }}</h4>
                                    <div class="rounded-lg bg-stone-50 p-4 text-sm text-stone-700 dark:bg-stone-800 dark:text-stone-300">
                                        {{ $application->cover_letter }}
                                    </div>
                                </div>
                            @endif

                            @if($application->notes)
                                <div class="mb-4">
                                    <h4 class="text-sm font-medium text-stone-700 dark:text-stone-300 mb-2">{{ __('recruiter.your_notes') }}</h4>
                                    <div class="rounded-lg bg-blue-50 p-4 text-sm text-stone-700 dark:bg-blue-500/10 dark:text-stone-300">
                                        {{ $application->notes }}
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="ml-6">
                            @php
                                $isDecisionMade = in_array($application->status->value, ['accepted', 'rejected']);
                            @endphp
                            
                            @if($isDecisionMade)
                                <div class="text-sm text-stone-600 dark:text-stone-400">
                                    <p class="font-medium mb-2">{{ __('recruiter.decision_made') }}</p>
                                    <p class="text-xs">{{ __('recruiter.decision_final_message', ['status' => __('recruiter.' . $application->status->value)]) }}</p>
                                </div>
                            @else
                                <form action="{{ localized_route('recruiter.applications.update', $application) }}" method="POST" class="space-y-2">
                                    @csrf
                                    @method('PATCH')
                                    
                                    <div class="flex gap-2">
                                        <select name="status" class="rounded-lg border border-stone-200 bg-white px-3 py-2 text-sm dark:border-stone-700 dark:bg-stone-800 dark:text-stone-300">
                                            <option value="pending" {{ $application->status->value === 'pending' ? 'selected' : '' }}>{{ __('recruiter.pending') }}</option>
                                            <option value="accepted">{{ __('recruiter.accepted') }}</option>
                                            <option value="rejected">{{ __('recruiter.rejected') }}</option>
                                        </select>
                                        <button 
                                            type="submit"
                                            class="inline-flex items-center justify-center rounded-lg bg-amber-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-amber-500"
                                        >
                                            {{ __('common.update') }}
                                        </button>
                                    </div>
                                    
                                    <textarea 
                                        name="notes" 
                                        placeholder="{{ __('recruiter.add_notes_placeholder') }}"
                                        class="w-full rounded-lg border border-stone-200 bg-white px-3 py-2 text-sm dark:border-stone-700 dark:bg-stone-800 dark:text-stone-300"
                                        rows="3"
                                    >{{ $application->notes }}</textarea>
                                    
                                    @error("notes")
                                        <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        @if($applications->hasPages())
            <div class="mt-6">
                {{ $applications->links() }}
            </div>
        @endif
    @endif
</div>
@endsection
