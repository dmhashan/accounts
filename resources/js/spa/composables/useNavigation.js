import { computed } from 'vue';
import {
    LayoutDashboard,
    Users,
    ShieldCheck,
    Package,
    CreditCard,
    ShoppingBag,
    FileText,
    Settings,
    Dumbbell,
    Activity,
    BriefcaseBusiness,
    MessageSquareMore,
} from 'lucide-vue-next';
import { useAppContext } from './useAppContext';

const ICONS = {
    dashboard:     LayoutDashboard,
    users:         Users,
    members:       Users,
    roles:         ShieldCheck,
    inventory:     Package,
    accounting:    CreditCard,
    sales:         ShoppingBag,
    reports:       FileText,
    settings:      Settings,
    workout:       Dumbbell,
    activity:      Activity,
    employees:     BriefcaseBusiness,
    chatbot:       MessageSquareMore,
};

export function useNavigation() {
    const context = useAppContext();

    const menuItems = computed(() => {
        const items = [];

        if (context.permissions?.dashboard) {
            items.push({ label: 'Dashboard', shortLabel: 'Home',     path: '/dashboard', icon: ICONS.dashboard });
            items.push({ label: 'AI Assistant', shortLabel: 'Chat',     path: '/chatbot', icon: ICONS.chatbot, badge: 'Beta' });
        }

        if (context.permissions?.members) {
            const children = [];
            if (context.permissions?.membersList) children.push({ label: 'Members', path: '/members' });
            if (context.permissions?.membersTemp) children.push({ label: 'Temp Members', path: '/members/temp' });
            items.push({
                label: 'Members',
                shortLabel: 'Members',
                path: '/members',
                icon: ICONS.members,
                children,
            });
        }

        if (context.permissions?.inventory) {
            const invChildren = [];
            if (context.permissions?.inventoryProducts) invChildren.push({ label: 'Products', path: '/inventory' });
            if (context.permissions?.inventoryStock) invChildren.push({ label: 'Stock', path: '/inventory/stock' });
            if (context.permissions?.inventoryDisplay) invChildren.push({ label: 'Display', path: '/inventory/display' });
            if (context.permissions?.inventoryAudit) invChildren.push({ label: 'Audit', path: '/inventory/audit' });
            items.push({ label: 'Inventory', shortLabel: 'Stock', path: '/inventory', icon: ICONS.inventory, children: invChildren });
        }

        if (context.permissions?.paymentsManage || context.permissions?.expenses || context.permissions?.accountsTransfers || context.permissions?.accountsTransactions || context.permissions?.accountsAdjust) {
            const children = [];
            if (context.permissions?.paymentsManage) children.push({ label: 'Payments', path: '/accounting/payments', activePrefix: '/accounting/payments' });
            if (context.permissions?.expenses) children.push({ label: 'Expenses', path: '/accounting/expenses', activePrefix: '/accounting/expenses' });
            if (context.permissions?.accountsTransfers) children.push({ label: 'Transfers', path: '/accounting/transfers', activePrefix: '/accounting/transfers' });
            if (context.permissions?.accountsTransactions) children.push({ label: 'Transactions', path: '/accounting/transactions' });
            if (context.permissions?.accountsAdjust) children.push({ label: 'Adjustments', path: '/accounting/adjustments', activePrefix: '/accounting/adjustments' });
            items.push({
                label: 'Accounting',
                shortLabel: 'Accounting',
                path: '/accounting',
                icon: ICONS.accounting,
                children,
            });
        }

        if (context.permissions?.sales) {
            const children = [];
            if (context.permissions?.salesOutstanding) children.push({ label: 'Outstanding', path: '/sales' });
            if (context.permissions?.salesPaid) children.push({ label: 'Paid', path: '/sales/paid' });
            items.push({
                label: 'Sales',
                shortLabel: 'Sales',
                path: '/sales',
                icon: ICONS.sales,
                children,
            });
        }

        if (context.permissions?.employeesManage || context.permissions?.employeePaySheetsManage) {
            items.push({
                label: 'Employees',
                shortLabel: 'Staff',
                path: '/employees',
                icon: ICONS.employees,
            });
        }

        if (context.permissions?.reports) {
            const children = [];
            if (context.permissions?.reportsDailySummary) children.push({ label: 'Daily Summary', path: '/reports/daily-summary' });
            if (context.permissions?.reportsRealProfit) children.push({ label: 'Real Profit', path: '/reports/real-profit' });
            if (context.permissions?.reportsMemberAnalysis) children.push({ label: 'Member Analysis', path: '/reports/member-analysis' });
            if (context.permissions?.reportsStatistics) children.push({ label: 'Statistics', path: '/reports' });
            if (context.permissions?.reportsCustomers) children.push({ label: 'Customers', path: '/reports/customers' });
            if (context.permissions?.reportsProducts) children.push({ label: 'Products', path: '/reports/products' });
            items.push({
                label: 'Reports',
                shortLabel: 'Reports',
                path: '/reports',
                icon: ICONS.reports,
                children,
            });
        }

        if (
            context.permissions?.settings ||
            context.permissions?.users ||
            context.permissions?.roles ||
            context.permissions?.accountsManage ||
            context.permissions?.paymentPlansManage ||
            context.permissions?.paymentMethodsManage ||
            context.permissions?.notifications ||
            context.permissions?.events ||
            context.permissions?.vouchersManage ||
            context.permissions?.formsManage ||
            context.permissions?.campaigns
        ) {
            const settingsChildren = [];
            if (context.permissions?.settingsGeneral) settingsChildren.push({ label: 'General', path: '/settings/general' });
            if (context.permissions?.settingsMemberReachable ?? context.permissions?.settingsGeneral) settingsChildren.push({ label: 'Member Reachable Configurations', path: '/settings/member-reachable' });
            if (context.permissions?.users) settingsChildren.push({ label: 'Users', path: '/settings/users' });
            if (context.permissions?.roles) settingsChildren.push({ label: 'Roles', path: '/settings/roles' });
            if (context.permissions?.accountsManage) settingsChildren.push({ label: 'Accounts', path: '/settings/accounts', activePrefix: '/settings/accounts' });
            if (context.permissions?.paymentPlansManage) settingsChildren.push({ label: 'Payment Plans', path: '/settings/payments-plans' });
            if (context.permissions?.paymentMethodsManage) settingsChildren.push({ label: 'Payment Methods', path: '/settings/payments-methods' });
            if (context.permissions?.notifications) settingsChildren.push({ label: 'Notifications', path: '/settings/notifications', activePrefix: '/settings/notifications' });
            if (context.permissions?.events) settingsChildren.push({ label: 'Events', path: '/settings/events', activePrefix: '/settings/events' });
            if (context.permissions?.vouchersManage) settingsChildren.push({ label: 'Vouchers', path: '/settings/vouchers', activePrefix: '/settings/vouchers' });
            if (context.permissions?.formsManage) settingsChildren.push({ label: 'Forms', path: '/settings/forms', activePrefix: '/settings/forms' });
            if (context.permissions?.campaigns) settingsChildren.push({ label: 'Campaigns', path: '/settings/campaigns', activePrefix: '/settings/campaigns' });
            if (context.permissions?.settingsConfiguration) settingsChildren.push({ label: 'Configuration', path: '/settings/configuration' });
            if (context.permissions?.settingsBiometric) settingsChildren.push({ label: 'Biometric', path: '/settings/biometric' });
            if (context.permissions?.settingsLegacyTools) settingsChildren.push({ label: 'Manual Commands', path: '/settings/legacy-tools' });
            items.push({
                label: 'Settings', shortLabel: 'Settings', path: '/settings', icon: ICONS.settings,
                children: settingsChildren,
            });
        }

        if (context.permissions?.workout) {
            const children = [];
            if (context.permissions?.workoutPrograms) children.push({ label: 'Programs', path: '/workout' });
            if (context.permissions?.workoutExercises) children.push({ label: 'Exercises', path: '/workout/exercises' });
            if (context.permissions?.workoutAssignments) children.push({ label: 'Assignments', path: '/workout/assignments' });
            items.push({
                label: 'Workout',
                shortLabel: 'Workout',
                path: '/workout',
                icon: ICONS.workout,
                children,
            });
        }

        if (context.permissions?.activity)               items.push({ label: 'Activity Logs',  shortLabel: 'Activity',      path: '/activity',        icon: ICONS.activity });

        return items;
    });

    const quickItems = computed(() => menuItems.value.slice(0, 4));

    return {
        context,
        menuItems,
        quickItems,
    };
}
