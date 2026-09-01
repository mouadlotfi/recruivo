<script setup lang="ts">
import { computed, ref } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import type { PageProps } from '../../types'
import { useTranslation } from '../../composables/useTranslation'
import { useDismiss } from '../../composables/useDismiss'

const page = usePage<PageProps>()
const { t } = useTranslation()

type AuthUser = NonNullable<PageProps['auth']['user']>
const user = computed(() => page.props.auth.user as AuthUser)
const isRecruiter = computed(() => user.value.roles.includes('Recruiter'))
const isAdmin = computed(() => user.value.roles.includes('Admin'))
const initials = computed(() => (user.value.name?.trim().charAt(0) ?? '?').toUpperCase())

const open = ref(false)
const root = ref<HTMLElement | null>(null)
useDismiss(open, root)

const profileUrl = `/${page.props.locale}/profile`
const adminDashboardUrl = `/${page.props.locale}/admin/dashboard`
const logoutUrl = `/${page.props.locale}/logout`
</script>

<template>
    <div ref="root" class="relative flex items-center">
        <button
            type="button"
            aria-haspopup="menu"
            :aria-expanded="open"
            class="inline-flex h-9 items-center gap-2 rounded-full px-2.5 text-sm font-medium text-stone-700 transition hover:bg-stone-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 sm:px-3 dark:text-stone-200 dark:hover:bg-stone-800"
            @click="open = !open"
        >
            <span
                v-if="isRecruiter"
                class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-amber-100 text-xs font-semibold text-amber-600 dark:bg-amber-500/10 dark:text-amber-300"
            >
                {{ initials }}
            </span>
            <svg v-else class="h-5 w-5 text-stone-500 dark:text-stone-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <span class="hidden max-w-[120px] truncate text-sm font-medium leading-none sm:inline-block">{{ user.name }}</span>
        </button>

        <div
            v-if="open"
            role="menu"
            class="absolute right-0 top-full mt-2 w-max rounded-lg border border-stone-200 bg-white py-1 shadow-lg dark:border-stone-700 dark:bg-stone-800"
        >
            <div class="border-b border-stone-200 px-4 py-3 dark:border-stone-700">
                <p class="whitespace-nowrap text-sm font-medium text-stone-800 dark:text-stone-200">{{ user.email }}</p>
            </div>
            <Link
                v-if="isAdmin"
                :href="adminDashboardUrl"
                role="menuitem"
                class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-amber-700 transition hover:bg-stone-100 dark:text-amber-400 dark:hover:bg-stone-700"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                </svg>
                {{ t('admin_dashboard') }}
            </Link>
            <Link
                v-if="!isAdmin"
                :href="profileUrl"
                role="menuitem"
                class="flex items-center gap-2 px-4 py-2 text-sm text-stone-700 transition hover:bg-stone-100 dark:text-stone-200 dark:hover:bg-stone-700"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                {{ isRecruiter ? t('company_profile') : t('profile_settings') }}
            </Link>
            <Link
                :href="logoutUrl"
                method="post"
                as="button"
                role="menuitem"
                class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm text-stone-700 transition hover:bg-stone-100 dark:text-stone-200 dark:hover:bg-stone-700"
            >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
                    </svg>
                    {{ t('sign_out') }}
            </Link>
        </div>
    </div>
</template>
