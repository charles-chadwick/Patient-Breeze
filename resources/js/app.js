import './bootstrap';
import { createApp, h } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from 'ziggy-js';
import { i18nVue, trans } from 'laravel-vue-i18n';
import { applyTheme } from './theme';
import { useAuthorizationModal } from '@/composables/useAuthorizationModal';

const HTTP_FORBIDDEN = 403;

// Surface authorization denials as the in-app AuthorizationModal instead of
// navigating to the full-page ErrorPage. Cancelling the event keeps the user
// on the current page; the full page still renders on non-Inertia first loads.
router.on('httpException', (event) => {
    if (event.detail.response?.status !== HTTP_FORBIDDEN) {
        return;
    }

    event.preventDefault();
    useAuthorizationModal().showDenied(trans('errors.unauthorized.description'));
});

createInertiaApp({
    title: (title) => `${title} - ${import.meta.env.VITE_APP_NAME}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        applyTheme(props.initialPage.props.theme ?? 'System');

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
