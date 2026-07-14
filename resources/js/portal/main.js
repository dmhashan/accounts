import { createApp } from 'vue';
import App from './App.vue';
import router from './router';
import { apiRequest } from './composables/usePortalApi';

function parseContext(value) {
    try {
        return value ? JSON.parse(value) : {};
    } catch {
        return {};
    }
}

const root = document.getElementById('portal-root');

if (root) {
    const context = parseContext(root.dataset.context);
    const app = createApp(App);

    // Sync auth status with API
    apiRequest('/auth/me')
        .then((authStatus) => {
            const finalContext = {
                ...context,
                authenticated: authStatus.authenticated,
                user: authStatus.user,
            };
            window.portalContext = finalContext;
            app.provide('portalContext', finalContext);
            app.use(router);
            app.mount(root);
        })
        .catch(() => {
            const finalContext = {
                ...context,
                authenticated: false,
                user: null,
            };
            window.portalContext = finalContext;
            app.provide('portalContext', finalContext);
            app.use(router);
            app.mount(root);
        });
}
