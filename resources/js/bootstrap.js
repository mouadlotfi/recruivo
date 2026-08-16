import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Alpine.js
import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';
import { profileCollection } from './profile-builder';

Alpine.plugin(focus);
Alpine.data('profileCollection', profileCollection);
Alpine.data('expandedTextarea', (fieldId, model) => ({
    isOpen: false,
    draft: '',
    fieldId,
    model,

    // Resolve the value source: an Alpine model on the nearest enclosing
    // x-data scope (skip this component's own root), or a DOM textarea by id.
    source() {
        if (this.model) {
            return this.$el.parentElement?.closest('[x-data]');
        }
        return document.getElementById(this.fieldId);
    },

    read() {
        if (this.model) {
            const scope = this.source();
            return scope ? (Alpine.$data(scope)[this.model] ?? '') : '';
        }
        return this.source()?.value ?? '';
    },

    write(value) {
        if (this.model) {
            const scope = this.source();
            if (scope) Alpine.$data(scope)[this.model] = value;
            return;
        }
        const el = this.source();
        if (el) el.value = value;
    },

    open() {
        this.draft = this.read();
        this.isOpen = true;
    },

    commit() {
        this.write(this.draft);
        this.isOpen = false;
    },

    close() {
        this.isOpen = false;
    },
}));
window.Alpine = Alpine;

document.addEventListener('alpine:init', () => {
    Alpine.directive('autosize', (el) => {
        const resize = () => {
            el.style.height = 'auto';
            el.style.height = el.scrollHeight + 'px';
        };
        el.addEventListener('input', resize);
        resize();
    });
});

Alpine.start();

