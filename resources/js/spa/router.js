import { createRouter, createWebHashHistory } from 'vue-router';
import { routeLoader } from './routeLoader';

const DashboardPage = () => import('./pages/DashboardPage.vue');
const UsersPage = () => import('./pages/UsersPage.vue');
const UserFormPage = () => import('./pages/UserFormPage.vue');
const MembersPage = () => import('./pages/MembersPage.vue');
const MemberFormPage = () => import('./pages/MemberFormPage.vue');
const MemberDetailsPage = () => import('./pages/MemberDetailsPage.vue');
const RolesPage = () => import('./pages/RolesPage.vue');
const RoleCreatePage = () => import('./pages/RoleCreatePage.vue');
const RoleEditPage = () => import('./pages/RoleEditPage.vue');
const InventoryPage = () => import('./pages/InventoryPage.vue');
const InventoryProductFormPage = () => import('./pages/InventoryProductFormPage.vue');
const InventoryStockFormPage = () => import('./pages/InventoryStockFormPage.vue');
const SalesPage = () => import('./pages/SalesPage.vue');
const SalesFormPage = () => import('./pages/SalesFormPage.vue');
const CompanyAccountsPage = () => import('./pages/CompanyAccountsPage.vue');
const ReportsPage = () => import('./pages/ReportsPage.vue');
const SettingsPage = () => import('./pages/SettingsPage.vue');
const ProfilePage = () => import('./pages/ProfilePage.vue');
const WorkoutPage = () => import('./pages/WorkoutPage.vue');
const DietPage = () => import('./pages/DietPage.vue');
const PaymentsPage = () => import('./pages/PaymentsPage.vue');
const AttendancePage = () => import('./pages/AttendancePage.vue');

const routes = [
    { path: '/', redirect: '/dashboard' },
    { path: '/dashboard', component: DashboardPage },
    { path: '/users', component: UsersPage },
    { path: '/users/new', component: UserFormPage },
    { path: '/users/:id/edit', component: UserFormPage },
    { path: '/members', component: MembersPage },
    { path: '/members/new', component: MemberFormPage },
    { path: '/members/:id/edit', component: MemberFormPage },
    { path: '/members/:id', component: MemberDetailsPage },
    { path: '/roles', component: RolesPage },
    { path: '/roles/new', component: RoleCreatePage },
    { path: '/roles/:id/edit', component: RoleEditPage },
    { path: '/inventory', component: InventoryPage },
    { path: '/inventory/products/new', component: InventoryProductFormPage },
    { path: '/inventory/products/:id/edit', component: InventoryProductFormPage },
    { path: '/inventory/stock/new', component: InventoryStockFormPage },
    { path: '/inventory/stock/:id/edit', component: InventoryStockFormPage },
    { path: '/sales', component: SalesPage },
    { path: '/sales/new', component: SalesFormPage },
    { path: '/company-accounts', component: CompanyAccountsPage },
    { path: '/CompanyAccounts', redirect: '/company-accounts' },
    { path: '/finance', redirect: '/company-accounts' },
    { path: '/reports', component: ReportsPage },
    { path: '/settings', component: SettingsPage },
    { path: '/profile', component: ProfilePage },
    { path: '/workout', component: WorkoutPage },
    { path: '/diet', component: DietPage },
    { path: '/payments', component: PaymentsPage },
    { path: '/attendance', component: AttendancePage },
];

const router = createRouter({
    history: createWebHashHistory(),
    routes,
});

router.beforeEach((to, from, next) => {
    if (to.path !== from.path) {
        routeLoader.loading = true;
    }
    next();
});

router.afterEach(() => {
    routeLoader.loading = false;
});

export default router;
