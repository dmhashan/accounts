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
const ExpensesPage = () => import('./pages/ExpensesPage.vue');
const ExpenseFormPage = () => import('./pages/ExpenseFormPage.vue');
const SalesPage = () => import('./pages/SalesPage.vue');
const SalesFormPage = () => import('./pages/SalesFormPage.vue');
const StatsPage = () => import('./pages/StatsPage.vue');
const ReportsPage = () => import('./pages/ReportsPage.vue');
const SettingsPage = () => import('./pages/SettingsPage.vue');
const ProfilePage = () => import('./pages/ProfilePage.vue');
const WorkoutPage = () => import('./pages/WorkoutPage.vue');
const WorkoutExerciseFormPage = () => import('./pages/WorkoutExerciseFormPage.vue');
const WorkoutProgramFormPage = () => import('./pages/WorkoutProgramFormPage.vue');
const WorkoutProgramAssignmentFormPage = () => import('./pages/WorkoutProgramAssignmentFormPage.vue');
const WorkoutProgramAssignmentEditPage = () => import('./pages/WorkoutProgramAssignmentEditPage.vue');
const DietPage = () => import('./pages/DietPage.vue');
const PaymentsPage = () => import('./pages/PaymentsPage.vue');
const PaymentFormPage = () => import('./pages/PaymentFormPage.vue');
const AttendancePage = () => import('./pages/AttendancePage.vue');
const NotificationsPage = () => import('./pages/NotificationsPage.vue');
const NotificationFormPage = () => import('./pages/NotificationFormPage.vue');
const NotificationViewPage = () => import('./pages/NotificationViewPage.vue');
const PaymentViewPage = () => import('./pages/PaymentViewPage.vue');
const ExpenseViewPage = () => import('./pages/ExpenseViewPage.vue');
const SaleViewPage = () => import('./pages/SaleViewPage.vue');
const UserViewPage = () => import('./pages/UserViewPage.vue');
const RoleViewPage = () => import('./pages/RoleViewPage.vue');
const InventoryProductViewPage = () => import('./pages/InventoryProductViewPage.vue');
const InventoryStockViewPage = () => import('./pages/InventoryStockViewPage.vue');
const AccountViewPage = () => import('./pages/AccountViewPage.vue');
const AccountTransferViewPage = () => import('./pages/AccountTransferViewPage.vue');
const WorkoutExerciseViewPage = () => import('./pages/WorkoutExerciseViewPage.vue');
const WorkoutProgramViewPage = () => import('./pages/WorkoutProgramViewPage.vue');
const WorkoutAssignmentViewPage = () => import('./pages/WorkoutAssignmentViewPage.vue');
const EventsPage = () => import('./pages/EventsPage.vue');
const EventFormPage = () => import('./pages/EventFormPage.vue');
const EventDetailPage = () => import('./pages/EventDetailPage.vue');
const EventRegistrationsPage = () => import('./pages/EventRegistrationsPage.vue');
const MemberActivityPage = () => import('./pages/MemberActivityPage.vue');
const ReconciliationPage = () => import('./pages/ReconciliationPage.vue');
const ReconciliationFormPage = () => import('./pages/ReconciliationFormPage.vue');
const ReconciliationComparisonPage = () => import('./pages/ReconciliationComparisonPage.vue');
const ReconciliationConfigPage = () => import('./pages/ReconciliationConfigPage.vue');

