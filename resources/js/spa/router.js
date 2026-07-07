import { createRouter, createWebHashHistory } from 'vue-router';
import { routeLoader } from './routeLoader';

const DashboardPage = () => import('./pages/DashboardPage.vue');
const ChatBotPage = () => import('./pages/ChatBotPage.vue');
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
const AccountAdjustmentFormPage = () => import('./pages/AccountAdjustmentFormPage.vue');
const ExpensesPage = () => import('./pages/ExpensesPage.vue');
const ExpenseFormPage = () => import('./pages/ExpenseFormPage.vue');
const SalesPage = () => import('./pages/SalesPage.vue');
const SalesFormPage = () => import('./pages/SalesFormPage.vue');
const StatsPage = () => import('./pages/StatsPage.vue');
const ReportsPage = () => import('./pages/ReportsPage.vue');
const DailySummaryPage = () => import('./pages/DailySummaryPage.vue');
const DailySummaryReportsPage = () => import('./pages/DailySummaryReportsPage.vue');
const RealProfitReportPage = () => import('./pages/RealProfitReportPage.vue');
const MemberAnalysisReportPage = () => import('./pages/reports/MemberAnalysisReport.vue');
const SettingsPage = () => import('./pages/SettingsPage.vue');
const GeneralSettingsPage = () => import('./pages/GeneralSettingsPage.vue');
const LegacyToolsPage = () => import('./pages/LegacyToolsPage.vue');
const ConfigurationPage = () => import('./pages/ConfigurationPage.vue');
const BiometricSettingsPage = () => import('./pages/BiometricSettingsPage.vue');
const WorkoutPage = () => import('./pages/WorkoutPage.vue');
const WorkoutExerciseFormPage = () => import('./pages/WorkoutExerciseFormPage.vue');
const WorkoutProgramFormPage = () => import('./pages/WorkoutProgramFormPage.vue');
const WorkoutProgramAssignmentFormPage = () => import('./pages/WorkoutProgramAssignmentFormPage.vue');
const WorkoutProgramAssignmentEditPage = () => import('./pages/WorkoutProgramAssignmentEditPage.vue');
const PaymentsPage = () => import('./pages/PaymentsPage.vue');
const PaymentFormPage = () => import('./pages/PaymentFormPage.vue');
const EmployeeManagementPage = () => import('./pages/EmployeeManagementPage.vue');
const EmployeeFormPage = () => import('./pages/EmployeeFormPage.vue');
const EmployeeViewPage = () => import('./pages/EmployeeViewPage.vue');
const NotificationsPage = () => import('./pages/NotificationsPage.vue');
const NotificationFormPage = () => import('./pages/NotificationFormPage.vue');
const NotificationViewPage = () => import('./pages/NotificationViewPage.vue');
const PaymentViewPage = () => import('./pages/PaymentViewPage.vue');
const ExpenseViewPage = () => import('./pages/ExpenseViewPage.vue');
const SaleViewPage = () => import('./pages/SaleViewPage.vue');
const WalletTopupViewPage = () => import('./pages/WalletTopupViewPage.vue');
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
const VouchersPage = () => import('./pages/VouchersPage.vue');
const VoucherViewPage = () => import('./pages/VoucherViewPage.vue');
const FormsPage = () => import('./pages/FormsPage.vue');
const FormBuilderFormPage = () => import('./pages/FormBuilderFormPage.vue');
const FormSubmissionsPage = () => import('./pages/FormSubmissionsPage.vue');
const CampaignsPage = () => import('./pages/CampaignsPage.vue');
const CampaignFormPage = () => import('./pages/CampaignFormPage.vue');

