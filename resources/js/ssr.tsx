import { createInertiaApp } from '@inertiajs/react';
import createServer from '@inertiajs/react/server';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import ReactDOMServer from 'react-dom/server';
import { type RouteName, route as routeFn } from 'ziggy-js';

const appName = import.meta.env.VITE_APP_NAME || 'Глобал Карго Спед';

createServer((page) =>
    createInertiaApp({
        page,
        render: ReactDOMServer.renderToString,
        title: (title) => (title ? `${title}` : appName),
        resolve: (name) => resolvePageComponent(`./pages/${name}.tsx`, import.meta.glob('./pages/**/*.tsx')),
        setup: ({ App, props }) => {
            /* eslint-disable @typescript-eslint/no-explicit-any */
            // The auth and settings screens call the global route() helper that
            // @routes normally defines in the browser. On the server it has to be
            // wired to the Ziggy payload shared by HandleInertiaRequests, or those
            // pages throw during render.
            (globalThis as any).route = (name: RouteName, params?: any, absolute?: boolean) =>
                routeFn(name, params, absolute, {
                    ...(page.props as any).ziggy,
                    location: new URL((page.props as any).ziggy.location),
                });

            return <App {...props} />;
        },
    }),
);
