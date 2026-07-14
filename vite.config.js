/// <reference types="vitest" />
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    test: {
        environment: 'jsdom',
        globals: true,
        include: ['resources/js/__tests__/**/*.test.js'],
        setupFiles: ['resources/js/__tests__/setup.js'],
    },
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/spa/main.js', 'resources/js/public-profile.js', 'resources/js/public-campaign.js', 'resources/js/portal/main.js'],
            refresh: true,
        }),
        vue(),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