const routes = [
    { path: '/', redirect: '/dashboard' },
    { path: '/dashboard', component: DashboardPage, meta: { title: 'Dashboard' } },
    { path: '/chatbot', component: ChatBotPage, meta: { title: 'AI Assistant' } },
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
    {
        path: '/accounts',
        redirect: (to) => {
            if (to.query?.tab === 'transfers') return { path: '/accounting/transfers' };
            if (to.query?.tab === 'transactions') return { path: '/accounting/transactions' };
            if (to.query?.tab === 'expenses') return { path: '/accounting/expenses' };
            return { path: '/settings/accounts' };
        },
    },
    { path: '/accounts/transfers', redirect: '/accounting/transfers' },
    { path: '/accounts/transactions', redirect: '/accounting/transactions' },
    { path: '/accounts/new', redirect: '/settings/accounts/new' },
    { path: '/accounts/transfers/new', redirect: '/accounting/transfers/new' },
    { path: '/accounts/transfers/:id(\\d+)', redirect: to => `/accounting/transfers/${to.params.id}` },
    { path: '/accounts/transfers/:id(\\d+)/edit', redirect: to => `/accounting/transfers/${to.params.id}/edit` },
    { path: '/accounts/:id(\\d+)', redirect: to => `/settings/accounts/${to.params.id}` },
    { path: '/accounts/:id(\\d+)/edit', redirect: to => `/settings/accounts/${to.params.id}/edit` },
    { path: '/accounts/expenses/new', redirect: '/accounting/expenses/new' },
    { path: '/accounts/expenses/:id(\\d+)/edit', redirect: to => `/accounting/expenses/${to.params.id}/edit` },
    { path: '/expenses', redirect: '/accounting/expenses' },
    { path: '/expenses/new', redirect: '/accounting/expenses/new' },
    { path: '/expenses/:id(\\d+)', redirect: to => `/accounting/expenses/${to.params.id}` },
    { path: '/expenses/:id(\\d+)/edit', redirect: to => `/accounting/expenses/${to.params.id}/edit` },
    { path: '/sales', component: SalesPage, meta: { title: 'Sales' } },
    { path: '/sales/paid', component: SalesPage, meta: { title: 'Paid Sales' } },
    { path: '/sales/new', component: SalesFormPage, meta: { title: 'New Sale' } },
    { path: '/sales/:id', component: SaleViewPage, meta: { title: 'Sale' } },
    { path: '/sales/:id/edit', component: SalesFormPage, meta: { title: 'Edit Sale' } },
    { path: '/stats', component: StatsPage, meta: { title: 'Sales Stats' } },
    { path: '/reports', component: ReportsPage, meta: { title: 'Reports' } },
    { path: '/reports/real-profit', component: RealProfitReportPage, meta: { title: 'Real Profit' } },
    { path: '/reports/member-analysis', component: MemberAnalysisReportPage, meta: { title: 'Member Analysis' } },
    { path: '/reports/daily-summary', component: DailySummaryPage, meta: { title: 'Daily Summary' } },
    { path: '/reports/daily-summary/history', component: DailySummaryReportsPage, meta: { title: 'Daily Summary Reports' } },
    { path: '/reports/customers', component: ReportsPage, meta: { title: 'Customer Reports' } },
    { path: '/reports/products', component: ReportsPage, meta: { title: 'Product Reports' } },
    { path: '/settings', component: SettingsPage, meta: { title: 'Settings' } },
    { path: '/settings/users', component: SettingsPage, meta: { title: 'Users' } },
    { path: '/settings/general', component: GeneralSettingsPage, meta: { title: 'General Settings' } },
    { path: '/settings/roles', component: SettingsPage, meta: { title: 'Roles Settings' } },
    { path: '/settings/accounts', component: AccountsPage, meta: { title: 'Accounts' } },
    { path: '/settings/accounts/new', component: AccountFormPage, meta: { title: 'New Account' } },
    { path: '/settings/accounts/:id(\\d+)', component: AccountViewPage, meta: { title: 'Account' } },
    { path: '/settings/accounts/:id(\\d+)/edit', component: AccountFormPage, meta: { title: 'Edit Account' } },
    { path: '/settings/payments-plans', component: PaymentsPage, meta: { title: 'Payment Plans' } },
    { path: '/settings/payments-methods', component: PaymentsPage, meta: { title: 'Payment Methods' } },
    { path: '/settings/notifications', component: NotificationsPage, meta: { title: 'Notifications' } },
    { path: '/settings/notifications/new', component: NotificationFormPage, meta: { title: 'New Notification' } },
    { path: '/settings/notifications/:id(\\d+)', component: NotificationViewPage, meta: { title: 'Notification' } },
    { path: '/settings/notifications/:id(\\d+)/edit', component: NotificationFormPage, meta: { title: 'Edit Notification' } },
    { path: '/settings/events', component: EventsPage, meta: { title: 'Events' } },
    { path: '/settings/events/new', component: EventFormPage, meta: { title: 'New Event' } },
    { path: '/settings/events/:id(\\d+)', component: EventDetailPage, meta: { title: 'Event' } },
    { path: '/settings/events/:id(\\d+)/edit', component: EventFormPage, meta: { title: 'Edit Event' } },
    { path: '/settings/events/:id(\\d+)/registrations', component: EventRegistrationsPage, meta: { title: 'Event Registrations' } },
    { path: '/settings/vouchers', component: VouchersPage, meta: { title: 'Vouchers' } },
    { path: '/settings/vouchers/:id(\\d+)', component: VoucherViewPage, meta: { title: 'Voucher' } },
    { path: '/settings/forms', component: FormsPage, meta: { title: 'Form Templates' } },
    { path: '/settings/forms/new', component: FormBuilderFormPage, meta: { title: 'New Form Template' } },
    { path: '/settings/forms/:id(\\d+)/edit', component: FormBuilderFormPage, meta: { title: 'Edit Form Template' } },
    { path: '/settings/forms/:id(\\d+)/submissions', component: FormSubmissionsPage, meta: { title: 'Form Submissions' } },
    { path: '/settings/campaigns', component: CampaignsPage, meta: { title: 'Campaigns' } },
    { path: '/settings/campaigns/new', component: CampaignFormPage, meta: { title: 'New Campaign' } },
    { path: '/settings/campaigns/:id(\\d+)/edit', component: CampaignFormPage, meta: { title: 'Edit Campaign' } },
    { path: '/settings/legacy-tools', component: LegacyToolsPage, meta: { title: 'Manual Commands' } },
    { path: '/settings/configuration', component: ConfigurationPage, meta: { title: 'Configuration' } },
    { path: '/settings/biometric', component: BiometricSettingsPage, meta: { title: 'Biometric Device' } },
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
    { path: '/accounting', redirect: '/accounting/payments' },
    { path: '/accounting/payments', component: PaymentsPage, meta: { title: 'Payments' } },
    { path: '/accounting/payments/new', component: PaymentFormPage, meta: { title: 'New Payment' } },
    { path: '/accounting/payments/:id(\\d+)', component: PaymentViewPage, meta: { title: 'Payment' } },
    { path: '/accounting/payments/:id(\\d+)/edit', component: PaymentFormPage, meta: { title: 'Edit Payment' } },
    { path: '/accounting/expenses', component: ExpensesPage, meta: { title: 'Expenses' } },
    { path: '/accounting/expenses/new', component: ExpenseFormPage, meta: { title: 'New Expense' } },
    { path: '/accounting/expenses/:id(\\d+)', component: ExpenseViewPage, meta: { title: 'Expense' } },
    { path: '/accounting/expenses/:id(\\d+)/edit', component: ExpenseFormPage, meta: { title: 'Edit Expense' } },
    { path: '/accounting/transfers', component: AccountsPage, meta: { title: 'Transfers' } },
    { path: '/accounting/transfers/new', component: AccountTransferFormPage, meta: { title: 'New Transfer' } },
    { path: '/accounting/transfers/:id(\\d+)', component: AccountTransferViewPage, meta: { title: 'Transfer' } },
    { path: '/accounting/transfers/:id(\\d+)/edit', component: AccountTransferFormPage, meta: { title: 'Edit Transfer' } },
    { path: '/accounting/transactions', component: AccountsPage, meta: { title: 'Transactions' } },
    { path: '/accounting/adjustments', component: AccountsPage, meta: { title: 'Adjustments' } },
    { path: '/accounting/adjustments/new', component: AccountAdjustmentFormPage, meta: { title: 'New Adjustment' } },
    { path: '/accounting/adjustments/:id(\\d+)/edit', component: AccountAdjustmentFormPage, meta: { title: 'Edit Adjustment' } },
    { path: '/payments', redirect: to => ({ path: '/accounting/payments', query: to.query }) },
    { path: '/payments/plans', redirect: '/settings/payments-plans' },
    { path: '/payments/methods', redirect: '/settings/payments-methods' },
    { path: '/payments/new', redirect: '/accounting/payments/new' },
    { path: '/payments/:id(\\d+)', redirect: to => `/accounting/payments/${to.params.id}` },
    { path: '/payments/:id(\\d+)/edit', redirect: to => `/accounting/payments/${to.params.id}/edit` },
    { path: '/employees', component: EmployeeManagementPage, meta: { title: 'Employees' } },
    { path: '/employees/new', component: EmployeeFormPage, meta: { title: 'New Employee' } },
    { path: '/employees/:id(\\d+)', component: EmployeeViewPage, meta: { title: 'Employee Profile' } },
    { path: '/employees/:id(\\d+)/edit', component: EmployeeFormPage, meta: { title: 'Edit Employee' } },
    { path: '/wallet-topups/:id', component: WalletTopupViewPage, meta: { title: 'Wallet Top-up' } },
    { path: '/notifications', redirect: to => ({ path: '/settings/notifications', query: to.query }) },
    { path: '/notifications/new', redirect: '/settings/notifications/new' },
    { path: '/notifications/:id(\\d+)', redirect: to => `/settings/notifications/${to.params.id}` },
    { path: '/notifications/:id(\\d+)/edit', redirect: to => `/settings/notifications/${to.params.id}/edit` },
    { path: '/events', redirect: to => ({ path: '/settings/events', query: to.query }) },
    { path: '/events/new', redirect: '/settings/events/new' },
    { path: '/events/:id(\\d+)', redirect: to => `/settings/events/${to.params.id}` },
    { path: '/events/:id(\\d+)/edit', redirect: to => `/settings/events/${to.params.id}/edit` },
    { path: '/events/:id(\\d+)/registrations', redirect: to => `/settings/events/${to.params.id}/registrations` },
    { path: '/activity', component: MemberActivityPage, meta: { title: 'Activity Logs' } },
    { path: '/vouchers', redirect: to => ({ path: '/settings/vouchers', query: to.query }) },
    { path: '/vouchers/:id(\\d+)', redirect: to => `/settings/vouchers/${to.params.id}` },
    { path: '/forms', redirect: to => ({ path: '/settings/forms', query: to.query }) },
    { path: '/forms/new', redirect: '/settings/forms/new' },
    { path: '/forms/:id(\\d+)/edit', redirect: to => `/settings/forms/${to.params.id}/edit` },
    { path: '/forms/:id(\\d+)/submissions', redirect: to => `/settings/forms/${to.params.id}/submissions` },
    { path: '/campaigns', redirect: '/settings/campaigns' },
    { path: '/campaigns/new', redirect: '/settings/campaigns/new' },
    { path: '/campaigns/:id(\\d+)/edit', redirect: to => `/settings/campaigns/${to.params.id}/edit` },
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
