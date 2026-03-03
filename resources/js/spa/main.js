import { createApp } from 'vue';
import App from './App.vue';
import router from './router';
import { apiRequest } from './composables/useApiClient';

function parseContext(value) {
    try {
        return value ? JSON.parse(value) : {};
    } catch {
        return {};
    }
}

const root = document.getElementById('spa-root');

if (root) {
    const context = parseContext(root.dataset.context);
    const app = createApp(App);

    apiRequest('/api/app/context')
        .then((apiContext) => ({ ...context, ...apiContext }))
        .catch(() => context)
        .then((finalContext) => {
            app.provide('appContext', finalContext);
            app.use(router);
            app.mount(root);
        });
}
