import { config } from '@vue/test-utils';

// Silence Vue's "Failed to resolve component: RouterLink" warning globally.
// RouterLink is a vue-router component; our mocks provide it, but Vue's
// component resolution logs a warning before the stub is applied.
config.global.config = {
    warnHandler(msg) {
        if (msg.includes('Failed to resolve component: RouterLink')) return;
        console.warn('[Vue warn]:', msg);
    },
};
