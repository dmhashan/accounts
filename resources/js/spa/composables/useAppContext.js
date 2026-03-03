import { inject } from 'vue';

export function useAppContext() {
    return inject('appContext', {
        user: { name: 'User', email: '' },
        tenant: { name: 'Tenant', domain: '' },
        permissions: {},
        legacyUrls: {},
    });
}
