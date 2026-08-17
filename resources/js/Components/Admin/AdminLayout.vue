<script setup lang="ts">
import AppLayout from '../../Layouts/AppLayout.vue'
import AdminSidebar from './AdminSidebar.vue'

const props = defineProps<{
    labels: Record<string, string>
}>()
</script>

<template>
    <AppLayout>
        <div class="grid items-start gap-6 lg:grid-cols-[13rem_minmax(0,1fr)] lg:gap-8">
            <AdminSidebar :labels="props.labels" />
            <div class="min-w-0">
                <slot />
            </div>
        </div>
    </AppLayout>
</template>
