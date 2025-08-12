import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { ZiggyVue } from 'ziggy-js';
import { initializeTheme } from './composables/useAppearance';
import Toast, { POSITION } from 'vue-toastification';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) => resolvePageComponent(`./pages/${name}.vue`, import.meta.glob<DefineComponent>('./pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(Toast,{
                position: POSITION.TOP_LEFT,
                timeout: 3000,
                closeOnClick: true,
                pauseOnHover: true,
                draggable: true,
                draggablePercent: 60,
                showCloseButtonOnHover: false,
                hideProgressBar: false,
                icon: true,
            })
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();

// Register service worker using Vite PWA helper (handles dev/prod paths)
if (typeof window !== 'undefined' && 'serviceWorker' in navigator) {
    // Lazy import to avoid SSR/tooling issues
    import('virtual:pwa-register').then(({ registerSW }) => {
        registerSW({ immediate: true });
    }).catch(() => {
        // no-op if plugin not available in certain environments
    });
}
