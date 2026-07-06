import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/spin/spin-page.js',
                'resources/js/spin/live-view.js',
                'resources/js/admin/redeem-scanner.js',
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
                            name: 'vendor-realtime',
                            test: /[\\/]node_modules[\\/](?:laravel-echo|pusher-js)[\\/]/,
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
                            name: 'vendor-qrcode-scanner',
                            test: /[\\/]node_modules[\\/]html5-qrcode[\\/]/,
                        },
                    ],
                },
            },
        },
    },
});
