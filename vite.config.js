import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { resolve } from 'node:path';
import { defineConfig } from 'vite';
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            ssr: 'resources/js/ssr.tsx',
            refresh: true,
        }),
        react(),
        tailwindcss(),
    ],
    resolve: {
        alias: {
            // ziggy-js is not an npm dependency here — app.tsx only imports it
            // for its types, which get erased at compile time. The SSR entry
            // uses route() as a real value, so it is aliased to the copy that
            // ships with the tightenco/ziggy composer package, keeping the JS
            // and PHP sides on the same version by construction.
            'ziggy-js': resolve(__dirname, 'vendor/tightenco/ziggy'),
        },
    },
    esbuild: {
        jsx: 'automatic',
    },
});
