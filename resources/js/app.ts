import { createApp, h, type DefineComponent } from 'vue'
import { createInertiaApp, Link } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'

import '../css/app.css'

// CACHE-BUST: admin dashboard redesign rollout (remove after confirmed)
const __BUILD_ID__ = 'admin-vuestic-2026-08-21'

createInertiaApp({
    title: (title) => (title ? `${title} — Recruivo` : `Recruivo ${__BUILD_ID__}`),
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
        app.use(plugin)
        app.component('Link', Link)
        app.mount(el)
    },
    progress: {
        color: '#f59e0b',
        showSpinner: true,
    },
})