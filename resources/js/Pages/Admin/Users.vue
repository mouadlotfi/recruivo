<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import type { PageProps, Pagination } from '../../types'
import AppLayout from '../../Layouts/AppLayout.vue'

type AdminUser = {
    id: number
    name: string
    email: string
    phone: string | null
    roles: string[]
    company: { name: string } | null
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

    <AppLayout>
        <div class="space-y-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-stone-900 dark:text-white sm:text-3xl">{{ labels.title }}</h1>
                    <p class="mt-2 text-sm text-stone-600 dark:text-stone-400 sm:text-base">{{ labels.registered_users_count }}</p>
                </div>
            </div>

            <div class="rounded-xl border border-stone-200/60 bg-white/80 p-3 backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/60 sm:p-4">
                <form class="flex flex-col gap-3 md:flex-row" @submit.prevent="submitFilters">
                    <div class="flex-1"><label for="admin-user-search" class="sr-only">{{ labels.search_placeholder }}</label><input id="admin-user-search" v-model="search" type="text" name="search" :placeholder="labels.search_placeholder" class="w-full rounded-lg border border-stone-200 bg-white px-3 py-2 text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-200 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-300 dark:focus:border-amber-400 dark:focus:ring-amber-500/20 sm:px-4" /></div>
                    <div><label for="admin-role-filter" class="sr-only">{{ labels.all_roles }}</label><select id="admin-role-filter" v-model="role" name="role" class="w-full rounded-lg border border-stone-200 bg-white px-3 py-2 text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-200 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-300 dark:focus:border-amber-400 dark:focus:ring-amber-500/20 sm:px-4 md:w-auto"><option value="">{{ labels.all_roles }}</option><option value="Candidate">{{ labels.candidate }}</option><option value="Recruiter">{{ labels.recruiter }}</option><option value="Admin">{{ labels.admin }}</option></select></div>
                    <div class="flex gap-2"><button type="submit" class="inline-flex flex-1 items-center justify-center rounded-lg bg-amber-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-amber-500 sm:flex-none sm:px-6"><svg class="h-4 w-4 sm:mr-2" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg><span class="hidden sm:inline">{{ labels.search_button }}</span></button><button v-if="search || role" type="button" class="inline-flex flex-1 items-center justify-center rounded-lg bg-stone-100 px-4 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-200 dark:bg-stone-800 dark:text-stone-300 dark:hover:bg-stone-700 sm:flex-none" @click="clearFilters"><span class="hidden sm:inline">{{ labels.clear_button }}</span><span class="sm:hidden">{{ labels.clear_button }}</span></button></div>
                </form>
            </div>

            <div v-if="items.length === 0" class="rounded-xl border border-stone-200/60 bg-white/80 p-12 text-center backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/60">
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-stone-100 dark:bg-stone-800"><svg class="h-8 w-8 text-stone-600 dark:text-stone-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg></div>
                <h3 class="mb-2 text-lg font-semibold text-stone-900 dark:text-white">{{ labels.no_users_found }}</h3>
                <p class="text-stone-600 dark:text-stone-400">{{ search ? labels.no_users_match_criteria : labels.no_users_registered }}</p>
            </div>

            <template v-else>
                <div class="space-y-4">
                    <div v-for="user in items" :key="user.id" class="rounded-xl border border-stone-200/60 bg-white/80 p-4 backdrop-blur transition hover:border-amber-200 dark:border-stone-700/60 dark:bg-stone-900/60 dark:hover:border-amber-800 sm:p-6">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div class="flex-1">
                                <div class="mb-3 flex items-center gap-3"><div class="flex h-12 w-12 items-center justify-center rounded-full bg-amber-100 text-lg font-semibold text-amber-700 dark:bg-amber-500/10 dark:text-amber-400">{{ user.name.substring(0, 1) }}</div><div><h3 class="text-lg font-semibold text-stone-900 dark:text-white">{{ user.name }}</h3><p class="text-sm text-stone-600 dark:text-stone-400">{{ user.email }}</p></div><span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium" :class="roleClasses(user)"><svg class="mr-1 h-3 w-3" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3" /></svg>{{ roleName(user) }}</span><span v-if="user.is_demo" class="inline-flex items-center rounded-full bg-amber-100 px-3 py-1 text-xs font-medium text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">{{ labels.demo_account }}</span></div>
                                <div class="grid grid-cols-1 gap-4 text-sm md:grid-cols-3"><div><span class="text-stone-500 dark:text-stone-500">{{ labels.phone }}</span><span class="ml-2 text-stone-700 dark:text-stone-300">{{ user.phone || labels.not_provided }}</span></div><div v-if="user.company"><span class="text-stone-500 dark:text-stone-500">{{ labels.company }}</span><span class="ml-2 text-stone-700 dark:text-stone-300">{{ user.company.name }}</span></div><div><span class="text-stone-500 dark:text-stone-500">{{ labels.applications }}</span><span class="ml-2 text-stone-700 dark:text-stone-300">{{ user.applications_count }}</span></div><div v-if="!user.is_admin"><span class="text-stone-500 dark:text-stone-500">{{ labels.email_verified }}</span><span class="ml-2"><span v-if="user.email_verified" class="inline-flex items-center text-green-600 dark:text-green-400"><svg class="mr-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>{{ labels.yes }}</span><span v-else class="inline-flex items-center text-amber-600 dark:text-amber-400"><svg class="mr-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>{{ labels.no }}</span></span></div><div><span class="text-stone-500 dark:text-stone-500">{{ labels.joined }}</span><span class="ml-2 text-stone-700 dark:text-stone-300">{{ user.joined_label }}</span></div></div>
                            </div>
                            <div class="sm:ml-6"><button v-if="!user.is_admin && !user.is_demo" type="button" class="inline-flex items-center justify-center rounded-lg bg-red-100 px-4 py-2 text-sm font-medium text-red-700 transition hover:bg-red-200 dark:bg-red-500/10 dark:text-red-400 dark:hover:bg-red-500/20" :title="labels.delete_user" @click="selectedUser = user"><svg class="mr-1.5 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>{{ labels.delete_user }}</button><div v-else class="text-sm italic text-stone-500 dark:text-stone-500">{{ labels.protected }}</div></div>
                        </div>
                    </div>
                </div>
                <div v-if="hasMore" class="mt-8 flex min-h-12 items-center justify-center"><button type="button" :disabled="loading" class="inline-flex min-h-11 items-center justify-center rounded-full bg-amber-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-2 dark:focus:ring-offset-stone-950" @click="loadMore">{{ loading ? labels.loading_more : labels.show_more }}</button></div>
            </template>

            <div v-if="selectedUser" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" :aria-labelledby="`delete-user-title-${selectedUser.id}`" @keydown.escape="selectedUser = null"><div class="flex min-h-screen items-center justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0"><div class="fixed inset-0 bg-stone-900/75 backdrop-blur-sm" @click="selectedUser = null"></div><span class="hidden sm:inline-block sm:h-screen sm:align-middle">&#8203;</span><div class="inline-block transform overflow-hidden rounded-2xl border border-stone-200/60 bg-white/95 text-left align-bottom shadow-2xl backdrop-blur transition-all dark:border-stone-700/60 dark:bg-stone-900/95 sm:my-8 sm:w-full sm:max-w-lg sm:align-middle"><div class="p-6 sm:p-8"><div class="flex items-start"><div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/20"><svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg></div></div><div class="mt-4 text-center"><h3 :id="`delete-user-title-${selectedUser.id}`" class="text-xl font-semibold text-stone-900 dark:text-white">{{ labels.delete_user }}</h3><div class="mt-3"><p class="text-sm text-stone-600 dark:text-stone-400">{{ labels.delete_user_confirmation }}</p></div></div></div><div class="bg-stone-50/80 px-6 py-4 dark:bg-stone-800/40 sm:flex sm:flex-row-reverse sm:px-8"><button type="button" class="inline-flex w-full justify-center rounded-2xl bg-red-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-red-500/30 transition hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-stone-900 sm:w-auto" @click="deleteUser">{{ labels.delete_user }}</button><button type="button" class="mt-3 inline-flex w-full justify-center rounded-2xl border border-stone-200/80 bg-white px-6 py-3 text-sm font-semibold text-stone-700 shadow-sm transition hover:bg-stone-50 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:border-stone-700 dark:bg-stone-800/80 dark:text-stone-200 dark:hover:bg-stone-700 dark:focus:ring-offset-stone-900 sm:mr-3 sm:mt-0 sm:w-auto" @click="selectedUser = null">{{ labels.cancel }}</button></div></div></div></div>
        </div>
    </AppLayout>
</template>
