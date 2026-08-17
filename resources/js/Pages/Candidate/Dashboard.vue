<script setup lang="ts">
import { computed } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import type { CandidateDashboardApplication, PageProps, ProfileCompletion } from '../../types'

const props = defineProps<{
    totalApplications: number
    inProgressApplications: number
    acceptedApplications: number
    rejectedApplications: number
    recentApplications: CandidateDashboardApplication[]
    profileCompletion: ProfileCompletion
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

const profileCompletionLink = (item: string) =>
    `${localeUrl('/profile')}#${item === 'resume' ? 'resume-section' : item}`

const statusClass = (status: string) => statusClasses[status] ?? 'bg-stone-100 text-stone-700'
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
                    :href="localeUrl('/jobs')"
                    class="inline-flex items-center justify-center rounded-2xl bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-amber-500/30 transition hover:bg-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200 sm:px-6 sm:py-3"
                >
                    <svg class="mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                    </svg>
                    <span class="hidden sm:inline">{{ labels.browse_jobs }}</span>
                    <span class="sm:hidden">{{ labels.browse }}</span>
                </Link>
            </div>

            <!-- Stats Grid -->
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-xl border border-stone-200/60 bg-white/80 p-6 backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/60">
                    <div class="flex items-center">
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-500/10">
                            <svg class="h-6 w-6 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-stone-600 dark:text-stone-400">{{ labels.total_applications }}</p>
                            <p class="text-2xl font-semibold text-stone-900 dark:text-white">{{ totalApplications }}</p>
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
                            <p class="text-sm font-medium text-stone-600 dark:text-stone-400">{{ labels.in_progress }}</p>
                            <p class="text-2xl font-semibold text-stone-900 dark:text-white">{{ inProgressApplications }}</p>
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
                            <p class="text-sm font-medium text-stone-600 dark:text-stone-400">{{ labels.accepted }}</p>
                            <p class="text-2xl font-semibold text-stone-900 dark:text-white">{{ acceptedApplications }}</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-stone-200/60 bg-white/80 p-6 backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/60">
                    <div class="flex items-center">
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-red-100 dark:bg-red-500/10">
                            <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-stone-600 dark:text-stone-400">{{ labels.rejected }}</p>
                            <p class="text-2xl font-semibold text-stone-900 dark:text-white">{{ rejectedApplications }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Profile Completion (only while incomplete) -->
            <div v-if="profileCompletion.percentage < 100" data-profile-completion class="rounded-xl border border-stone-200/60 bg-white/80 p-6 backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/60">
                <div class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-stone-900 dark:text-white">{{ labels.profile_completion }}</h2>
                        <p class="text-sm text-stone-600 dark:text-stone-400">{{ labels.profile_completion_help }}</p>
                    </div>
                    <Link :href="localeUrl('/profile')" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-amber-300 px-4 py-2 text-sm font-semibold text-amber-700 transition hover:bg-amber-50 focus:outline-none focus:ring-2 focus:ring-amber-400 dark:border-amber-500/40 dark:text-amber-300 dark:hover:bg-amber-500/10">
                        {{ labels.complete_profile }}
                    </Link>
                </div>

                <div class="mb-3 flex items-center gap-3">
                    <span class="text-2xl font-bold text-stone-900 dark:text-white">{{ profileCompletion.percentage }}%</span>
                    <div class="flex-1" role="progressbar" :aria-valuenow="profileCompletion.percentage" aria-valuemin="0" aria-valuemax="100" :aria-label="labels.profile_completion">
                        <div class="h-2.5 overflow-hidden rounded-full bg-stone-200 dark:bg-stone-700">
                            <div class="h-full rounded-full bg-gradient-to-r from-amber-400 to-amber-600 transition-all duration-500" :style="{ width: `${profileCompletion.percentage}%` }"></div>
                        </div>
                    </div>
                </div>

                <ul v-if="profileCompletion.missing.length" class="space-y-1.5">
                    <li v-for="item in profileCompletion.missing" :key="item" class="flex items-center gap-2 text-sm">
                        <svg class="h-4 w-4 shrink-0 text-amber-500" fill="currentColor" viewBox="0 0 8 8" aria-hidden="true">
                            <circle cx="4" cy="4" r="3" />
                        </svg>
                        <Link :href="profileCompletionLink(item)" class="text-stone-700 underline decoration-stone-300 underline-offset-2 hover:text-amber-700 dark:text-stone-300 dark:hover:text-amber-400">
                            {{ labels[`completion_${item}`] }}
                        </Link>
                    </li>
                </ul>
                <p v-else class="text-sm font-medium text-green-700 dark:text-green-400">{{ labels.profile_complete }}</p>
            </div>

            <!-- Recent Applications -->
            <div class="rounded-xl border border-stone-200/60 bg-white/80 p-4 backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/60 sm:p-8">
                <div data-recent-applications-header class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <h2 class="text-lg font-semibold text-stone-900 dark:text-white sm:text-xl">{{ labels.recent_applications }}</h2>
                    <Link
                        :href="localeUrl('/candidate/applications')"
                        class="text-sm font-medium text-amber-600 hover:text-amber-500 dark:text-amber-400 dark:hover:text-amber-300"
                    >
                        {{ labels.view_all_applications }}
                    </Link>
                </div>

                <div v-if="recentApplications.length === 0" class="py-8 text-center">
                    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-stone-100 dark:bg-stone-800">
                        <svg class="h-8 w-8 text-stone-600 dark:text-stone-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
                        </svg>
                    </div>
                    <h3 class="mb-2 text-lg font-semibold text-stone-900 dark:text-white">{{ labels.no_applications_yet }}</h3>
                    <p class="text-stone-600 dark:text-stone-400">{{ labels.start_applying }}</p>
                </div>

                <div v-else class="space-y-4">
                    <div v-for="application in recentApplications" :key="application.id" data-recent-application class="flex flex-col gap-3 rounded-lg border border-stone-200 p-3 dark:border-stone-700 sm:flex-row sm:items-center sm:justify-between sm:p-4">
                        <div class="flex min-w-0 flex-1 items-start gap-3 sm:items-center sm:gap-4">
                            <div v-if="application.job.company" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-100 text-sm font-semibold text-amber-700 dark:bg-amber-500/10 dark:text-amber-400">
                                {{ application.job.company.name.charAt(0) }}
                            </div>
                            <div v-else class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-100 text-sm font-semibold text-amber-700 dark:bg-amber-500/10 dark:text-amber-400">J</div>
                            <div class="min-w-0 flex-1">
                                <h3 class="break-words font-medium text-stone-900 dark:text-white">{{ application.job.title }}</h3>
                                <p v-if="application.job.company" class="break-words text-sm text-stone-600 dark:text-stone-400">{{ application.job.company.name }}</p>
                            </div>
                        </div>
                        <div class="flex w-full flex-wrap items-center gap-x-3 gap-y-2 pl-14 sm:w-auto sm:shrink-0 sm:pl-0">
                            <span :class="['inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium', statusClass(application.status)]">
                                {{ application.status_label }}
                            </span>
                            <span class="text-xs text-stone-500 dark:text-stone-500 sm:text-sm">
                                {{ application.applied_label }}
                            </span>
                            <Link
                                :href="localeUrl(`/jobs/${application.job.id}`)"
                                class="inline-flex min-h-11 items-center justify-center rounded-lg bg-amber-100 px-3 py-1.5 text-xs font-medium text-amber-700 transition hover:bg-amber-200 dark:bg-amber-500/10 dark:text-amber-400 dark:hover:bg-amber-500/20"
                            >
                                {{ labels.view }}
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="grid gap-6 md:grid-cols-2">
                <div class="rounded-xl border border-stone-200/60 bg-white/80 p-6 backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/60">
                    <h3 class="mb-4 text-lg font-semibold text-stone-900 dark:text-white">{{ labels.quick_actions }}</h3>
                    <div class="space-y-3">
                        <Link
                            :href="localeUrl('/jobs')"
                            class="flex items-center gap-3 rounded-lg bg-amber-50 p-3 text-sm font-medium text-amber-700 transition hover:bg-amber-100 dark:bg-amber-500/10 dark:text-amber-400 dark:hover:bg-amber-500/20"
                        >
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                            </svg>
                            {{ labels.browse_available_jobs }}
                        </Link>
                        <Link
                            :href="localeUrl('/candidate/applications')"
                            class="flex items-center gap-3 rounded-lg bg-stone-50 p-3 text-sm font-medium text-stone-700 transition hover:bg-stone-100 dark:bg-stone-800 dark:text-stone-300 dark:hover:bg-stone-700"
                        >
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
                            </svg>
                            {{ labels.view_my_applications }}
                        </Link>
                        <Link
                            :href="localeUrl('/profile')"
                            class="flex items-center gap-3 rounded-lg bg-stone-50 p-3 text-sm font-medium text-stone-700 transition hover:bg-stone-100 dark:bg-stone-800 dark:text-stone-300 dark:hover:bg-stone-700"
                        >
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                            </svg>
                            {{ labels.update_my_profile }}
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
