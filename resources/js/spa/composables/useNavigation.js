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
} from 'lucide-vue-next';
import { useAppContext } from './useAppContext';

const ICONS = {
    dashboard: LayoutDashboard,
    users: Users,
    members: Users,
    roles: ShieldCheck,
    inventory: Package,
    accounts: CreditCard,
    expenses: ReceiptText,
    sales: ShoppingBag,
    reports: FileText,
    settings: Settings,
    profile: CircleUserRound,
    workout: Dumbbell,
    diet: Salad,
    payments: WalletCards,
    attendance: CalendarCheck2,
};

export function useNavigation() {
    const context = useAppContext();

    const menuItems = computed(() => {
        const items = [];

        if (context.permissions?.dashboard) items.push({ label: 'Dashboard', shortLabel: 'Home',     path: '/dashboard', icon: ICONS.dashboard });
        if (context.permissions?.members)   items.push({ label: 'Members',   shortLabel: 'Members',  path: '/members',   icon: ICONS.members });
        if (context.permissions?.inventory) items.push({ label: 'Inventory', shortLabel: 'Stock',    path: '/inventory', icon: ICONS.inventory });
        if (context.permissions?.accounts)       items.push({ label: 'Accounts',  shortLabel: 'Accounts', path: '/accounts',  icon: ICONS.accounts });
        if (context.permissions?.expenses)       items.push({ label: 'Expenses',  shortLabel: 'Expenses', path: '/expenses',  icon: ICONS.expenses });
        if (context.permissions?.sales)          items.push({ label: 'Sales',     shortLabel: 'Sales',    path: '/sales',     icon: ICONS.sales });
        if (context.permissions?.paymentsManage) items.push({ label: 'Payments',  shortLabel: 'Payments', path: '/payments',  icon: ICONS.payments });

        if (context.permissions?.reports)        items.push({ label: 'Reports',   shortLabel: 'Reports',  path: '/reports',   icon: ICONS.reports });
        if (context.permissions?.settings)       items.push({ label: 'Settings',  shortLabel: 'Settings', path: '/settings',  icon: ICONS.settings });
        if (context.permissions?.workout)        items.push({ label: 'Workout',   shortLabel: 'Workout',  path: '/workout',   icon: ICONS.workout });
        if (context.permissions?.diet)           items.push({ label: 'Diet',      shortLabel: 'Diet',     path: '/diet',      icon: ICONS.diet });
        if (context.permissions?.attendance)     items.push({ label: 'Attendance', shortLabel: 'Attend', path: '/attendance', icon: ICONS.attendance });
        if (context.permissions?.profile)        items.push({ label: 'Profile',   shortLabel: 'Profile',  path: '/profile',   icon: ICONS.profile });

        return items;
    });

    const quickItems = computed(() => menuItems.value.slice(0, 4));

    return {
        context,
        menuItems,
        quickItems,
    };
}