const routes = [
    { path: '/', redirect: '/dashboard' },
    { path: '/dashboard', component: DashboardPage, meta: { title: 'Dashboard' } },
    { path: '/users', component: UsersPage, meta: { title: 'Users' } },
    { path: '/users/new', component: UserFormPage, meta: { title: 'New User' } },
    { path: '/users/:id', component: UserViewPage, meta: { title: 'User' } },
    { path: '/users/:id/edit', component: UserFormPage, meta: { title: 'Edit User' } },
    { path: '/members', component: MembersPage, meta: { title: 'Members' } },
    { path: '/members/temp', component: MembersPage, meta: { title: 'Temp Members' } },
    { path: '/members/new', component: MemberFormPage, meta: { title: 'New Member' } },
    { path: '/members/:id/edit', component: MemberFormPage, meta: { title: 'Edit Member' } },
    { path: '/members/:id', component: MemberViewPage, meta: { title: 'Member Profile' } },
    { path: '/roles', component: RolesPage, meta: { title: 'Roles' } },
    { path: '/roles/new', component: RoleCreatePage, meta: { title: 'New Role' } },
    { path: '/roles/:id', component: RoleViewPage, meta: { title: 'Role' } },
    { path: '/roles/:id/edit', component: RoleEditPage, meta: { title: 'Edit Role' } },
    { path: '/inventory', component: InventoryPage, meta: { title: 'Inventory' } },
    { path: '/inventory/stock', component: InventoryPage, meta: { title: 'Stock' } },
    { path: '/inventory/display', component: InventoryPage, meta: { title: 'Display' } },
    { path: '/inventory/audit', component: InventoryPage, meta: { title: 'Audit' } },
    { path: '/inventory/products/new', component: InventoryProductFormPage, meta: { title: 'New Product' } },
    { path: '/inventory/products/:id', component: InventoryProductViewPage, meta: { title: 'Product' } },
    { path: '/inventory/products/:id/edit', component: InventoryProductFormPage, meta: { title: 'Edit Product' } },
    { path: '/inventory/stock/new', component: InventoryStockFormPage, meta: { title: 'Add Stock' } },
    { path: '/inventory/stock/:id', component: InventoryStockViewPage, meta: { title: 'Stock Entry' } },
    { path: '/inventory/stock/:id/edit', component: InventoryStockFormPage, meta: { title: 'Edit Stock Entry' } },
    { path: '/accounts', component: AccountsPage, meta: { title: 'Accounts' } },
    { path: '/accounts/transfers', component: AccountsPage, meta: { title: 'Transfers' } },
    { path: '/accounts/transactions', component: AccountsPage, meta: { title: 'Transactions' } },
    { path: '/accounts/new', component: AccountFormPage, meta: { title: 'New Account' } },
    { path: '/accounts/transfers/new', component: AccountTransferFormPage, meta: { title: 'New Transfer' } },
    { path: '/accounts/transfers/:id', component: AccountTransferViewPage, meta: { title: 'Transfer' } },
    { path: '/accounts/transfers/:id/edit', component: AccountTransferFormPage, meta: { title: 'Edit Transfer' } },
    { path: '/accounts/:id', component: AccountViewPage, meta: { title: 'Account' } },
    { path: '/accounts/:id/edit', component: AccountFormPage, meta: { title: 'Edit Account' } },
    { path: '/accounts/expenses/new', component: ExpenseFormPage, meta: { title: 'New Expense' } },
    { path: '/accounts/expenses/:id/edit', component: ExpenseFormPage, meta: { title: 'Edit Expense' } },
    { path: '/expenses', component: ExpensesPage, meta: { title: 'Expenses' } },
    { path: '/expenses/new', component: ExpenseFormPage, meta: { title: 'New Expense' } },
    { path: '/expenses/:id', component: ExpenseViewPage, meta: { title: 'Expense' } },
    { path: '/expenses/:id/edit', component: ExpenseFormPage, meta: { title: 'Edit Expense' } },
    { path: '/sales', component: SalesPage, meta: { title: 'Sales' } },
    { path: '/sales/paid', component: SalesPage, meta: { title: 'Paid Sales' } },
    { path: '/sales/new', component: SalesFormPage, meta: { title: 'New Sale' } },
    { path: '/sales/:id', component: SaleViewPage, meta: { title: 'Sale' } },
    { path: '/sales/:id/edit', component: SalesFormPage, meta: { title: 'Edit Sale' } },
    { path: '/stats', component: StatsPage, meta: { title: 'Sales Stats' } },
    { path: '/reports', component: ReportsPage, meta: { title: 'Reports' } },
    { path: '/reports/customers', component: ReportsPage, meta: { title: 'Customer Reports' } },
    { path: '/reports/products', component: ReportsPage, meta: { title: 'Product Reports' } },
    { path: '/settings', component: SettingsPage, meta: { title: 'Settings' } },
    { path: '/settings/roles', component: SettingsPage, meta: { title: 'Roles Settings' } },
    { path: '/profile', component: ProfilePage, meta: { title: 'My Profile' } },
    { path: '/workout', component: WorkoutPage, meta: { title: 'Workout' } },
    { path: '/workout/exercises', component: WorkoutPage, meta: { title: 'Exercises' } },
    { path: '/workout/exercises/new', component: WorkoutExerciseFormPage, meta: { title: 'New Exercise' } },
    { path: '/workout/exercises/:id', component: WorkoutExerciseViewPage, meta: { title: 'Exercise' } },
    { path: '/workout/exercises/:id/edit', component: WorkoutExerciseFormPage, meta: { title: 'Edit Exercise' } },
    { path: '/workout/programs/new', component: WorkoutProgramFormPage, meta: { title: 'New Workout Program' } },
    { path: '/workout/programs/:id', component: WorkoutProgramViewPage, meta: { title: 'Program' } },
    { path: '/workout/programs/:id/edit', component: WorkoutProgramFormPage, meta: { title: 'Edit Workout Program' } },
    { path: '/workout/assignments', component: WorkoutPage, meta: { title: 'Assignments' } },
    { path: '/workout/assignments/new', component: WorkoutProgramAssignmentFormPage, meta: { title: 'Assign Workout Program' } },
    { path: '/workout/assignments/:id', component: WorkoutAssignmentViewPage, meta: { title: 'Assignment' } },
        { path: '/workout/assignments/:id/edit', component: WorkoutProgramAssignmentEditPage, meta: { title: 'Edit Assignment' } },
    { path: '/diet', component: DietPage, meta: { title: 'Diet' } },
    { path: '/payments', component: PaymentsPage, meta: { title: 'Payments' } },
    { path: '/payments/new', component: PaymentFormPage, meta: { title: 'New Payment' } },
    { path: '/payments/:id', component: PaymentViewPage, meta: { title: 'Payment' } },
    { path: '/payments/:id/edit', component: PaymentFormPage, meta: { title: 'Edit Payment' } },
    { path: '/attendance', component: AttendancePage, meta: { title: 'Attendance' } },
    { path: '/notifications', component: NotificationsPage, meta: { title: 'Notifications' } },
    { path: '/notifications/new', component: NotificationFormPage, meta: { title: 'New Notification' } },
    { path: '/notifications/:id', component: NotificationViewPage, meta: { title: 'Notification' } },
    { path: '/notifications/:id/edit', component: NotificationFormPage, meta: { title: 'Edit Notification' } },
    { path: '/events', component: EventsPage, meta: { title: 'Events' } },
    { path: '/events/new', component: EventFormPage, meta: { title: 'New Event' } },
    { path: '/events/:id', component: EventDetailPage, meta: { title: 'Event' } },
    { path: '/events/:id/edit', component: EventFormPage, meta: { title: 'Edit Event' } },
    { path: '/events/:id/registrations', component: EventRegistrationsPage, meta: { title: 'Event Registrations' } },
    { path: '/activity', component: MemberActivityPage, meta: { title: 'Activity Logs' } },
    { path: '/reconciliation', component: ReconciliationPage, meta: { title: 'Reconciliation' } },
    { path: '/reconciliation/open', component: ReconciliationFormPage, meta: { title: 'Open Reconciliation' } },
    { path: '/reconciliation/close/:id', component: ReconciliationFormPage, meta: { title: 'Close Reconciliation' } },
    { path: '/reconciliation/comparison/:id', component: ReconciliationComparisonPage, meta: { title: 'Reconciliation Comparison' } },
    { path: '/reconciliation/sessions/:id', component: ReconciliationComparisonPage, meta: { title: 'Reconciliation Session' } },
    { path: '/reconciliation/config', component: ReconciliationConfigPage, meta: { title: 'Reconciliation Config' } },
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
