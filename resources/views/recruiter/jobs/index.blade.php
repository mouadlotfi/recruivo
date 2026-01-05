@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-stone-900 dark:text-white sm:text-3xl">{{ __('recruiter.my_job_listings') }}</h1>
            <p class="mt-2 text-sm text-stone-600 dark:text-stone-400 sm:text-base">{{ __('recruiter.my_job_listings_subtitle') }}</p>
        </div>
        <a 
            href="{{ localized_route('recruiter.jobs.create') }}" 
            class="inline-flex items-center justify-center rounded-2xl bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-amber-500/30 transition hover:bg-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200 sm:px-6 sm:py-3"
        >
            <svg class="mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            <span class="hidden sm:inline">{{ __('recruiter.post_new_job') }}</span>
            <span class="sm:hidden">{{ __('Post Job') }}</span>
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

    @if($jobs->isEmpty())
        <div class="rounded-xl border border-stone-200/60 bg-white/80 p-12 text-center backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/60">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-500/10">
                <svg class="h-8 w-8 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0M12 12.75h.008v.008H12v-.008z" />
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-stone-900 dark:text-white mb-2">{{ __('recruiter.no_job_listings') }}</h3>
            <p class="text-stone-600 dark:text-stone-400 mb-6">{{ __('recruiter.get_started_posting') }}</p>
            <a 
                href="{{ localized_route('recruiter.jobs.create') }}" 
                class="inline-flex items-center justify-center rounded-2xl bg-amber-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-amber-500/30 transition hover:bg-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200"
            >
                {{ __('recruiter.post_first_job') }}
            </a>
        </div>
    @else
        <div class="space-y-4">
            @foreach($jobs as $job)
                <div class="rounded-xl border border-stone-200/60 bg-white/80 p-4 backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/60 transition hover:border-amber-200 dark:hover:border-amber-800 sm:p-6">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <h3 class="text-xl font-semibold text-stone-900 dark:text-white">
                                    {{ $job->title }}
                                </h3>
                                @if($job->status->value === 'published')
                                    <span class="inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-700 dark:bg-green-500/10 dark:text-green-400">
                                        <svg class="mr-1 h-3 w-3" fill="currentColor" viewBox="0 0 8 8">
                                            <circle cx="4" cy="4" r="3" />
                                        </svg>
                                        {{ __('recruiter.published') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-stone-100 px-3 py-1 text-xs font-medium text-stone-700 dark:bg-stone-500/10 dark:text-stone-400">
                                        <svg class="mr-1 h-3 w-3" fill="currentColor" viewBox="0 0 8 8">
                                            <circle cx="4" cy="4" r="3" />
                                        </svg>
                                        {{ __('recruiter.draft') }}
                                    </span>
                                @endif
                            </div>

                            <div class="flex flex-wrap items-center gap-4 text-sm text-stone-600 dark:text-stone-400 mb-3">
                                @if($job->location)
                                    <div class="flex items-center gap-1">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                                        </svg>
                                        {{ $job->location }}
                                    </div>
                                @endif
                                @if($job->remote_type)
                                    <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">
                                        {{ __('recruiter.' . str_replace('-', '', strtolower($job->remote_type))) }}
                                    </span>
                                @endif
                                @if($job->category)
                                    <span class="rounded-full bg-teal-100 px-2.5 py-0.5 text-xs font-medium text-teal-700 dark:bg-teal-500/10 dark:text-teal-300">
                                        {{ $job->category }}
                                    </span>
                                @endif
                            </div>

                            <div class="flex items-center gap-6 text-sm">
                                <div class="flex items-center gap-2">
                                    <svg class="h-5 w-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                                    </svg>
                                    <span class="font-medium text-stone-900 dark:text-white">{{ $job->applications_count }}</span>
                                    <span class="text-stone-600 dark:text-stone-400">{{ trans_choice('recruiter.applications_count', $job->applications_count) }}</span>
                                </div>
                                <div class="text-stone-500 dark:text-stone-500">
                                    {{ __('recruiter.posted_time', ['time' => $job->created_at->diffForHumans()]) }}
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2 sm:ml-4">
                            <a 
                                href="{{ localized_route('recruiter.jobs.applications', $job) }}" 
                                class="inline-flex items-center justify-center rounded-lg bg-stone-100 px-3 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-200 dark:bg-stone-800 dark:text-stone-300 dark:hover:bg-stone-700"
                                title="{{ __('recruiter.view_applications_title') }}"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </a>

                            <a 
                                href="{{ localized_route('recruiter.jobs.edit', $job) }}" 
                                class="inline-flex items-center justify-center rounded-lg bg-amber-100 px-3 py-2 text-sm font-medium text-amber-700 transition hover:bg-amber-200 dark:bg-amber-500/10 dark:text-amber-400 dark:hover:bg-amber-500/20"
                                title="{{ __('recruiter.edit_job_title_attr') }}"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                </svg>
                            </a>

                            <form action="{{ localized_route('recruiter.jobs.toggle', $job) }}" method="POST" class="inline-block">
                                @csrf
                                <button 
                                    type="submit"
                                    class="inline-flex items-center justify-center rounded-lg {{ $job->status->value === 'published' ? 'bg-amber-100 text-amber-700 hover:bg-amber-200 dark:bg-amber-500/10 dark:text-amber-400 dark:hover:bg-amber-500/20' : 'bg-green-100 text-green-700 hover:bg-green-200 dark:bg-green-500/10 dark:text-green-400 dark:hover:bg-green-500/20' }} px-3 py-2 text-sm font-medium transition"
                                    title="{{ $job->status->value === 'published' ? __('recruiter.unpublish') : __('recruiter.publish') }}"
                                >
                                    @if($job->status->value === 'published')
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                                        </svg>
                                    @else
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    @endif
                                </button>
                            </form>

                            <div x-data="{ showModal: false }" class="inline-block">
                                <button 
                                    @click="showModal = true"
                                    type="button"
                                    class="inline-flex items-center justify-center rounded-lg bg-red-100 px-3 py-2 text-sm font-medium text-red-700 transition hover:bg-red-200 dark:bg-red-500/10 dark:text-red-400 dark:hover:bg-red-500/20"
                                    title="{{ __('recruiter.delete_job_title') }}"
                                >
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                    </svg>
                                </button>

                                <!-- Delete Job Modal -->
                                <template x-teleport="body">
                                    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
                                        <div class="flex min-h-screen items-center justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                                            <!-- Background overlay -->
                                            <div x-show="showModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-stone-900/75 backdrop-blur-sm transition-opacity" @click="showModal = false"></div>

                                            <!-- Center modal -->
                                            <span class="hidden sm:inline-block sm:h-screen sm:align-middle">&#8203;</span>

                                            <!-- Modal panel -->
                                            <div x-show="showModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block transform overflow-hidden rounded-2xl border border-stone-200/60 bg-white/95 text-left align-bottom shadow-2xl backdrop-blur transition-all dark:border-stone-700/60 dark:bg-stone-900/95 sm:my-8 sm:w-full sm:max-w-lg sm:align-middle">
                                                <div class="p-6 sm:p-8">
                                                    <div class="flex items-start">
                                                        <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/20">
                                                            <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                                            </svg>
                                                        </div>
                                                    </div>
                                                    <div class="mt-4 text-center">
                                                        <h3 class="text-xl font-semibold text-stone-900 dark:text-white">
                                                            {{ __('recruiter.delete_job_title') }}
                                                        </h3>
                                                        <div class="mt-3">
                                                            <p class="text-sm text-stone-600 dark:text-stone-400">
                                                                {{ __('recruiter.delete_job_confirm') }}
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="bg-stone-50/80 px-6 py-4 dark:bg-stone-800/40 sm:flex sm:flex-row-reverse sm:px-8">
                                                    <form method="POST" action="{{ localized_route('recruiter.jobs.destroy', $job) }}" class="w-full sm:w-auto">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="inline-flex w-full justify-center rounded-2xl bg-red-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-red-500/30 transition hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-stone-900 sm:w-auto">
                                                            {{ __('recruiter.delete_job') }}
                                                        </button>
                                                    </form>
                                                    <button @click="showModal = false" type="button" class="mt-3 inline-flex w-full justify-center rounded-2xl border border-stone-200/80 bg-white px-6 py-3 text-sm font-semibold text-stone-700 shadow-sm transition hover:bg-stone-50 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:border-stone-700 dark:bg-stone-800/80 dark:text-stone-200 dark:hover:bg-stone-700 dark:focus:ring-offset-stone-900 sm:mr-3 sm:mt-0 sm:w-auto">
                                                        {{ __('common.cancel') }}
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        @if($jobs->hasPages())
            <div class="mt-6">
                {{ $jobs->links() }}
            </div>
        @endif
    @endif
</div>
@endsection

