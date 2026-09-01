<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import type { PageProps, Pagination } from '../../types'
import AdminLayout from '../../Components/Admin/AdminLayout.vue'
import AdminBreadcrumb from '../../Components/Admin/AdminBreadcrumb.vue'

type AdminUser = {
    id: number
    name: string
    email: string
    phone: string | null
    roles: string[]
    is_candidate?: boolean
    candidate_url?: string | null
    company: { id?: number; name: string; slug?: string; url?: string } | null
    applications_count: number
    email_verified: boolean
    is_demo: boolean
    is_admin: boolean
    joined_label: string | null
}

const props = defineProps<{
    users: AdminUser[]
    pagination: Pagination
    filters: { search: string; role: string }
    labels: Record<string, string>
}>()

const page = usePage<PageProps>()
const localeUrl = (path: string) => `/${page.props.locale}${path}`
const items = ref<AdminUser[]>([...props.users])
const search = ref(props.filters.search)
const role = ref(props.filters.role)
const selectedUser = ref<AdminUser | null>(null)
const loading = ref(false)

watch(() => props.users, (incoming) => {
    const known = new Set(items.value.map((user) => user.id))
    items.value = props.pagination.current_page === 1
        ? [...incoming]
        : [...items.value, ...incoming.filter((user) => !known.has(user.id))]
})

const hasMore = computed(() => props.pagination.next_page_url !== null)
const roleName = (user: AdminUser) => user.roles.includes('Admin') ? props.labels.admin : user.roles.includes('Recruiter') ? props.labels.recruiter : props.labels.candidate
const roleClasses = (user: AdminUser) => user.roles.includes('Admin')
    ? 'bg-teal-100 text-teal-700 dark:bg-teal-500/10 dark:text-teal-400'
    : user.roles.includes('Recruiter')
        ? 'bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400'
        : 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400'

const submitFilters = () => router.get(localeUrl('/admin/users'), { search: search.value || undefined, role: role.value || undefined }, { preserveState: true, replace: true })
const clearFilters = () => { search.value = ''; role.value = ''; submitFilters() }
const loadMore = () => {
    if (!props.pagination.next_page_url || loading.value) return
    loading.value = true
    router.get(props.pagination.next_page_url, {}, { preserveState: true, preserveScroll: true, onFinish: () => { loading.value = false } })
}
const deleteUser = () => {
    if (!selectedUser.value) return
    router.delete(localeUrl(`/admin/users/${selectedUser.value.id}`), { preserveScroll: true, onFinish: () => { selectedUser.value = null } })
}
</script>

