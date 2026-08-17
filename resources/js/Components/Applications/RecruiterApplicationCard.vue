<script setup lang="ts">
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import type { PageProps, RecruiterApplication, RecruiterNoteTemplate } from '../../types'
import ApplicationStatusBadge from './ApplicationStatusBadge.vue'
import CoverLetterDisclosure from './CoverLetterDisclosure.vue'
import StatusTimeline from './StatusTimeline.vue'
import ApplicationReviewPanel from './ApplicationReviewPanel.vue'

const props = defineProps<{
    application: RecruiterApplication
    noteTemplates: RecruiterNoteTemplate[]
    labels: Record<string, string>
}>()

const page = usePage<PageProps>()

// Same guard as the Blade @if($errors->any()) open — a failed validation
// must not hide behind a collapsed card (all cards open on error; the errors
// are shared page props, matching the Blade behavior).
const hasPageErrors = computed(() => Object.keys(page.props.errors).length > 0)

const showReviewPanel = computed(() => props.application.is_withdrawn || props.application.can_review)

const avatarInitial = computed(() => props.application.candidate.name.charAt(0))
</script>

<template>
    <article class="rounded-2xl border border-stone-200/70 bg-white/85 p-5 shadow-sm backdrop-blur transition hover:border-amber-300/70 sm:p-6 dark:border-stone-800 dark:bg-stone-900/70 dark:hover:border-amber-700/70">
        <details data-application-card-collapsible class="group" :open="hasPageErrors">
            <summary class="flex cursor-pointer list-none flex-wrap items-center gap-3 [&::-webkit-details-marker]:hidden">
                <div class="flex min-w-0 flex-1 basis-full items-center gap-3 sm:basis-auto">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-amber-100 text-lg font-semibold text-amber-700 dark:bg-amber-500/10 dark:text-amber-400">
                        {{ avatarInitial }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <h2 class="text-lg font-semibold leading-snug text-stone-900 dark:text-white">
                            {{ application.candidate.name }}
                        </h2>
                        <p class="mt-0.5 break-words text-sm text-stone-600 dark:text-stone-400">
                            {{ application.candidate.email }}
                        </p>
                    </div>
                </div>

                <ApplicationStatusBadge :status="application.status" :label="application.status_label" :show-dot="false" />

                <svg class="h-4 w-4 shrink-0 text-stone-500 transition group-open:rotate-180 dark:text-stone-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
            </summary>

            <div class="mt-5 grid gap-6" :class="showReviewPanel ? 'lg:grid-cols-[minmax(0,1fr)_18rem]' : ''">
                <div class="min-w-0 space-y-5">
                    <div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-stone-600 dark:text-stone-400">
                        <span class="break-words">{{ application.applied_label }}</span>
                        <span class="break-words"><strong>{{ labels.phone }}</strong> {{ application.candidate.phone ?? labels.not_provided }}</span>
                        <Link
                            v-if="application.candidate.has_resume && application.candidate.resume_url"
                            :href="application.candidate.resume_url"
                            target="_blank"
                            rel="noopener"
                            class="font-medium text-amber-600 hover:text-amber-500 dark:text-amber-400"
                        >
                            {{ labels.view_resume }}
                        </Link>
                    </div>

                    <CoverLetterDisclosure
                        v-if="application.cover_letter"
                        :content="application.cover_letter"
                        :label="labels.cover_letter"
                        variant="recruiter"
                    />

                    <section v-if="application.notes">
                        <h3 class="mb-2 text-sm font-semibold text-stone-700 dark:text-stone-300">{{ labels.your_notes }}</h3>
                        <p class="rounded-xl bg-blue-50 p-4 text-sm text-stone-700 dark:bg-blue-500/10 dark:text-stone-300">{{ application.notes }}</p>
                    </section>

                    <section
                        v-if="application.timeline.length"
                        class="mt-4"
                        :aria-labelledby="`status-timeline-${application.id}`"
                    >
                        <h3 :id="`status-timeline-${application.id}`" class="mb-2 text-sm font-semibold text-stone-700 dark:text-stone-300">
                            {{ labels.status_timeline }}
                        </h3>
                        <StatusTimeline :events="application.timeline" />
                    </section>
                </div>

                <aside
                    v-if="showReviewPanel"
                    data-application-review-panel
                    class="self-start rounded-xl border border-stone-200 bg-stone-50/80 p-4 dark:border-stone-700 dark:bg-stone-800/60"
                >
                    <p v-if="application.is_withdrawn" class="text-sm font-semibold text-stone-800 dark:text-stone-200">
                        {{ labels.withdrawn_by_candidate }}
                    </p>
                    <ApplicationReviewPanel
                        v-else
                        :application="application"
                        :note-templates="noteTemplates"
                        :labels="labels"
                    />
                </aside>
            </div>
        </details>
    </article>
</template>