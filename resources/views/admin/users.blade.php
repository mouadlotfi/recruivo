@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-stone-900 dark:text-white sm:text-3xl">{{ __('admin.user_management_title') }}</h1>
            <p class="mt-2 text-sm text-stone-600 dark:text-stone-400 sm:text-base">{{ __('admin.registered_users_count', ['count' => $users->total()]) }}</p>
        </div>
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

    <!-- Search and Filter Form -->
    <div class="rounded-xl border border-stone-200/60 bg-white/80 p-3 backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/60 sm:p-4">
        <form method="GET" action="{{ localized_route('admin.users') }}" class="flex flex-col gap-3 md:flex-row">
            <div class="flex-1">
                <input 
                    type="text" 
                    name="search" 
                    placeholder="{{ __('admin.search_placeholder') }}" 
                    value="{{ request('search') }}"
                    class="w-full rounded-lg border border-stone-200 bg-white px-3 py-2 text-sm dark:border-stone-700 dark:bg-stone-800 dark:text-stone-300 focus:border-amber-500 focus:ring-2 focus:ring-amber-200 dark:focus:border-amber-400 dark:focus:ring-amber-500/20 sm:px-4"
                >
            </div>
            <div>
                <select 
                    name="role" 
                    class="w-full rounded-lg border border-stone-200 bg-white px-3 py-2 text-sm dark:border-stone-700 dark:bg-stone-800 dark:text-stone-300 focus:border-amber-500 focus:ring-2 focus:ring-amber-200 dark:focus:border-amber-400 dark:focus:ring-amber-500/20 sm:px-4 md:w-auto"
                >
                    <option value="">{{ __('admin.all_roles') }}</option>
                    <option value="Candidate" {{ request('role') == 'Candidate' ? 'selected' : '' }}>{{ __('admin.candidate') }}</option>
                    <option value="Recruiter" {{ request('role') == 'Recruiter' ? 'selected' : '' }}>{{ __('admin.recruiter') }}</option>
                    <option value="Admin" {{ request('role') == 'Admin' ? 'selected' : '' }}>{{ __('admin.admin') }}</option>
                </select>
            </div>
            <div class="flex gap-2">
                <button 
                    type="submit"
                    class="inline-flex flex-1 items-center justify-center rounded-lg bg-amber-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-amber-500 sm:flex-none sm:px-6"
                >
                    <svg class="h-4 w-4 sm:mr-2" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                    </svg>
                    <span class="hidden sm:inline">{{ __('admin.search_button') }}</span>
                </button>
                @if(request('search') || request('role'))
                    <a 
                        href="{{ localized_route('admin.users') }}"
                        class="inline-flex flex-1 items-center justify-center rounded-lg bg-stone-100 px-4 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-200 dark:bg-stone-800 dark:text-stone-300 dark:hover:bg-stone-700 sm:flex-none"
                    >
                        <span class="hidden sm:inline">{{ __('admin.clear_button') }}</span>
                        <span class="sm:hidden">{{ __('Clear') }}</span>
                    </a>
                @endif
            </div>
        </form>
    </div>

    @if($users->isEmpty())
        <div class="rounded-xl border border-stone-200/60 bg-white/80 p-12 text-center backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/60">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-stone-100 dark:bg-stone-800">
                <svg class="h-8 w-8 text-stone-600 dark:text-stone-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-stone-900 dark:text-white mb-2">{{ __('admin.no_users_found') }}</h3>
            <p class="text-stone-600 dark:text-stone-400">
                @if(request('search'))
                    {{ __('admin.no_users_match_criteria') }}
                @else
                    {{ __('admin.no_users_registered') }}
                @endif
            </p>
        </div>
    @else
        <div class="space-y-4">
            @foreach($users as $user)
                <div class="rounded-xl border border-stone-200/60 bg-white/80 p-4 backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/60 transition hover:border-amber-200 dark:hover:border-amber-800 sm:p-6">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-amber-100 text-lg font-semibold text-amber-700 dark:bg-amber-500/10 dark:text-amber-400">
                                    {{ substr($user->name, 0, 1) }}
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-stone-900 dark:text-white">
                                        {{ $user->name }}
                                    </h3>
                                    <p class="text-sm text-stone-600 dark:text-stone-400">{{ $user->email }}</p>
                                </div>
                                @if($user->hasRole('Admin'))
                                    <span class="inline-flex items-center rounded-full bg-teal-100 px-3 py-1 text-xs font-medium text-teal-700 dark:bg-teal-500/10 dark:text-teal-400">
                                        <svg class="mr-1 h-3 w-3" fill="currentColor" viewBox="0 0 8 8">
                                            <circle cx="4" cy="4" r="3" />
                                        </svg>
                                        {{ __('admin.admin') }}
                                    </span>
                                @elseif($user->hasRole('Recruiter'))
                                    <span class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-xs font-medium text-blue-700 dark:bg-blue-500/10 dark:text-blue-400">
                                        <svg class="mr-1 h-3 w-3" fill="currentColor" viewBox="0 0 8 8">
                                            <circle cx="4" cy="4" r="3" />
                                        </svg>
                                        {{ __('admin.recruiter') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-700 dark:bg-green-500/10 dark:text-green-400">
                                        <svg class="mr-1 h-3 w-3" fill="currentColor" viewBox="0 0 8 8">
                                            <circle cx="4" cy="4" r="3" />
                                        </svg>
                                        {{ __('admin.candidate') }}
                                    </span>
                                @endif
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                                <div>
                                    <span class="text-stone-500 dark:text-stone-500">{{ __('admin.phone') }}</span>
                                    <span class="ml-2 text-stone-700 dark:text-stone-300">{{ $user->phone ?? __('admin.not_provided') }}</span>
                                </div>
                                
                                @if($user->company)
                                    <div>
                                        <span class="text-stone-500 dark:text-stone-500">{{ __('admin.company') }}</span>
                                        <span class="ml-2 text-stone-700 dark:text-stone-300">{{ $user->company->name }}</span>
                                    </div>
                                @endif

                                <div>
                                    <span class="text-stone-500 dark:text-stone-500">{{ __('admin.applications') }}</span>
                                    <span class="ml-2 text-stone-700 dark:text-stone-300">{{ $user->applications_count }}</span>
                                </div>

                                @if(!$user->hasRole('Admin'))
                                    <div>
                                        <span class="text-stone-500 dark:text-stone-500">{{ __('admin.email_verified') }}</span>
                                        <span class="ml-2">
                                            @if($user->email_verified_at)
                                                <span class="inline-flex items-center text-green-600 dark:text-green-400">
                                                    <svg class="mr-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    {{ __('admin.yes') }}
                                                </span>
                                            @else
                                                <span class="inline-flex items-center text-amber-600 dark:text-amber-400">
                                                    <svg class="mr-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                                    </svg>
                                                    {{ __('admin.no') }}
                                                </span>
                                            @endif
                                        </span>
                                    </div>
                                @endif

                                <div>
                                    <span class="text-stone-500 dark:text-stone-500">{{ __('admin.joined') }}</span>
                                    <span class="ml-2 text-stone-700 dark:text-stone-300">{{ $user->created_at->format('M d, Y') }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="sm:ml-6">
                            @if(!$user->hasRole('Admin'))
                                <div x-data="{ showModal: false }">
                                    <button 
                                        @click="showModal = true"
                                        type="button"
                                        class="inline-flex items-center justify-center rounded-lg bg-red-100 px-4 py-2 text-sm font-medium text-red-700 transition hover:bg-red-200 dark:bg-red-500/10 dark:text-red-400 dark:hover:bg-red-500/20"
                                        title="{{ __('admin.delete_user') }}"
                                    >
                                        <svg class="mr-1.5 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                        </svg>
                                        {{ __('admin.delete_user') }}
                                    </button>

                                    <!-- Delete User Modal -->
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
                                                                {{ __('admin.delete_user') }}
                                                            </h3>
                                                            <div class="mt-3">
                                                                <p class="text-sm text-stone-600 dark:text-stone-400">
                                                                    {{ __('admin.delete_user_confirmation') }}
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="bg-stone-50/80 px-6 py-4 dark:bg-stone-800/40 sm:flex sm:flex-row-reverse sm:px-8">
                                                        <form method="POST" action="{{ localized_route('admin.users.destroy', $user) }}" class="w-full sm:w-auto">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="inline-flex w-full justify-center rounded-2xl bg-red-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-red-500/30 transition hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-stone-900 sm:w-auto">
                                                                {{ __('admin.delete_user') }}
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
                            @else
                                <div class="text-sm text-stone-500 dark:text-stone-500 italic">
                                    {{ __('admin.protected') }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        @if($users->hasPages())
            <div class="mt-6">
                <x-pagination :paginator="$users" />
            </div>
        @endif
    @endif
</div>
@endsection

