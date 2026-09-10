import { ref } from 'vue'

/**
 * Cookie-consent state, shared by the banner and the footer control.
 *
 * The choice is stored in a plain cookie the server can read, because the one
 * thing consent actually gates - the Google Fonts stylesheet, which discloses the
 * visitor's IP address to Google - is rendered by the shell, not by JavaScript.
 */
export const CONSENT_COOKIE = 'recruivo:cookie_consent'

export type ConsentValue = 'accepted' | 'necessary'

/** Whether the banner should be visible (no stored choice, or reopened). */
export const consentOpen = ref(false)

export function readConsent(): ConsentValue | null {
    const match = document.cookie.match(new RegExp(`(?:^|; )${CONSENT_COOKIE}=([^;]*)`))
    const value = match?.[1]

    return value === 'accepted' || value === 'necessary' ? value : null
}

export function storeConsent(value: ConsentValue): void {
    document.cookie = `${CONSENT_COOKIE}=${value}; path=/; max-age=31536000; SameSite=Lax`
    consentOpen.value = false
}

/**
 * Withdrawing consent must be as easy as giving it: drop the stored choice and
 * ask again.
 */
export function openConsentSettings(): void {
    document.cookie = `${CONSENT_COOKIE}=; path=/; max-age=0; SameSite=Lax`
    consentOpen.value = true
}
