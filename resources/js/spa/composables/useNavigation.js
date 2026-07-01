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
    CircleUserRound,
    Dumbbell,
    Salad,
    WalletCards,
    CalendarCheck2,
    ReceiptText,
    BellRing,
    Activity,
    ClipboardCheck,
    ClipboardList,
    CalendarDays,
    Ticket,
    BriefcaseBusiness,
} from 'lucide-vue-next';
import { useAppContext } from './useAppContext';

const ICONS = {
    dashboard:     LayoutDashboard,
    users:         Users,
    members:       Users,
    roles:         ShieldCheck,
    inventory:     Package,
    accounts:      CreditCard,
    expenses:      ReceiptText,
    sales:         ShoppingBag,
    reports:       FileText,
    settings:      Settings,
    profile:       CircleUserRound,
    workout:       Dumbbell,
    diet:          Salad,
    payments:      WalletCards,
    attendance:    CalendarCheck2,
    notifications: BellRing,
    events:        CalendarDays,
    activity:      Activity,
    reconciliation: ClipboardCheck,
    vouchers:      Ticket,
    forms:         ClipboardList,
    employees:     BriefcaseBusiness,
};

export function useNavigation() {
    const context = useAppContext();

    const menuItems = computed(() => {
        const items = [];

        if (context.permissions?.dashboard) items.push({ label: 'Dashboard', shortLabel: 'Home',     path: '/dashboard', icon: ICONS.dashboard });

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

        if (context.permissions?.accounts) {
            const children = [];
            if (context.permissions?.accountsManage) children.push({ label: 'Accounts', path: '/accounts' });
            if (context.permissions?.accountsTransfers) children.push({ label: 'Transfers', path: '/accounts/transfers' });
            if (context.permissions?.accountsTransactions) children.push({ label: 'Transactions', path: '/accounts/transactions' });
            items.push({
                label: 'Accounts',
                shortLabel: 'Accounts',
                path: '/accounts',
                icon: ICONS.accounts,
                children,
            });
        }

        if (context.permissions?.expenses)       items.push({ label: 'Expenses',  shortLabel: 'Expenses', path: '/expenses',  icon: ICONS.expenses });

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

        if (context.permissions?.paymentsManage || context.permissions?.paymentPlansManage) {
            const children = [];
            if (context.permissions?.paymentsManage) children.push({ label: 'Payments', path: '/payments' });
            if (context.permissions?.paymentPlansManage) children.push({ label: 'Payment Plans', path: '/payments/plans' });
            items.push({
                label: 'Payments',
                shortLabel: 'Payments',
                path: '/payments',
                icon: ICONS.payments,
                children,
            });
        }

        if (context.permissions?.employeesManage || context.permissions?.employeePaySheetsManage) {
            const employeeChildren = [];
            if (context.permissions?.employeesManage) employeeChildren.push({ label: 'Employees', path: '/employees' });
            if (context.permissions?.employeePaySheetsManage) employeeChildren.push({ label: 'Pay Sheets', path: '/employees' });
            items.push({
                label: 'Employees',
                shortLabel: 'Staff',
                path: '/employees',
                icon: ICONS.employees,
                children: employeeChildren.length > 1 ? employeeChildren : undefined,
            });
        }

        if (context.permissions?.reports) {
            const children = [];
            if (context.permissions?.reportsDailySummary) children.push({ label: 'Daily Summary', path: '/reports/daily-summary' });
            if (context.permissions?.reportsRealProfit) children.push({ label: 'Real Profit', path: '/reports/real-profit' });
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

        if (context.permissions?.settings || context.permissions?.users || context.permissions?.roles) {
            const settingsChildren = [];
            if (context.permissions?.settingsGeneral) settingsChildren.push({ label: 'General', path: '/settings/general' });
            if (context.permissions?.users) settingsChildren.push({ label: 'Users', path: '/settings/users' });
            if (context.permissions?.roles) settingsChildren.push({ label: 'Roles', path: '/settings/roles' });
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

        if (context.permissions?.diet)           items.push({ label: 'Diet',      shortLabel: 'Diet',     path: '/diet',      icon: ICONS.diet });
        if (context.permissions?.attendance)     items.push({ label: 'Attendance',    shortLabel: 'Attend',  path: '/attendance',    icon: ICONS.attendance });
        if (context.permissions?.notifications)  items.push({ label: 'Notifications', shortLabel: 'Notify',   path: '/notifications', icon: ICONS.notifications });
        if (context.permissions?.events)          items.push({ label: 'Events',        shortLabel: 'Events',   path: '/events',        icon: ICONS.events });
        if (context.permissions?.activity)               items.push({ label: 'Activity Logs',  shortLabel: 'Activity',      path: '/activity',        icon: ICONS.activity });
        if (context.permissions?.reconciliation) items.push({ label: 'Reconciliation', shortLabel: 'Recon', path: '/reconciliation', icon: ICONS.reconciliation });
        // TODO: temparary hide because of requirement not finalized
        if (context.permissions?.vouchersManage)
            items.push({ label: 'Vouchers', shortLabel: 'Vouchers', path: '/vouchers', icon: ICONS.vouchers });
        if (context.permissions?.formsManage)
            items.push({ label: 'Forms', shortLabel: 'Forms', path: '/forms', icon: ICONS.forms });
        if (context.permissions?.profile)                 items.push({ label: 'Profile',        shortLabel: 'Profile',       path: '/profile',         icon: ICONS.profile });

        return items;
    });

    const quickItems = computed(() => menuItems.value.slice(0, 4));

    return {
        context,
        menuItems,
        quickItems,
    };
}
