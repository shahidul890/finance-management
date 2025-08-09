import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import { defineConfig } from 'vite';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.ts'],
            ssr: 'resources/js/ssr.ts',
            refresh: true,
        }),
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        VitePWA({
            injectRegister: null,
            registerType: 'autoUpdate',
            workbox: {
                globPatterns: ['**/*.{js,css,html,png,svg,ico,woff2}'],
            },
            devOptions: {
                enabled: true,
            },
            manifest: {
                name: 'Finance',
                short_name: 'Finance',
                description: 'Personal finance manager',
                start_url: '/',
                scope: '/',
                display: 'standalone',
                theme_color: '#ffffff',
                background_color: '#ffffff',
                icons: [
                    {
                        src: '/favicon.svg',
                        sizes: 'any',
                        type: 'image/svg+xml',
                        purpose: 'any',
                    },
                    {
                        src: '/apple-touch-icon.png',
                        sizes: '180x180',
                        type: 'image/png',
                        purpose: 'any',
                    },
                ],
            },
        }),
    ],
});
