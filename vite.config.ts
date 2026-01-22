import tailwindcss from '@tailwindcss/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { fileURLToPath, URL } from 'node:url';
import { defineConfig } from 'vite';

export default defineConfig({
    base: '',
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
        },
    },
    plugins: [
        laravel({
            input: ['resources/js/app.ts'],
            ssr: 'resources/js/ssr.ts',
            refresh: true,
            buildDirectory: 'build',
            publicDirectory: 'public',
            usePolling: false,
            hotFile: 'hot',
        }),
        tailwindcss(),
        // Enable Wayfinder in development only to avoid build-time artisan dependency in production
        ...(process.env.NODE_ENV !== 'production'
            ? [wayfinder({ formVariants: true })]
            : []
        ),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
});
