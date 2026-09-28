import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/passkeys.js',
                'resources/css/site.css',
                'resources/js/site.js',
            ],
            refresh: true,
            fonts: [
                // Self-hosted so the public site never blocks render on a
                // third-party stylesheet. Only the two faces that paint above
                // the fold are preloaded — preloading all seven starved the
                // hero image of bandwidth and pushed LCP out by 1.6s.
                bunny('Fraunces', {
                    weights: [300, 400, 500],
                    preload: [{ weight: 400, style: 'normal' }],
                }),
                bunny('Inter', {
                    weights: [400, 500, 600, 700],
                    preload: [{ weight: 400, style: 'normal' }],
                }),
                bunny('Instrument Sans', { weights: [400, 500, 600] }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
