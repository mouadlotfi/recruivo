@php
    $navbarNotifications = $user->notifications()->latest()->limit(8)->get();
    $navbarUnreadCount = $user->unreadNotifications()->count();
@endphp

<div
    class="relative"
    x-data="{ open: false }"
    data-notification-center
    data-notification-unread-count="{{ $navbarUnreadCount }}"
>
    <button
        type="button"
        @click="open = !open"
        @click.away="open = false"
        @keydown.escape.window="open = false"
        :aria-expanded="open.toString()"
        aria-haspopup="menu"
        aria-label="{{ $navbarUnreadCount > 0 ? __('common.unread_notifications', ['count' => $navbarUnreadCount]) : __('common.notification_center') }}"
        class="relative inline-flex h-9 w-9 items-center justify-center rounded-full text-stone-600 transition hover:bg-stone-100 hover:text-stone-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 dark:text-stone-300 dark:hover:bg-stone-800 dark:hover:text-stone-100 dark:focus-visible:ring-offset-stone-950"
    >
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
        </svg>
        @if($navbarUnreadCount > 0)
            <span class="absolute -right-1 -top-1 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-amber-600 px-1.5 text-[11px] font-bold leading-none text-white ring-2 ring-white dark:ring-stone-950" aria-hidden="true">
                {{ $navbarUnreadCount > 99 ? '99+' : $navbarUnreadCount }}
            </span>
        @endif
    </button>

    <div
        x-show="open"
        x-transition
        style="display: none;"
        role="menu"
        aria-label="{{ __('common.notifications') }}"
        class="fixed inset-x-3 top-16 z-[10020] max-h-[min(32rem,calc(100vh-6rem))] overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-2xl sm:absolute sm:inset-x-auto sm:right-0 sm:top-full sm:mt-2 sm:w-[24rem] dark:border-stone-700 dark:bg-stone-900"
    >
        <div class="flex items-center justify-between gap-3 border-b border-stone-200 px-4 py-3 dark:border-stone-700">
            <div class="min-w-0">
                <h2 class="font-semibold text-stone-900 dark:text-white">{{ __('common.notifications') }}</h2>
                @if($navbarUnreadCount > 0)
                    <p class="text-xs text-stone-500 dark:text-stone-400">{{ __('common.unread_notifications', ['count' => $navbarUnreadCount]) }}</p>
                @endif
            </div>
            @if($navbarUnreadCount > 0)
                <form method="POST" action="{{ localized_route('notifications.read-all') }}">
                    @csrf
                    <button type="submit" class="min-h-11 whitespace-nowrap rounded-lg px-2 text-xs font-semibold text-amber-700 transition hover:bg-amber-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 dark:text-amber-300 dark:hover:bg-amber-500/10">
                        {{ __('common.mark_all_as_read') }}
                    </button>
                </form>
            @endif
        </div>

        <div class="max-h-[min(26rem,calc(100vh-11rem))] overflow-y-auto">
            @forelse($navbarNotifications as $notification)
                @php
                    $data = $notification->data;
                    $kind = $data['kind'] ?? null;
                    $isUnread = $notification->read_at === null;
                    $statusValue = $data['status'] ?? null;
                    $title = match ($kind) {
                        'new_application' => __('common.new_application'),
                        'application_status_updated' => match ($statusValue) {
                            'accepted' => __('common.application_accepted'),
                            'rejected' => __('common.application_rejected'),
                            'shortlisted' => __('common.application_shortlisted'),
                            'interview' => __('common.application_interview'),
                            default => __('common.notifications'),
                        },
                        default => __('common.notifications'),
                    };
                    $message = match ($kind) {
                        'new_application' => __('common.new_application_message', [
                            'candidate' => $data['candidate_name'] ?? __('common.candidate'),
                            'job' => $data['job_title'] ?? __('common.jobs'),
                        ]),
                        'application_status_updated' => __('common.application_status_message', [
                            'company' => $data['company_name'] ?? __('common.companies'),
                            'job' => $data['job_title'] ?? __('common.jobs'),
                        ]),
                        default => __('common.no_notifications_description'),
                    };
                @endphp
                <form method="POST" action="{{ localized_route('notifications.open', ['notification' => $notification->id]) }}" class="border-b border-stone-100 last:border-b-0 dark:border-stone-800">
                    @csrf
                    <button type="submit" role="menuitem" class="flex min-h-20 w-full items-start gap-3 px-4 py-3 text-left transition hover:bg-stone-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-amber-500 dark:hover:bg-stone-800/70 {{ $isUnread ? 'bg-amber-50/70 dark:bg-amber-500/5' : '' }}">
                        <span class="mt-1 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full {{ $kind === 'new_application' ? 'bg-teal-100 text-teal-700 dark:bg-teal-500/10 dark:text-teal-300' : ($statusValue === 'accepted' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' : ($statusValue === 'rejected' ? 'bg-rose-100 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300')) }}">
                            @if($kind === 'new_application')
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.941 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" /></svg>
                            @else
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75l2.25 2.25L15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            @endif
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="flex items-center gap-2">
                                <span class="truncate text-sm font-semibold text-stone-900 dark:text-white">{{ $title }}</span>
                                @if($isUnread)
                                    <span class="h-2 w-2 shrink-0 rounded-full bg-amber-500" aria-label="{{ __('common.unread') }}"></span>
                                @endif
                            </span>
                            <span class="mt-0.5 block text-sm leading-5 text-stone-600 dark:text-stone-300">{{ $message }}</span>
                            <span class="mt-1 block text-xs text-stone-400 dark:text-stone-500">{{ $notification->created_at->diffForHumans() }}</span>
                        </span>
                    </button>
                </form>
            @empty
                <div class="px-6 py-10 text-center">
                    <svg class="mx-auto h-8 w-8 text-stone-300 dark:text-stone-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" /></svg>
                    <p class="mt-3 text-sm font-semibold text-stone-800 dark:text-stone-100">{{ __('common.no_notifications') }}</p>
                    <p class="mt-1 text-xs text-stone-500 dark:text-stone-400">{{ __('common.no_notifications_description') }}</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
