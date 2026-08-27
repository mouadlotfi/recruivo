<script setup lang="ts">
import { computed, ref } from 'vue'
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import type { PageProps } from '../../types'
import GuestLayout from '../../Layouts/GuestLayout.vue'

const props = defineProps<{ labels: Record<string, string>; old: { email: string }; messages: Record<string, string | boolean | null> }>()
const page = usePage<PageProps>()
const form = useForm({ email: props.old.email, password: '' })
const showPassword = ref(false)
const errors = computed(() => Object.values(page.props.errors ?? {}))
const localeUrl = (path: string) => `/${page.props.locale}${path}`
const submit = () => form.post(localeUrl('/login'))
</script>

<template>
    <Head :title="labels.title" />
    <GuestLayout>
        <div class="mx-auto flex max-w-xl flex-col items-center py-12">
            <div class="w-full space-y-8 rounded-3xl border border-stone-200/70 bg-white/80 p-10 shadow-2xl shadow-amber-500/10 dark:border-stone-800/60 dark:bg-stone-950/80">
                <div class="space-y-3 text-center">
                    <h1 class="font-display text-3xl font-bold text-stone-900 dark:text-white">{{ labels.title }}</h1>
                    <p class="text-sm text-stone-500 dark:text-stone-400">{{ labels.subtitle }}</p>
                </div>
                <div v-if="messages.verified" data-alert class="rounded-xl border border-green-200 bg-green-50 px-4 py-2.5 text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-200" role="status"><div class="flex items-center gap-3"><div class="min-w-0 flex-1 text-sm leading-5 sm:text-base sm:leading-6"><div class="flex items-center"><svg class="mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>{{ labels.email_verified }}</div></div></div></div>
                <div v-if="messages.info" data-alert class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-2.5 text-blue-800 dark:border-blue-800 dark:bg-blue-900/20 dark:text-blue-200" role="status"><div class="flex items-center gap-3"><div class="min-w-0 flex-1 text-sm leading-5 sm:text-base sm:leading-6">{{ messages.info }}</div></div></div>
                <div v-if="messages.status" data-alert class="rounded-xl border border-green-200 bg-green-50 px-4 py-2.5 text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-200" role="status"><div class="flex items-center gap-3"><div class="min-w-0 flex-1 text-sm leading-5 sm:text-base sm:leading-6">{{ messages.status }}</div></div></div>
                <div v-if="messages.registered" data-alert class="rounded-xl border border-green-200 bg-green-50 px-4 py-2.5 text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-200" role="status"><div class="flex items-center gap-3"><div class="min-w-0 flex-1 text-sm leading-5 sm:text-base sm:leading-6"><div class="flex items-center"><svg class="mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>{{ labels.account_created }}</div></div></div></div>
                <div v-if="errors.length" data-alert class="rounded-xl border border-red-200 bg-red-50 px-4 py-2.5 text-red-800 dark:border-red-800 dark:bg-red-900/20 dark:text-red-200" role="alert"><div class="flex items-center gap-3"><div class="min-w-0 flex-1 text-sm leading-5 sm:text-base sm:leading-6"><div v-for="error in errors" :key="error">{{ error }}</div></div></div></div>
                <form method="POST" class="space-y-6" @submit.prevent="submit">
                    <div class="space-y-2"><label for="email" class="text-sm font-medium text-stone-700 dark:text-stone-200">{{ labels.email }}</label><input id="email" v-model="form.email" name="email" type="email" autocomplete="email" :placeholder="labels.email_placeholder" required class="w-full rounded-2xl border border-stone-200/80 bg-white/80 px-4 py-3 text-sm text-stone-700 shadow-sm transition focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-700 dark:bg-stone-900/70 dark:text-stone-100 dark:focus:border-amber-500" /></div>
                    <div class="space-y-2"><div class="flex items-center justify-between text-sm"><label for="password" class="font-medium text-stone-700 dark:text-stone-200">{{ labels.password }}</label><Link :href="localeUrl('/reset-password')" class="font-semibold text-amber-600 transition hover:text-amber-500 dark:text-amber-300 dark:hover:text-amber-200">{{ labels.forgot_password }}</Link></div><div class="relative"><input id="password" v-model="form.password" name="password" :type="showPassword ? 'text' : 'password'" autocomplete="current-password" :placeholder="labels.password_placeholder" required class="w-full rounded-2xl border border-stone-200/80 bg-white/80 px-4 py-3 pr-12 text-sm text-stone-700 shadow-sm transition focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-700 dark:bg-stone-900/70 dark:text-stone-100 dark:focus:border-amber-500" /><button type="button" :aria-label="labels.toggle_password_visibility" class="absolute right-3 top-1/2 -translate-y-1/2 text-stone-400 hover:text-stone-600 dark:text-stone-500 dark:hover:text-stone-300" @click="showPassword = !showPassword"><svg v-if="!showPassword" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg><svg v-else class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/></svg></button></div></div>
                    <button type="submit" :disabled="form.processing" class="inline-flex w-full items-center justify-center rounded-2xl bg-amber-600 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-amber-500/30 transition hover:bg-amber-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 disabled:opacity-50 dark:focus-visible:ring-offset-stone-950">{{ labels.submit }}</button>
                </form>
                <p class="text-center text-sm text-stone-500 dark:text-stone-400">{{ labels.new_to_recruivo }} <Link :href="localeUrl('/register')" class="font-semibold text-amber-600 transition hover:text-amber-500 dark:text-amber-300 dark:hover:text-amber-200">{{ labels.create_account }}</Link></p>
            </div>
        </div>
    </GuestLayout>
</template>
