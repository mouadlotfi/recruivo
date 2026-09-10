import { ref } from 'vue'

/**
 * Cookie-notice state, shared by the banner and the shell. Only functional
 * cookies are used (session, language, theme) and the fonts are self-hosted, so
 * there is nothing to opt out of: the cookie records that the notice was seen.
 */
export const CONSENT_COOKIE = 'recruivo:cookie_consent'

/** Whether the banner should be visible (notice not acknowledged yet). */
export const consentOpen = ref(false)

export function openNoticeIfUnacknowledged(): void {
    if (!document.cookie.match(new RegExp(`(?:^|; )${CONSENT_COOKIE}=accepted`))) {
        consentOpen.value = true
    }
}

export function acknowledgeNotice(): void {
    document.cookie = `${CONSENT_COOKIE}=accepted; path=/; max-age=31536000; SameSite=Lax`
    consentOpen.value = false
}
