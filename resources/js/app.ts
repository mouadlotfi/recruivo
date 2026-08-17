import { createApp, h } from 'vue'
import { createInertiaApp, Link } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'

import '../css/app.css'

createInertiaApp({
    title: (title) => (title ? `${title} — Recruivo` : 'Recruivo'),
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ) as unknown as Promise<import('vue').DefineComponent>,
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
