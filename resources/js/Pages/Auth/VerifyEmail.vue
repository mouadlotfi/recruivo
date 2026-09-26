<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3'
import type { PageProps } from '../../types'
import GuestLayout from '../../Layouts/GuestLayout.vue'
const props = defineProps<{ email: string; labels: Record<string, string>; message?: string | null }>()
const page = usePage<PageProps>()
const form = useForm({})
const localeUrl = (path: string) => `/${page.props.locale}${path}`
const resend = () => form.post(localeUrl('/email/verification-notification'), { preserveScroll: true })
const logout = () => router.post(localeUrl('/logout'))
</script>
<template>
    <Head :title="labels.title" /><GuestLayout><div class="mx-auto flex max-w-xl flex-col items-center py-12"><div class="w-full space-y-8 rounded-3xl border border-stone-200/70 bg-white/80 p-10 shadow-2xl shadow-amber-500/10 dark:border-stone-800/60 dark:bg-stone-950/80"><div class="space-y-3 text-center"><div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-500/10"><svg class="h-8 w-8 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" /></svg></div><h1 class="font-display text-3xl font-bold text-stone-900 dark:text-white">{{ labels.title }}</h1><p class="text-sm text-stone-600 dark:text-stone-400">{{ labels.description }}</p></div><div v-if="message" data-alert class="rounded-xl border border-green-200 bg-green-50 px-4 py-2.5 text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-200" role="status">{{ message }}</div><form method="POST" @submit.prevent="resend"><button type="submit" :disabled="form.processing" class="inline-flex w-full items-center justify-center rounded-2xl bg-amber-600 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-amber-500/30 transition hover:bg-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200 focus:ring-offset-2 disabled:opacity-50 dark:focus:ring-offset-stone-950">{{ labels.resend }}</button></form><form method="POST" @submit.prevent="logout"><button type="submit" class="w-full text-center text-sm text-stone-600 hover:text-stone-900 dark:text-stone-400 dark:hover:text-stone-200">{{ labels.logout }}</button></form></div></div></GuestLayout>
</template>
