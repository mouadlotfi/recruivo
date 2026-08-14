import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Alpine.js
import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';
import { profileCollection } from './profile-builder';

Alpine.plugin(focus);
Alpine.data('profileCollection', profileCollection);
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

