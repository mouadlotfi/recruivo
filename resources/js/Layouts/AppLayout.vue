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
const { t } = useTranslation()

const user = computed(() => page.props.auth.user)
const isRecruiter = computed(() => user.value?.roles.includes('Recruiter') ?? false)
const isAdmin = computed(() => user.value?.roles.includes('Admin') ?? false)

const localeUrl = (path: string) => `/${page.props.locale}${path}`
const logoHref = computed(() => {
    if (isAdmin.value) return localeUrl('/admin/dashboard')
    if (isRecruiter.value) return localeUrl('/recruiter/dashboard')

    return localeUrl('/')
})
</script>

<template>
    <div class="flex min-h-screen flex-col bg-stone-100 pb-16 text-stone-900 antialiased sm:pb-0 dark:bg-stone-950 dark:text-stone-100">
        <a href="#main-content" class="sr-only z-[100] rounded-lg bg-amber-600 px-4 py-2 font-semibold text-white focus:not-sr-only focus:fixed focus:left-4 focus:top-4">{{ t('skip_to_content') }}</a>

        <SiteHeader :logo-href="logoHref" hide-navigation-for-admin />

        <FlashMessages />

        <main id="main-content" tabindex="-1" class="mx-auto w-full max-w-6xl flex-1 px-4 pb-20 pt-10 sm:px-6 sm:pb-16">
            <slot />
        </main>

        <Footer />
        <CookieConsent />

        <MobileNav v-if="!isAdmin" />
        <ScrollToTop />
    </div>
</template>
