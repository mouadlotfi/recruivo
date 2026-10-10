<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3'
import type { PageProps } from '../../types'

defineProps<{
    label: string
}>()

const page = usePage<PageProps>()
</script>

<template>
    <nav aria-label="Breadcrumb" class="flex items-center gap-1.5 text-sm text-stone-500 dark:text-stone-400">
        <Link
            :href="`/${page.props.locale}/admin/dashboard`"
            class="rounded font-medium text-stone-600 transition hover:text-amber-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 dark:text-stone-300 dark:hover:text-amber-300"
        >
            Overview
        </Link>
        <span class="text-stone-300 dark:text-stone-600" aria-hidden="true">/</span>
        <span aria-current="page" class="font-medium text-stone-900 dark:text-white">
            {{ label }}
        </span>
    </nav>
</template>
