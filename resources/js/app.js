import './bootstrap';
import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from 'ziggy-js';
import { i18nVue } from 'laravel-vue-i18n';

createInertiaApp({
    title: (title) => `${title} - ${import.meta.env.VITE_APP_NAME}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(i18nVue, {
                lang: props.initialPage.props.locale,
                resolve: async (lang) => {
                    const langs = import.meta.glob('../../lang/*.json');
                    const loader = langs[`../../lang/${lang}.json`];

                    // Only PHP translations exist (compiled to php_{lang}.json),
                    // so a plain {lang}.json lookup returns undefined — resolve
                    // to an empty set instead of crashing.
                    return loader ? await loader() : {};
                },
            })
            .mount(el);
    },
    progress: {
        color: '#0082aa',
    },
});
