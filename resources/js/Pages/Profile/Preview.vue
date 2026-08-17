<script setup lang="ts">
import { computed } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import type { PageProps, ProfilePreviewApplicant } from '../../types'

const props = defineProps<{
    applicant: ProfilePreviewApplicant
    labels: Record<string, string>
}>()

const page = usePage<PageProps>()
const locale = computed(() => page.props.locale)
const localeUrl = (path: string) => `/${locale.value}${path}`
const profile = computed(() => props.applicant.candidateProfile)

function formatMonthYear(value: string | null | undefined): string {
    if (!value) return ''
    if (/^\d{4}$/.test(value)) return value
    const [year, month] = value.split('-')
    return new Intl.DateTimeFormat(locale.value, { month: 'short', year: 'numeric', timeZone: 'UTC' }).format(new Date(Date.UTC(Number(year), Number(month) - 1, 1)))
}

function dateRange(start: string, end: string | null, current: boolean): string {
    return `${formatMonthYear(start)} – ${current ? props.labels.present : formatMonthYear(end)}`
}

function applicantInitial(name: string): string {
    return Array.from(name)[0]?.toLocaleUpperCase(locale.value) ?? ''
}

function proficiencyLabel(level: string): string {
    return props.labels[`proficiency_${level}`] ?? level
}
</script>

<template>
    <AppLayout>
        <Head :title="labels.recruiter_preview" />

        <div class="space-y-8">
            <div class="flex flex-col gap-4 md:flex-row md:items-start">
                <div class="min-w-0">
                    <p class="text-sm font-medium text-stone-500 dark:text-stone-400">{{ labels.recruiter_preview }}</p>
                    <div class="mt-3 flex items-center gap-4">
                        <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-amber-100 text-2xl font-semibold text-amber-700 dark:bg-amber-500/10 dark:text-amber-400">{{ applicantInitial(applicant.name) }}</div>
                        <div class="min-w-0 break-words"><h1 class="break-words text-3xl font-bold text-stone-900 dark:text-white">{{ applicant.name }}</h1><p v-if="profile?.headline" class="mt-1 break-words text-stone-600 dark:text-stone-400">{{ profile.headline }}</p></div>
                    </div>
                </div>
                <Link
                    :href="localeUrl('/profile')"
                    class="inline-flex min-h-11 shrink-0 items-center text-sm font-medium text-amber-600 hover:text-amber-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400 focus-visible:ring-offset-2 dark:text-amber-400 dark:focus-visible:ring-offset-stone-950 md:ml-auto"
                >← {{ labels.back_to_profile_settings }}</Link>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <aside class="space-y-6">
                    <section class="rounded-xl border border-stone-200/60 bg-white/80 p-6 dark:border-stone-700/60 dark:bg-stone-900/60">
                        <h2 class="font-semibold text-stone-900 dark:text-white">{{ labels.contact_information }}</h2>
                        <dl class="mt-4 space-y-3 text-sm"><div><dt class="text-stone-500">{{ labels.email_address }}</dt><dd class="mt-1 break-all text-stone-800 dark:text-stone-200">{{ applicant.email }}</dd></div><div><dt class="text-stone-500">{{ labels.phone_number }}</dt><dd class="mt-1 text-stone-800 dark:text-stone-200">{{ applicant.phone || labels.not_provided }}</dd></div></dl>
                    </section>
                </aside>

                <div class="space-y-6 lg:col-span-2">
                    <section v-if="applicant.profile_summary" class="rounded-xl border border-stone-200/60 bg-white/80 p-6 dark:border-stone-700/60 dark:bg-stone-900/60"><h2 class="font-semibold text-stone-900 dark:text-white">{{ labels.about_heading }}</h2><p class="mt-3 whitespace-pre-line text-sm text-stone-700 dark:text-stone-300">{{ applicant.profile_summary }}</p></section>
                    <section v-if="profile?.skills" class="rounded-xl border border-stone-200/60 bg-white/80 p-6 dark:border-stone-700/60 dark:bg-stone-900/60"><h2 class="font-semibold text-stone-900 dark:text-white">{{ labels.skills }}</h2><p class="mt-3 whitespace-pre-line text-sm text-stone-700 dark:text-stone-300">{{ profile.skills }}</p></section>
                    <section v-if="profile?.languages_data.length" class="rounded-xl border border-stone-200/60 bg-white/80 p-6 dark:border-stone-700/60 dark:bg-stone-900/60"><h2 class="font-semibold text-stone-900 dark:text-white">{{ labels.languages }}</h2><div class="mt-4 space-y-3"><div v-for="(language, index) in profile.languages_data" :key="`${language.language}-${index}`" class="flex items-center justify-between gap-3 rounded-lg bg-stone-50 px-4 py-3 text-sm dark:bg-stone-800"><span class="font-medium text-stone-900 dark:text-white">{{ language.language }}</span><span class="text-stone-500 dark:text-stone-400">{{ proficiencyLabel(language.proficiency) }}</span></div></div></section>
                    <section v-if="profile?.profile_links.length" class="rounded-xl border border-stone-200/60 bg-white/80 p-6 dark:border-stone-700/60 dark:bg-stone-900/60"><h2 class="font-semibold text-stone-900 dark:text-white">{{ labels.links }}</h2><div class="mt-4 flex flex-wrap gap-2"><a v-for="link in profile.profile_links" :key="link.name" :href="link.url" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-11 items-center rounded-xl bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-700 transition hover:bg-amber-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400 dark:bg-amber-500/10 dark:text-amber-400 dark:hover:bg-amber-500/20">{{ link.name }} ↗</a></div></section>
                    <section v-if="profile?.experiences.length" class="rounded-xl border border-stone-200/60 bg-white/80 p-6 dark:border-stone-700/60 dark:bg-stone-900/60"><h2 class="font-semibold text-stone-900 dark:text-white">{{ labels.experience }}</h2><div class="mt-4 divide-y divide-stone-200 dark:divide-stone-700"><article v-for="(experience, index) in profile.experiences" :key="`${experience.job_title}-${index}`" class="py-4 first:pt-0 last:pb-0"><h3 class="font-semibold text-stone-900 dark:text-white">{{ experience.job_title }}</h3><p class="text-sm text-stone-700 dark:text-stone-300">{{ experience.company_name }}<span v-if="experience.location"> · {{ experience.location }}</span></p><p class="mt-1 text-sm text-stone-500">{{ dateRange(experience.start_date, experience.end_date, experience.is_current) }}</p><p v-if="experience.description" class="mt-3 whitespace-pre-line text-sm text-stone-600 dark:text-stone-400">{{ experience.description }}</p></article></div></section>
                    <section v-if="profile?.educations.length" class="rounded-xl border border-stone-200/60 bg-white/80 p-6 dark:border-stone-700/60 dark:bg-stone-900/60"><h2 class="font-semibold text-stone-900 dark:text-white">{{ labels.education }}</h2><div class="mt-4 divide-y divide-stone-200 dark:divide-stone-700"><article v-for="(education, index) in profile.educations" :key="`${education.school}-${index}`" class="py-4 first:pt-0 last:pb-0"><h3 class="font-semibold text-stone-900 dark:text-white">{{ education.school }}</h3><p class="text-sm text-stone-700 dark:text-stone-300">{{ education.degree }} · {{ education.field_of_study }}</p><p class="mt-1 text-sm text-stone-500">{{ dateRange(education.start_date, education.end_date, education.is_current) }}</p><p v-if="education.description" class="mt-3 whitespace-pre-line text-sm text-stone-600 dark:text-stone-400">{{ education.description }}</p></article></div></section>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
