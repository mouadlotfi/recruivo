<script setup lang="ts">
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import type { PageProps } from '../types'
import { useTranslation } from '../composables/useTranslation'
import SiteHeader from '../Components/Layout/SiteHeader.vue'
import MobileNav from '../Components/Layout/MobileNav.vue'
import ScrollToTop from '../Components/Layout/ScrollToTop.vue'
import FlashMessages from '../Components/Layout/FlashMessages.vue'
import Footer from '../Components/Layout/Footer.vue'
import CookieConsent from '../Components/Layout/CookieConsent.vue'

const page = usePage<PageProps>()
const { t, localeUrl } = useTranslation()
const user = computed(() => page.props.auth.user)
const isRecruiter = computed(() => user.value?.roles.includes('Recruiter') ?? false)
const isAdmin = computed(() => user.value?.roles.includes('Admin') ?? false)
const logoHref = computed(() => localeUrl(isRecruiter.value ? '/recruiter/dashboard' : '/'))
</script>

<template>
    <div class="flex min-h-screen flex-col bg-stone-50 pb-16 text-stone-900 antialiased transition-colors duration-300 sm:pb-0 dark:bg-stone-950 dark:text-stone-100">
        <a href="#main-content" class="sr-only z-[100] rounded-lg bg-amber-600 px-4 py-2 font-semibold text-white focus:not-sr-only focus:fixed focus:left-4 focus:top-4">{{ t('skip_to_content') }}</a>
        <div class="relative isolate flex-1 overflow-hidden">
            <div class="pointer-events-none absolute inset-x-0 top-0 -z-10">
                <div class="mx-auto h-72 max-w-5xl rounded-full bg-gradient-to-r from-amber-400/20 via-teal-400/10 to-stone-400/15 blur-3xl"></div>
                <div class="absolute -bottom-20 right-10 h-48 w-48 rounded-full bg-amber-300/20 blur-2xl dark:bg-amber-500/10"></div>
            </div>

            <SiteHeader :logo-href="logoHref" sign-out-for-admin />

            <main id="main-content" tabindex="-1" class="mx-auto max-w-6xl px-4 pb-20 pt-10 sm:px-6 sm:pb-16">
                <slot />
            </main>
        </div>
        <FlashMessages />
        <Footer />
        <CookieConsent />
        <MobileNav v-if="!isAdmin" />
        <ScrollToTop />
    </div>
</template>
