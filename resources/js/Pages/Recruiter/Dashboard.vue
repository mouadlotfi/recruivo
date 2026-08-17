<script setup lang="ts">
import { computed } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import type { PageProps, RecruiterDashboardApplication } from '../../types'
import AppLayout from '../../Layouts/AppLayout.vue'

const props = defineProps<{
    stats: {
        totalJobs: number
        activeJobs: number
        totalApplications: number
        pendingApplications: number
    }
    recentApplications: RecruiterDashboardApplication[]
    labels: Record<string, string>
}>()

const page = usePage<PageProps>()
const locale = computed(() => page.props.locale)
const localeUrl = (path: string) => `/${locale.value}${path}`

const statusClasses: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-500/10 dark:text-yellow-400',
    shortlisted: 'bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400',
    interview: 'bg-violet-100 text-violet-700 dark:bg-violet-500/10 dark:text-violet-400',
    accepted: 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400',
    rejected: 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-400',
}

const statusClass = (status: string) => statusClasses[status] ?? 'bg-stone-100 text-stone-700'
const applicationsUrl = (application: RecruiterDashboardApplication) =>
    localeUrl(`/recruiter/jobs/${application.job.id}/applications`)
</script>

<template>
    <Head :title="labels.dashboard" />

    <AppLayout>
        <div class="space-y-8">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-stone-900 dark:text-white sm:text-3xl">{{ labels.dashboard }}</h1>
                    <p class="mt-2 text-sm text-stone-600 dark:text-stone-400 sm:text-base">{{ labels.dashboard_subtitle }}</p>
                </div>
                <Link
                    :href="localeUrl('/recruiter/jobs/create')"
                    class="inline-flex items-center justify-center rounded-2xl bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-amber-500/30 transition hover:bg-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200 sm:px-6 sm:py-3"
                >
                    <svg class="mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    <span>{{ labels.post_new_job }}</span>
                </Link>
            </div>

            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-xl border border-stone-200/60 bg-white/80 p-6 backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/60">
                    <div class="flex items-center">
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-500/10">
                            <svg class="h-6 w-6 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0M12 12.75h.008v.008H12v-.008z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-stone-600 dark:text-stone-400">{{ labels.total_jobs }}</p>
                            <p class="text-2xl font-semibold text-stone-900 dark:text-white">{{ stats.totalJobs }}</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-stone-200/60 bg-white/80 p-6 backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/60">
                    <div class="flex items-center">
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-green-100 dark:bg-green-500/10">
                            <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-stone-600 dark:text-stone-400">{{ labels.active_jobs }}</p>
                            <p class="text-2xl font-semibold text-stone-900 dark:text-white">{{ stats.activeJobs }}</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-stone-200/60 bg-white/80 p-6 backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/60">
                    <div class="flex items-center">
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-500/10">
                            <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-stone-600 dark:text-stone-400">{{ labels.total_applications }}</p>
                            <p class="text-2xl font-semibold text-stone-900 dark:text-white">{{ stats.totalApplications }}</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-stone-200/60 bg-white/80 p-6 backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/60">
                    <div class="flex items-center">
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-yellow-100 dark:bg-yellow-500/10">
                            <svg class="h-6 w-6 text-yellow-600 dark:text-yellow-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-stone-600 dark:text-stone-400">{{ labels.pending_applications }}</p>
                            <p class="text-2xl font-semibold text-stone-900 dark:text-white">{{ stats.pendingApplications }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-stone-200/60 bg-white/80 p-8 backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/60">
                <div class="mb-6 flex items-center justify-between">
                    <h2 class="text-xl font-semibold text-stone-900 dark:text-white">{{ labels.recent_applications }}</h2>
                    <Link
                        :href="localeUrl('/recruiter/jobs')"
                        class="text-sm font-medium text-amber-600 hover:text-amber-500 dark:text-amber-400 dark:hover:text-amber-300"
                    >
                        {{ labels.view_all_jobs }}
                    </Link>
                </div>

                <div v-if="recentApplications.length === 0" class="py-8 text-center">
                    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-stone-100 dark:bg-stone-800">
                        <svg class="h-8 w-8 text-stone-600 dark:text-stone-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                        </svg>
                    </div>
                    <h3 class="mb-2 text-lg font-semibold text-stone-900 dark:text-white">{{ labels.no_applications_yet }}</h3>
                    <p class="text-stone-600 dark:text-stone-400">{{ labels.applications_will_appear }}</p>
                </div>

                <div v-else class="space-y-4">
                    <div
                        v-for="application in recentApplications"
                        :key="application.id"
                        class="flex flex-col gap-3 rounded-lg border border-stone-200 p-4 dark:border-stone-700 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <Link
                            :href="applicationsUrl(application)"
                            data-recent-applicant-link
                            class="group flex min-w-0 items-center gap-4 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-2 dark:focus:ring-offset-stone-900"
                        >
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-100 text-sm font-semibold text-amber-700 transition group-hover:bg-amber-200 dark:bg-amber-500/10 dark:text-amber-400 dark:group-hover:bg-amber-500/20">
                                {{ application.candidate.initial }}
                            </span>
                            <span class="min-w-0">
                                <span class="block truncate font-medium text-stone-900 transition group-hover:text-amber-600 dark:text-white dark:group-hover:text-amber-400">{{ application.candidate.name }}</span>
                                <p class="truncate text-sm text-stone-600 dark:text-stone-400">{{ application.job.title }}</p>
                            </span>
                        </Link>
                        <div class="flex shrink-0 flex-wrap items-center gap-x-3 gap-y-1">
                            <span
                                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                :class="statusClass(application.status)"
                            >
                                {{ application.status_label }}
                            </span>
                            <span class="whitespace-nowrap text-sm text-stone-500 dark:text-stone-500">
                                {{ application.created_at_label }}
                            </span>
                            <Link
                                :href="applicationsUrl(application)"
                                class="inline-flex items-center justify-center rounded-lg bg-amber-100 px-3 py-1.5 text-xs font-medium text-amber-700 transition hover:bg-amber-200 dark:bg-amber-500/10 dark:text-amber-400 dark:hover:bg-amber-500/20"
                            >
                                {{ labels.view }}
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <div class="rounded-xl border border-stone-200/60 bg-white/80 p-6 backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/60">
                    <h3 class="mb-4 text-lg font-semibold text-stone-900 dark:text-white">{{ labels.quick_actions }}</h3>
                    <div class="space-y-3">
                        <Link
                            :href="localeUrl('/recruiter/jobs/create')"
                            class="flex items-center gap-3 rounded-lg bg-amber-50 p-3 text-sm font-medium text-amber-700 transition hover:bg-amber-100 dark:bg-amber-500/10 dark:text-amber-400 dark:hover:bg-amber-500/20"
                        >
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            {{ labels.post_new_job }}
                        </Link>
                        <Link
                            :href="localeUrl('/recruiter/jobs')"
                            class="flex items-center gap-3 rounded-lg bg-stone-50 p-3 text-sm font-medium text-stone-700 transition hover:bg-stone-100 dark:bg-stone-800 dark:text-stone-300 dark:hover:bg-stone-700"
                        >
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0M12 12.75h.008v.008H12v-.008z" />
                            </svg>
                            {{ labels.manage_jobs }}
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
