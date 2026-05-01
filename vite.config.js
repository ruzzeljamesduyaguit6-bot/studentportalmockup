import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/views.css',
                'resources/css/login.css',
                'resources/js/app.js',
                'resources/js/catalog-management-loader.js',
                'resources/js/user-management-loader.js',
                'resources/js/messages-loader.js',
                'resources/js/profile-loader.js',
                'resources/js/notifications-loader.js',
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
});
