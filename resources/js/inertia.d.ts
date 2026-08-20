/// <reference types="vite/client" />

import type { Flash, User } from './types'

/**
 * Global Inertia type augmentation.
 *
 * The props shared on every page by `HandleInertiaRequests` are declared once
 * here through the official `InertiaConfig` module augmentation, so
 * `usePage()` and `Page['props']` gain meaningful global safety without each
 * page manually extending a local `PageProps` interface.
 */
declare module '@inertiajs/core' {
    interface InertiaConfig {
        sharedPageProps: {
            auth: {
                user: User | null
            }
            locale: string
            supportedLocales: string[]
            translations: Record<string, Record<string, string>>
            flash: Flash
            notificationCount: number
        }
    }
}