<template>
    <Head :title="labels.title" />

    <AdminLayout :labels="labels">
        <div class="space-y-8">
            <div class="flex items-center justify-between">
                <div class="min-w-0">
                    <AdminBreadcrumb :items="[{ label: labels.title }]" />
                    <h1 class="mt-2 text-2xl font-semibold text-stone-900 dark:text-white sm:text-3xl">{{ labels.title }}</h1>
                    <p class="mt-2 text-sm text-stone-600 dark:text-stone-400 sm:text-base">{{ labels.registered_users_count }}</p>
                </div>
            </div>

            <section aria-labelledby="admin-users-filters-heading" class="rounded-xl border border-stone-200/60 bg-white/80 p-3 backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/60 sm:p-4">
                <h2 id="admin-users-filters-heading" class="sr-only">{{ labels.search }}</h2>
                <form class="flex flex-col gap-3 md:flex-row md:items-center" @submit.prevent="submitFilters">
                    <div class="min-w-0 flex-1">
                        <label for="admin-user-search" class="sr-only">{{ labels.search_placeholder }}</label>
                        <input
                            id="admin-user-search"
                            v-model="search"
                            type="search"
                            name="search"
                            :placeholder="labels.search_placeholder"
                            class="min-h-11 w-full rounded-lg border border-stone-200 bg-white px-3 text-sm text-stone-800 focus:border-amber-500 focus:ring-2 focus:ring-amber-200 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200 dark:focus:border-amber-400 dark:focus:ring-amber-500/20 sm:px-4"
                        />
                    </div>
                    <div class="md:w-48">
                        <label for="admin-role-filter" class="sr-only">{{ labels.all_roles }}</label>
                        <select
                            id="admin-role-filter"
                            v-model="role"
                            name="role"
                            class="min-h-11 w-full rounded-lg border border-stone-200 bg-white px-3 text-sm text-stone-800 focus:border-amber-500 focus:ring-2 focus:ring-amber-200 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200 dark:focus:border-amber-400 dark:focus:ring-amber-500/20 sm:px-4"
                        >
                            <option value="">{{ labels.all_roles }}</option>
                            <option value="Candidate">{{ labels.candidate }}</option>
                            <option value="Recruiter">{{ labels.recruiter }}</option>
                            <option value="Admin">{{ labels.admin }}</option>
                        </select>
                    </div>
                    <div class="flex gap-2 sm:shrink-0">
                        <button
                            type="submit"
                            class="inline-flex min-h-11 flex-1 items-center justify-center gap-2 rounded-lg bg-amber-600 px-4 text-sm font-semibold text-white transition hover:bg-amber-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 sm:flex-none sm:px-5"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                            </svg>
                            <span>{{ labels.search_button }}</span>
                        </button>
                        <button
                            v-if="search || role"
                            type="button"
                            class="inline-flex min-h-11 flex-1 items-center justify-center rounded-lg bg-stone-100 px-4 text-sm font-semibold text-stone-700 transition hover:bg-stone-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 dark:bg-stone-800 dark:text-stone-300 dark:hover:bg-stone-700 sm:flex-none"
                            @click="clearFilters"
                        >
                            {{ labels.clear || labels.clear_button || 'Clear' }}
                        </button>
                    </div>
                </form>
            </section>

            <div v-if="items.length === 0" class="rounded-xl border border-stone-200/60 bg-white/80 p-12 text-center backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/60">
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-stone-100 dark:bg-stone-800">
                    <svg class="h-8 w-8 text-stone-600 dark:text-stone-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                    </svg>
                </div>
                <h3 class="mb-2 text-lg font-semibold text-stone-900 dark:text-white">{{ labels.no_users_found }}</h3>
                <p class="text-stone-600 dark:text-stone-400">{{ search ? labels.no_users_match_criteria : labels.no_users_registered }}</p>
            </div>

            <template v-else>
                <div class="space-y-4">
                    <div
                        v-for="user in items"
                        :key="user.id"
                        class="rounded-xl border border-stone-200/60 bg-white/80 p-4 backdrop-blur transition hover:border-amber-200 dark:border-stone-700/60 dark:bg-stone-900/60 dark:hover:border-amber-800 sm:p-5"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex min-w-0 flex-1 items-start gap-3">
                                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-amber-100 text-base font-semibold text-amber-700 dark:bg-amber-500/10 dark:text-amber-400 sm:h-12 sm:w-12 sm:text-lg">
                                    {{ user.name.substring(0, 1) }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <h3 class="break-words text-base font-semibold text-stone-900 dark:text-white sm:text-lg">
                                        <Link
                                            v-if="user.candidate_url"
                                            :href="user.candidate_url"
                                            class="transition hover:text-amber-600 hover:underline dark:hover:text-amber-400"
                                        >
                                            {{ user.name }}
                                        </Link>
                                        <span v-else>{{ user.name }}</span>
                                    </h3>
                                    <p class="mt-0.5 break-all text-xs text-stone-600 dark:text-stone-400 sm:text-sm">
                                        {{ user.email }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex shrink-0 flex-wrap items-center justify-end gap-1.5">
                                <span
                                    class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium"
                                    :class="roleClasses(user)"
                                >
                                    <svg class="mr-1 h-2.5 w-2.5 shrink-0" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3" /></svg>
                                    {{ roleName(user) }}
                                </span>
                                <span
                                    v-if="user.is_demo"
                                    class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-700 dark:bg-amber-500/10 dark:text-amber-300"
                                >
                                    {{ labels.demo_account }}
                                </span>
                            </div>
                        </div>

                        <div class="mt-4 grid grid-cols-2 gap-3 border-t border-stone-100 pt-3 text-xs text-stone-600 dark:border-stone-800/80 dark:text-stone-400 sm:grid-cols-3 sm:gap-4 sm:pt-4 sm:text-sm md:grid-cols-4 lg:grid-cols-5">
                            <div class="space-y-0.5">
                                <span class="text-stone-500 dark:text-stone-400">{{ labels.phone }}</span>
                                <p class="break-words font-medium text-stone-800 dark:text-stone-200">{{ user.phone || labels.not_provided }}</p>
                            </div>
                            <div v-if="user.company" class="space-y-0.5">
                                <span class="text-stone-500 dark:text-stone-400">{{ labels.company }}</span>
                                <p class="break-words font-medium">
                                    <Link
                                        v-if="user.company.slug"
                                        :href="localeUrl('/companies/' + user.company.slug)"
                                        class="text-amber-700 transition hover:text-amber-800 hover:underline dark:text-amber-400 dark:hover:text-amber-300"
                                    >
                                        {{ user.company.name }}
                                    </Link>
                                    <span v-else class="text-stone-800 dark:text-stone-200">{{ user.company.name }}</span>
                                </p>
                            </div>
                            <div class="space-y-0.5">
                                <span class="text-stone-500 dark:text-stone-400">{{ labels.applications }}</span>
                                <p class="font-medium text-stone-800 dark:text-stone-200">{{ user.applications_count }}</p>
                            </div>
                            <div v-if="!user.is_admin" class="space-y-0.5">
                                <span class="text-stone-500 dark:text-stone-400">{{ labels.email_verified }}</span>
                                <p class="flex items-center font-medium text-stone-800 dark:text-stone-200">
                                    <span v-if="user.email_verified" class="inline-flex items-center text-green-600 dark:text-green-400">
                                        <svg class="mr-1 h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        {{ labels.yes }}
                                    </span>
                                    <span v-else class="inline-flex items-center text-amber-600 dark:text-amber-400">
                                        <svg class="mr-1 h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                                        {{ labels.no }}
                                    </span>
                                </p>
                            </div>
                            <div class="space-y-0.5">
                                <span class="text-stone-500 dark:text-stone-400">{{ labels.joined }}</span>
                                <p class="font-medium text-stone-800 dark:text-stone-200">{{ user.joined_label }}</p>
                            </div>
                        </div>

                        <div class="mt-3.5 flex flex-wrap items-center justify-end gap-2 border-t border-stone-100 pt-3 dark:border-stone-800/80 sm:mt-4">
                            <Link
                                v-if="user.candidate_url"
                                :href="user.candidate_url"
                                class="inline-flex min-h-9 items-center justify-center gap-1.5 rounded-lg border border-amber-300 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-800 transition hover:bg-amber-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-400 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300 dark:hover:bg-amber-500/20"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg>
                                {{ labels.view_profile }}
                            </Link>
                            <Link
                                v-if="user.company?.slug"
                                :href="localeUrl('/companies/' + user.company.slug)"
                                class="inline-flex min-h-9 items-center justify-center gap-1.5 rounded-lg border border-stone-200 bg-stone-50 px-3 py-1.5 text-xs font-semibold text-stone-700 transition hover:bg-stone-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-300 dark:hover:bg-stone-700"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z" /></svg>
                                {{ labels.view_company }}
                            </Link>
                            <button
                                v-if="!user.is_admin && !user.is_demo"
                                type="button"
                                class="inline-flex min-h-9 items-center justify-center rounded-lg bg-red-100 px-3 py-1.5 text-xs font-medium text-red-700 transition hover:bg-red-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-400 dark:bg-red-500/10 dark:text-red-400 dark:hover:bg-red-500/20"
                                :title="labels.delete_user"
                                @click="selectedUser = user"
                            >
                                <svg class="mr-1.5 h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                </svg>
                                {{ labels.delete_user }}
                            </button>
                            <span v-else-if="user.is_admin" class="text-xs italic text-stone-500 dark:text-stone-400">{{ labels.protected }}</span>
                        </div>
                    </div>
                </div>

                <div v-if="hasMore" class="mt-8 flex min-h-12 items-center justify-center">
                    <button
                        type="button"
                        :disabled="loading"
                        class="inline-flex min-h-11 items-center justify-center rounded-xl border border-stone-200 bg-white px-6 py-2.5 text-sm font-semibold text-stone-700 shadow-sm transition hover:bg-stone-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 disabled:cursor-wait disabled:opacity-70 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200 dark:hover:bg-stone-700"
                        @click="loadMore"
                    >
                        {{ loading ? labels.loading_more : labels.show_more }}
                    </button>
                </div>
            </template>

            <div v-if="selectedUser" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" :aria-labelledby="`delete-user-title-${selectedUser.id}`" @keydown.escape="selectedUser = null"><div class="flex min-h-screen items-center justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0"><div class="fixed inset-0 bg-stone-900/75 backdrop-blur-sm" @click="selectedUser = null"></div><span class="hidden sm:inline-block sm:h-screen sm:align-middle">&#8203;</span><div class="inline-block transform overflow-hidden rounded-2xl border border-stone-200/60 bg-white/95 text-left align-bottom shadow-2xl backdrop-blur transition-all dark:border-stone-700/60 dark:bg-stone-900/95 sm:my-8 sm:w-full sm:max-w-lg sm:align-middle"><div class="p-6 sm:p-8"><div class="flex items-start"><div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/20"><svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg></div></div><div class="mt-4 text-center"><h3 :id="`delete-user-title-${selectedUser.id}`" class="text-xl font-semibold text-stone-900 dark:text-white">{{ labels.delete_user }}</h3><div class="mt-3"><p class="text-sm text-stone-600 dark:text-stone-400">{{ labels.delete_user_confirmation }}</p></div></div></div><div class="bg-stone-50/80 px-6 py-4 dark:bg-stone-800/40 sm:flex sm:flex-row-reverse sm:px-8"><button type="button" class="inline-flex w-full justify-center rounded-2xl bg-red-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-red-500/30 transition hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-stone-900 sm:w-auto" @click="deleteUser">{{ labels.delete_user }}</button><button type="button" class="mt-3 inline-flex w-full justify-center rounded-2xl border border-stone-200/80 bg-white px-6 py-3 text-sm font-semibold text-stone-700 shadow-sm transition hover:bg-stone-50 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:border-stone-700 dark:bg-stone-800/80 dark:text-stone-200 dark:hover:bg-stone-700 dark:focus:ring-offset-stone-900 sm:mr-3 sm:mt-0 sm:w-auto" @click="selectedUser = null">{{ labels.cancel }}</button></div></div></div></div>
        </div>
    </AdminLayout>
</template>
