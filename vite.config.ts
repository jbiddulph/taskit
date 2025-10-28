import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';

export default defineConfig({
    base: '',
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
        // Make Wayfinder non-blocking in production builds by disabling generation
        ...(process.env.NODE_ENV === 'production'
            ? []
            : [wayfinder({ formVariants: true })]
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
