import { createRouter, createWebHashHistory } from 'vue-router';
import { routeLoader } from './routeLoader';

const DashboardPage = () => import('./pages/DashboardPage.vue');
const UsersPage = () => import('./pages/UsersPage.vue');
const UserFormPage = () => import('./pages/UserFormPage.vue');
const MembersPage = () => import('./pages/MembersPage.vue');
const MemberViewPage = () => import('./pages/MemberViewPage.vue');
const MemberFormPage = () => import('./pages/MemberFormPage.vue');
const RolesPage = () => import('./pages/RolesPage.vue');
const RoleCreatePage = () => import('./pages/RoleCreatePage.vue');
const RoleEditPage = () => import('./pages/RoleEditPage.vue');
const InventoryPage = () => import('./pages/InventoryPage.vue');
const InventoryProductFormPage = () => import('./pages/InventoryProductFormPage.vue');
const InventoryStockFormPage = () => import('./pages/InventoryStockFormPage.vue');
const AccountsPage = () => import('./pages/AccountsPage.vue');
const AccountFormPage = () => import('./pages/AccountFormPage.vue');
const AccountTransferFormPage = () => import('./pages/AccountTransferFormPage.vue');
const ExpenseFormPage = () => import('./pages/ExpenseFormPage.vue');
const SalesPage = () => import('./pages/SalesPage.vue');
const SalesFormPage = () => import('./pages/SalesFormPage.vue');
const StatsPage = () => import('./pages/StatsPage.vue');
const ReportsPage = () => import('./pages/ReportsPage.vue');
const SettingsPage = () => import('./pages/SettingsPage.vue');
const ProfilePage = () => import('./pages/ProfilePage.vue');
const WorkoutPage = () => import('./pages/WorkoutPage.vue');
const DietPage = () => import('./pages/DietPage.vue');
const PaymentsPage = () => import('./pages/PaymentsPage.vue');
const AttendancePage = () => import('./pages/AttendancePage.vue');

const routes = [
    { path: '/', redirect: '/dashboard' },
    { path: '/dashboard', component: DashboardPage, meta: { title: 'Dashboard' } },
    { path: '/users', component: UsersPage, meta: { title: 'Users' } },
    { path: '/users/new', component: UserFormPage, meta: { title: 'New User' } },
    { path: '/users/:id/edit', component: UserFormPage, meta: { title: 'Edit User' } },
    { path: '/members', component: MembersPage, meta: { title: 'Members' } },
    { path: '/members/new', component: MemberFormPage, meta: { title: 'New Member' } },
    { path: '/members/:id/edit', component: MemberFormPage, meta: { title: 'Edit Member' } },
    { path: '/members/:id', component: MemberViewPage, meta: { title: 'Member Profile' } },
    { path: '/roles', component: RolesPage, meta: { title: 'Roles' } },
    { path: '/roles/new', component: RoleCreatePage, meta: { title: 'New Role' } },
    { path: '/roles/:id/edit', component: RoleEditPage, meta: { title: 'Edit Role' } },
    { path: '/inventory', component: InventoryPage, meta: { title: 'Inventory' } },
    { path: '/inventory/products/new', component: InventoryProductFormPage, meta: { title: 'New Product' } },
    { path: '/inventory/products/:id/edit', component: InventoryProductFormPage, meta: { title: 'Edit Product' } },
    { path: '/inventory/stock/new', component: InventoryStockFormPage, meta: { title: 'Add Stock' } },
    { path: '/inventory/stock/:id/edit', component: InventoryStockFormPage, meta: { title: 'Edit Stock Entry' } },
    { path: '/accounts', component: AccountsPage, meta: { title: 'Accounts' } },
    { path: '/accounts/new', component: AccountFormPage, meta: { title: 'New Account' } },
    { path: '/accounts/:id/edit', component: AccountFormPage, meta: { title: 'Edit Account' } },
    { path: '/accounts/transfers/new', component: AccountTransferFormPage, meta: { title: 'New Transfer' } },
    { path: '/accounts/transfers/:id/edit', component: AccountTransferFormPage, meta: { title: 'Edit Transfer' } },
    { path: '/accounts/expenses/new', component: ExpenseFormPage, meta: { title: 'New Expense' } },
    { path: '/accounts/expenses/:id/edit', component: ExpenseFormPage, meta: { title: 'Edit Expense' } },
    { path: '/sales', component: SalesPage, meta: { title: 'Sales' } },
    { path: '/sales/new', component: SalesFormPage, meta: { title: 'New Sale' } },
    { path: '/sales/:id/edit', component: SalesFormPage, meta: { title: 'Edit Sale' } },
    { path: '/stats', component: StatsPage, meta: { title: 'Sales Stats' } },
    { path: '/reports', component: ReportsPage, meta: { title: 'Reports' } },
    { path: '/settings', component: SettingsPage, meta: { title: 'Settings' } },
    { path: '/profile', component: ProfilePage, meta: { title: 'My Profile' } },
    { path: '/workout', component: WorkoutPage, meta: { title: 'Workout' } },
    { path: '/diet', component: DietPage, meta: { title: 'Diet' } },
    { path: '/payments', component: PaymentsPage, meta: { title: 'Payments' } },
    { path: '/attendance', component: AttendancePage, meta: { title: 'Attendance' } },
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
