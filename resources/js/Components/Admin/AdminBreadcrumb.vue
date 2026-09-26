<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3'
import type { PageProps } from '../../types'

type Crumb = {
    label: string
    href?: string
}

const props = defineProps<{
    items: Crumb[]
}>()

const page = usePage<PageProps>()
const locale = page.props.locale

const home: Crumb = { label: 'Overview', href: `/${locale}/admin/dashboard` }
const trail = [home, ...props.items]
</script>

<template>
    <nav aria-label="Breadcrumb" class="flex items-center gap-1.5 text-sm text-stone-500 dark:text-stone-400">
        <template v-for="(crumb, index) in trail" :key="index">
            <span v-if="index > 0" class="text-stone-300 dark:text-stone-600" aria-hidden="true">/</span>
            <Link
                v-if="crumb.href && index < trail.length - 1"
                :href="crumb.href"
                class="rounded font-medium text-stone-600 transition hover:text-amber-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 dark:text-stone-300 dark:hover:text-amber-300"
            >
                {{ crumb.label }}
            </Link>
            <span
                v-else
                :aria-current="index === trail.length - 1 ? 'page' : undefined"
                class="font-medium text-stone-900 dark:text-white"
            >
                {{ crumb.label }}
            </span>
        </template>
    </nav>
</template>
