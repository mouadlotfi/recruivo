import { computed, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import type { Pagination } from '../types'

/**
 * Drives a "load more" button over an Inertia paginator: requests the next
 * page, tracks the request in flight and reports a failed page so the button
 * can explain itself. Labels and empty states belong to the call site.
 *
 * `only` narrows the partial reload for pages that paginate more than one
 * list, so a "load more" on one list does not re-send the other's props.
 */
export function useLoadMore(paginator: () => Pagination, only: string[] = []) {
    const hasMore = computed(() => paginator().next_page_url !== null)
    const loadingMore = ref(false)
    const loadMoreFailed = ref(false)

    const loadMore = () => {
        const url = paginator().next_page_url
        if (!url || loadingMore.value) return

        loadingMore.value = true
        loadMoreFailed.value = false
        router.get(url, {}, {
            ...(only.length ? { only } : {}),
            preserveState: true,
            preserveScroll: true,
            onError: () => {
                loadMoreFailed.value = true
            },
            onFinish: () => {
                loadingMore.value = false
            },
        })
    }

    return { hasMore, loadingMore, loadMoreFailed, loadMore }
}
