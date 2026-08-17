import { onBeforeUnmount, onMounted, type Ref } from 'vue'

/**
 * Closes a dropdown when clicking outside `root` or pressing Escape.
 * Ports the Alpine @click.away + @keydown.escape.window behaviour from the Blade partials.
 */
export function useDismiss(open: Ref<boolean>, root: Ref<HTMLElement | null>) {
    const onPointerDown = (event: MouseEvent) => {
        if (open.value && root.value && !root.value.contains(event.target as Node)) {
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
