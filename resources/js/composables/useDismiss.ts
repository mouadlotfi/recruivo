import { onBeforeUnmount, onMounted, type Ref } from 'vue'

/**
 * Closes a dropdown when clicking outside `root` or pressing Escape.
 * Ports the Alpine @click.away + @keydown.escape.window behaviour from the Blade partials.
 *
 * The root is intentionally typed as a minimal structural contract (only the
 * `contains` method it actually needs) instead of a concrete DOM element
 * type: this keeps the composable usable from SFCs whose template refs are
 * checked against a different DOM lib than this plain-TS module is.
 */
export interface DismissableElement {
    contains(node: unknown): boolean
}

export function useDismiss(open: Ref<boolean>, root: Ref<DismissableElement | null>) {
    const onPointerDown = (event: MouseEvent) => {
        if (open.value && root.value && !root.value.contains(event.target)) {
            open.value = false
        }
    }

    const onKeyDown = (event: KeyboardEvent) => {
        if (event.key === 'Escape') {
            open.value = false
        }
    }

    onMounted(() => {
        document.addEventListener('click', onPointerDown)
        document.addEventListener('keydown', onKeyDown)
    })

    onBeforeUnmount(() => {
        document.removeEventListener('click', onPointerDown)
        document.removeEventListener('keydown', onKeyDown)
    })
}
