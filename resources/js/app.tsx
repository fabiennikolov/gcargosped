// Tailwind's app.css is deliberately not imported: its preflight is a global
// reset — h1..h6 lose their font-size, lists lose their markers — which the
// lifted design system never expected. The public pages load only site.css
// (via site-layout), and the Filament panel ships its own stylesheet.
import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot, hydrateRoot } from 'react-dom/client';
import { route as routeFn } from 'ziggy-js';

declare global {
    const route: typeof routeFn;
}

const appName = import.meta.env.VITE_APP_NAME || 'Глобал Карго Спед';

createInertiaApp({
    title: (title) => title || appName,
    resolve: (name) => resolvePageComponent(`./pages/${name}.tsx`, import.meta.glob('./pages/**/*.tsx')),
    setup({ el, App, props }) {
        // The page arrives already rendered by the SSR server. Hydrating keeps
        // that markup — and the text the visitor is already reading — in place
        // instead of throwing it away and drawing everything a second time.
        if (el.hasChildNodes()) {
            hydrateRoot(el, <App {...props} />);

            return;
        }

        createRoot(el).render(<App {...props} />);
    },
    progress: {
        color: '#0E9F6E',
    },
});
