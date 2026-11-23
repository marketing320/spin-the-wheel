import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/landing.js',
                'resources/js/spin/spin-page.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
    build: {
        // Three.js is intentionally isolated and is ~515 kB before gzip.
        chunkSizeWarningLimit: 550,
        rolldownOptions: {
            output: {
                codeSplitting: {
                    groups: [
                        {
                            name: 'vendor-three',
                            test: /[\\/]node_modules[\\/]three[\\/]/,
                        },
                        {
                            name: 'vendor-matter',
                            test: /[\\/]node_modules[\\/]matter-js[\\/]/,
                        },
                        {
                            name: 'vendor-confetti',
                            test: /[\\/]node_modules[\\/]canvas-confetti[\\/]/,
                        },
                        {
                            name: 'vendor-alpine',
                            test: /[\\/]node_modules[\\/]alpinejs[\\/]/,
                        },
                        {
                            name: 'vendor-swiper',
                            test: /[\\/]node_modules[\\/]swiper[\\/]/,
                        },
                    ],
                },
            },
        },
    },
});
