import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite'

export default defineConfig({
    plugins: [
        tailwindcss(),
        laravel([
            'resources/js/app.js',
            'resources/css/app.css',
        ]),
    ],
});
