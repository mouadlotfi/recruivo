import { nextTick, onBeforeUnmount, watch, type Ref } from 'vue'

/**
 * The only thing the trap needs from the element that opened a dialog, and
 * from the dialog itself. Typed structurally, like `useDismiss`: SFC template
 * refs are checked against a different DOM lib than this plain-TS module sees,
 * and the two `HTMLElement` declarations are not interchangeable.
 */
export interface FocusableElement {
    focus(): void
}

export interface FocusTrapRoot {
    querySelectorAll(selectors: string): ArrayLike<FocusableElement>
    contains(node: unknown): boolean
}

export interface FocusTrapOptions {
    /** Freeze `document.body` scrolling while the dialog is open. */
    lockScroll?: boolean
    /** Hand focus back to the element that opened the dialog when it closes. */
    restoreFocus?: boolean
    /**
     * The element that opened the dialog, when it is not the focused one:
     * Safari does not focus a clicked button, so `document.activeElement` alone
     * would hand focus back to the page body there.
     */
    trigger?: () => FocusableElement | null
}

const FOCUSABLE = 'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'

/**
 * Focus management for the hand-rolled dialogs: cycles Tab inside `root`,
 * closes on Escape, locks body scrolling while open and restores focus when it
 * closes.
 *
 * `onKeydown` is not installed here: call sites attach it where their own key
 * handling lived, because that scope is part of each dialog's behaviour (the
 * delete dialogs close on Escape only from inside, the others listen on the
 * window).
 */
export function useFocusTrap(open: Ref<boolean>, root: Ref<FocusTrapRoot | null>, options: FocusTrapOptions = {}) {
    const { lockScroll = true, restoreFocus = true } = options

    let openedFrom: FocusableElement | null = null
    let previousBodyOverflow = ''

    const focusables = () => Array.from(root.value?.querySelectorAll(FOCUSABLE) ?? [])

    const onKeydown = (event: KeyboardEvent) => {
        if (!open.value) return

        if (event.key === 'Escape') {
            event.preventDefault()
            open.value = false
            return
        }

        if (event.key !== 'Tab') return

        const elements = focusables()
        if (elements.length === 0) {
            event.preventDefault()
            return
        }

        const first = elements[0]
        const last = elements[elements.length - 1]
        // The focused element under this module's DOM lib: it is the same
        // runtime element the dialog refs see.
        const active: FocusableElement | null = document.activeElement instanceof HTMLElement ? document.activeElement : null
        const escaped = !root.value?.contains(active)

        if (event.shiftKey && (escaped || active === first)) {
            event.preventDefault()
            last.focus()
        } else if (!event.shiftKey && (escaped || active === last)) {
            event.preventDefault()
            first.focus()
        }
    }

    watch(
        open,
        (isOpen, wasOpen) => {
            if (isOpen) {
                openedFrom = options.trigger?.() ?? (document.activeElement instanceof HTMLElement ? document.activeElement : null)

                if (lockScroll) {
                    previousBodyOverflow = document.body.style.overflow
                    document.body.style.overflow = 'hidden'
                }

                return
            }

            if (!wasOpen) return

            if (lockScroll) document.body.style.overflow = previousBodyOverflow

            const element = openedFrom
            openedFrom = null
            if (restoreFocus && element) nextTick(() => element.focus())
        },
        { immediate: true },
    )

    onBeforeUnmount(() => {
        if (lockScroll && open.value) document.body.style.overflow = previousBodyOverflow
    })

    return { onKeydown, focusables }
}
