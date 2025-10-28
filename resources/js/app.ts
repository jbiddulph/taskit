import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { initializeTheme } from './composables/useAppearance';
import { router } from '@inertiajs/vue3';
import axios from 'axios';

// Global axios configuration for Sanctum/CSRF
axios.defaults.withCredentials = true;
axios.defaults.xsrfCookieName = 'XSRF-TOKEN';
axios.defaults.xsrfHeaderName = 'X-XSRF-TOKEN';
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.baseURL = '/api';

// Prefetch CSRF cookie once per session (ignore errors)
// Use absolute URL so axios baseURL ('/api') is not prefixed
axios.get(`${window.location.origin}/sanctum/csrf-cookie`, { withCredentials: true }).catch(() => {});

const appName = import.meta.env.VITE_APP_NAME || 'ZapTask';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) => resolvePageComponent(`./pages/${name}.vue`, import.meta.glob<DefineComponent>('./pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();

// Google Analytics page tracking for Inertia navigation
declare global {
    interface Window {
        gtag: (...args: any[]) => void;
    }
}

// Track page views on Inertia navigation
router.on('navigate', () => {
    if (typeof window !== 'undefined' && window.gtag) {
        window.gtag('config', window.gtag, {
            page_title: document.title,
            page_location: window.location.href,
        });
    }
});
