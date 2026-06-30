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

        if (context.permissions?.members) items.push({
            label: 'Members', shortLabel: 'Members', path: '/members', icon: ICONS.members,
            children: [
                { label: 'Members', path: '/members' },
                { label: 'Temp Members', path: '/members/temp' },
            ],
        });

        if (context.permissions?.inventory) {
            const invChildren = [{ label: 'Products', path: '/inventory' }];
            if (context.permissions?.inventoryStock) invChildren.push({ label: 'Stock', path: '/inventory/stock' });
            if (context.permissions?.inventoryDisplay) invChildren.push({ label: 'Display', path: '/inventory/display' });
            if (context.permissions?.inventoryStock || context.permissions?.inventoryDisplay) invChildren.push({ label: 'Audit', path: '/inventory/audit' });
            items.push({ label: 'Inventory', shortLabel: 'Stock', path: '/inventory', icon: ICONS.inventory, children: invChildren });
        }

        if (context.permissions?.accounts) items.push({
            label: 'Accounts', shortLabel: 'Accounts', path: '/accounts', icon: ICONS.accounts,
            children: [
                { label: 'Accounts', path: '/accounts' },
                { label: 'Transfers', path: '/accounts/transfers' },
                { label: 'Transactions', path: '/accounts/transactions' },
            ],
        });

        if (context.permissions?.expenses)       items.push({ label: 'Expenses',  shortLabel: 'Expenses', path: '/expenses',  icon: ICONS.expenses });

        if (context.permissions?.sales) items.push({
            label: 'Sales', shortLabel: 'Sales', path: '/sales', icon: ICONS.sales,
            children: [
                { label: 'Outstanding', path: '/sales' },
                { label: 'Paid', path: '/sales/paid' },
            ],
        });

        if (context.permissions?.paymentsManage) items.push({
            label: 'Payments', shortLabel: 'Payments', path: '/payments', icon: ICONS.payments,
            children: [
                { label: 'Payments', path: '/payments' },
                { label: 'Payment Plans', path: '/payments/plans' },
            ],
        });

        if (context.permissions?.employeesManage || context.permissions?.employeePaySheetsManage) {
            items.push({
                label: 'Employees',
                shortLabel: 'Staff',
                path: '/employees',
                icon: ICONS.employees,
            });
        }

        if (context.permissions?.reports) items.push({
            label: 'Reports', shortLabel: 'Reports', path: '/reports', icon: ICONS.reports,
            children: [
                { label: 'Daily Summary', path: '/reports/daily-summary' },
                { label: 'Real Profit', path: '/reports/real-profit' },
                { label: 'Statistics', path: '/reports' },
                { label: 'Customers', path: '/reports/customers' },
                { label: 'Products', path: '/reports/products' },
            ],
        });

        if (context.permissions?.settings || context.permissions?.users || context.permissions?.roles) {
            const settingsChildren = [];
            if (context.permissions?.settings) settingsChildren.push({ label: 'General', path: '/settings/general' });
            if (context.permissions?.users) settingsChildren.push({ label: 'Users', path: '/settings/users' });
            if (context.permissions?.roles) settingsChildren.push({ label: 'Roles', path: '/settings/roles' });
            if (context.permissions?.settings) settingsChildren.push({ label: 'Configuration', path: '/settings/configuration' });
            if (context.permissions?.settings) settingsChildren.push({ label: 'Biometric', path: '/settings/biometric' });
            if (context.permissions?.settings) settingsChildren.push({ label: 'Manual Commands', path: '/settings/legacy-tools' });
            items.push({
                label: 'Settings', shortLabel: 'Settings', path: '/settings', icon: ICONS.settings,
                children: settingsChildren,
            });
        }

        if (context.permissions?.workout) items.push({
            label: 'Workout', shortLabel: 'Workout', path: '/workout', icon: ICONS.workout,
            children: [
                { label: 'Programs', path: '/workout' },
                { label: 'Exercises', path: '/workout/exercises' },
                { label: 'Assignments', path: '/workout/assignments' },
            ],
        });

        if (context.permissions?.diet)           items.push({ label: 'Diet',      shortLabel: 'Diet',     path: '/diet',      icon: ICONS.diet });
        if (context.permissions?.attendance)     items.push({ label: 'Attendance',    shortLabel: 'Attend',  path: '/attendance',    icon: ICONS.attendance });
        if (context.permissions?.notifications)  items.push({ label: 'Notifications', shortLabel: 'Notify',   path: '/notifications', icon: ICONS.notifications });
        if (context.permissions?.events)          items.push({ label: 'Events',        shortLabel: 'Events',   path: '/events',        icon: ICONS.events });
        if (context.permissions?.activity)               items.push({ label: 'Activity Logs',  shortLabel: 'Activity',      path: '/activity',        icon: ICONS.activity });
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
