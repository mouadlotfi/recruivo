<script setup lang="ts">
import { computed, ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import type { PageProps, AppNotification } from '../../types'
import { useTranslation } from '../../composables/useTranslation'
import { useDismiss } from '../../composables/useDismiss'

const page = usePage<PageProps>()
const { t } = useTranslation()

const open = ref(false)
const root = ref<HTMLElement | null>(null)
useDismiss(open, root)

const unreadCount = computed(() => Number(page.props.notificationCount ?? 0))
const notifications = computed<AppNotification[]>(() => (page.props.notifications as AppNotification[]) ?? [])
const badge = computed(() => (unreadCount.value > 99 ? '99+' : String(unreadCount.value)))

const markAllReadUrl = `/${page.props.locale}/notifications/read-all`

function markAllRead(): void {
    router.post(markAllReadUrl, {}, {
        only: ['notificationCount', 'notifications'],
        preserveState: true,
        preserveScroll: true,
        showProgress: false,
    })
}

function openNotification(notificationId: string): void {
    open.value = false
    router.post(`/${page.props.locale}/notifications/${notificationId}`)
}

function notificationTitle(item: AppNotification): string {
    if (item.data.kind === 'new_application') {
        return t('new_application')
    }
    const status = item.data.status
    if (status === 'accepted') return t('application_accepted')
    if (status === 'rejected') return t('application_rejected')
    if (status === 'shortlisted') return t('application_shortlisted')
    if (status === 'interview') return t('application_interview')
    return t('notifications')
}

function notificationMessage(item: AppNotification): string {
    if (item.data.kind === 'new_application') {
        return t('new_application_message', {
            candidate: item.data.candidate_name ?? t('candidate'),
            job: item.data.job_title ?? '',
        })
    }
    return t('application_status_message', {
        company: item.data.company_name ?? 'Company',
        job: item.data.job_title ?? '',
    })
}
</script>

<template>
    <div ref="root" class="relative" data-notification-center>
        <button
            type="button"
            :aria-label="unreadCount > 0 ? t('unread_notifications', { count: unreadCount }) : t('notifications')"
            aria-haspopup="dialog"
            :aria-expanded="open"
            class="relative inline-flex h-9 w-9 items-center justify-center rounded-full text-stone-600 transition hover:bg-stone-100 hover:text-stone-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 dark:text-stone-300 dark:hover:bg-stone-800 dark:hover:text-stone-100 dark:focus-visible:ring-offset-stone-950"
            @click="open = !open"
        >
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
            </svg>
            <span
                v-if="unreadCount > 0"
                class="absolute -right-1 -top-1 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-amber-600 px-1.5 text-[11px] font-bold leading-none text-white ring-2 ring-white dark:ring-stone-950"
                aria-hidden="true"
            >
                {{ badge }}
            </span>
        </button>

        <div
            v-if="open"
            role="dialog"
            :aria-label="t('notifications')"
            class="fixed inset-x-3 top-16 z-[10020] max-h-[min(32rem,calc(100vh-6rem))] overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-2xl sm:absolute sm:inset-x-auto sm:right-0 sm:top-full sm:mt-2 sm:w-[24rem] dark:border-stone-700 dark:bg-stone-900"
        >
            <div class="flex items-center justify-between gap-3 border-b border-stone-200 px-4 py-3 dark:border-stone-700">
                <div class="min-w-0">
                    <h2 class="font-semibold text-stone-900 dark:text-white">{{ t('notifications') }}</h2>
                    <p v-if="unreadCount > 0" class="text-xs text-stone-500 dark:text-stone-400">
                        {{ t('unread_notifications', { count: unreadCount }) }}
                    </p>
                </div>
                <div v-if="unreadCount > 0">
                    <button
                        type="button"
                        class="min-h-11 whitespace-nowrap rounded-lg px-2 text-xs font-semibold text-amber-700 transition hover:bg-amber-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 dark:text-amber-300 dark:hover:bg-amber-500/10"
                        @click="markAllRead"
                    >
                        {{ t('mark_all_as_read') }}
                    </button>
                </div>
            </div>
            <div class="max-h-[min(26rem,calc(100vh-11rem))] divide-y divide-stone-100 overflow-y-auto dark:divide-stone-800">
                <div v-if="notifications.length === 0" class="px-6 py-10 text-center">
                    <svg class="mx-auto h-8 w-8 text-stone-300 dark:text-stone-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                    </svg>
                    <p class="mt-3 text-sm font-semibold text-stone-800 dark:text-stone-100">{{ t('no_notifications') }}</p>
                    <p class="mt-1 text-xs text-stone-500 dark:text-stone-400">{{ t('no_notifications_description') }}</p>
                </div>
                <button
                    v-for="item in notifications"
                    :key="item.id"
                    type="button"
                    class="group flex w-full items-start gap-3 p-3.5 text-left transition hover:bg-amber-500/5 focus-visible:bg-amber-500/5 focus-visible:outline-none dark:hover:bg-amber-500/10 dark:focus-visible:bg-amber-500/10"
                    @click="openNotification(item.id)"
                >
                    <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-amber-500/10 text-amber-600 ring-1 ring-inset ring-amber-500/20 dark:bg-amber-400/10 dark:text-amber-400 dark:ring-amber-400/30">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-2">
                            <p class="truncate text-xs font-semibold text-stone-900 group-hover:text-amber-700 dark:text-white dark:group-hover:text-amber-400">
                                {{ notificationTitle(item) }}
                            </p>
                            <span v-if="item.created_at" class="shrink-0 text-[10px] text-stone-400 dark:text-stone-500">
                                {{ item.created_at }}
                            </span>
                        </div>
                        <p class="mt-0.5 text-xs text-stone-600 line-clamp-2 dark:text-stone-300">
                            {{ notificationMessage(item) }}
                        </p>
                    </div>
                    <span v-if="!item.read_at" class="mt-2 h-2 w-2 shrink-0 rounded-full bg-amber-500 ring-2 ring-white dark:ring-stone-900" aria-hidden="true" />
                </button>
            </div>
        </div>
    </div>
</template>
