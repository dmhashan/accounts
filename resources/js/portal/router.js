import { createRouter, createWebHashHistory } from 'vue-router';
import LoginPage from './pages/LoginPage.vue';
import DashboardPage from './pages/DashboardPage.vue';
import TenantsPage from './pages/TenantsPage.vue';
import PortalUsersPage from './pages/PortalUsersPage.vue';
import TenantViewPage from './pages/TenantViewPage.vue';

const routes = [
    {
        path: '/login',
        name: 'Login',
        component: LoginPage,
        meta: { guest: true, title: 'Portal Login' },
    },
    {
        path: '/',
        redirect: '/dashboard',
    },
    {
        path: '/dashboard',
        name: 'Dashboard',
        component: DashboardPage,
        meta: { auth: true, title: 'Dashboard' },
    },
    {
        path: '/tenants',
        name: 'Tenants',
        component: TenantsPage,
        meta: { auth: true, title: 'Tenant Management' },
    },
    {
        path: '/tenants/:subdomain',
        name: 'TenantView',
        component: TenantViewPage,
        meta: { auth: true, title: 'Tenant Details' },
    },
    {
        path: '/portal-users',
        name: 'PortalUsers',
        component: PortalUsersPage,
        meta: { auth: true, title: 'Portal Administrators' },
    },
];

const router = createRouter({
    history: createWebHashHistory(),
    routes,
});

router.beforeEach((to, from, next) => {
    const context = window.portalContext || { authenticated: false };

    if (to.meta.auth && !context.authenticated) {
        next('/login');
    } else if (to.meta.guest && context.authenticated) {
        next('/dashboard');
    } else {
        next();
    }
});

// Update document title
router.afterEach((to) => {
    document.title = to.meta.title ? `${to.meta.title} - SaaS Portal` : 'SaaS Administration Portal';
});

export default router;
