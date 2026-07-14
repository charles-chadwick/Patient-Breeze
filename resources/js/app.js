import './bootstrap';
import { createApp, h } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from 'ziggy-js';
import { i18nVue } from 'laravel-vue-i18n';
import { applyTheme } from './theme';
import { useErrorModal } from '@/composables/useErrorModal';

// HTTP error statuses surfaced as an in-app modal on client-side visits.
const ERROR_MODAL_STATUSES = [403, 404, 500, 503];

// Surface HTTP errors as the in-app ErrorModal instead of navigating to the
// full-page ErrorPage. Cancelling the event keeps the user on their current
// page; the full page still renders on non-Inertia first loads, where no app
// is mounted yet to host a modal.
router.on('httpException', (event) => {
    const status = event.detail.response?.status;

    if (!ERROR_MODAL_STATUSES.includes(status)) {
        return;
    }

    event.preventDefault();
    useErrorModal().show(status);
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
