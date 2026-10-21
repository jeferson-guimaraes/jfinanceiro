import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { initializeTheme } from './composables/useAppearance';
import { montarTitulo, nomeDaAplicacao } from './lib/tituloDaPagina';

/**
 * Preenchido no setup com o nome enviado pelo servidor, antes de a primeira página montar.
 */
let appName = nomeDaAplicacao(undefined);

createInertiaApp({
    title: (title) => montarTitulo(title, appName),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        appName = nomeDaAplicacao(props.initialPage.props);

        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();
