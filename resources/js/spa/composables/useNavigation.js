import { computed } from 'vue';
import {
    LayoutDashboard,
    Users,
    ShieldCheck,
    Package,
    CreditCard,
    ShoppingBag,
    BarChart3,
    FileText,
    Settings,
    CircleUserRound,
} from 'lucide-vue-next';
import { useAppContext } from './useAppContext';

const ICONS = {
    dashboard: LayoutDashboard,
    users: Users,
    members: Users,
    roles: ShieldCheck,
    inventory: Package,
    accounts: CreditCard,
    sales: ShoppingBag,
    stats: BarChart3,
    reports: FileText,
    settings: Settings,
    profile: CircleUserRound,
};

export function useNavigation() {
    const context = useAppContext();

    const menuItems = computed(() => {
        const items = [];

        if (context.permissions?.dashboard) items.push({ label: 'Dashboard', shortLabel: 'Home',     path: '/dashboard', icon: ICONS.dashboard });
        if (context.permissions?.users)     items.push({ label: 'Users',     shortLabel: 'Users',    path: '/users',     icon: ICONS.users });
        if (context.permissions?.members)   items.push({ label: 'Members',   shortLabel: 'Members',  path: '/members',   icon: ICONS.members });
        if (context.permissions?.roles)     items.push({ label: 'Roles',     shortLabel: 'Roles',    path: '/roles',     icon: ICONS.roles });
        if (context.permissions?.inventory) items.push({ label: 'Inventory', shortLabel: 'Stock',    path: '/inventory', icon: ICONS.inventory });
        if (context.permissions?.accounts)  items.push({ label: 'Accounts',  shortLabel: 'Accounts', path: '/accounts',  icon: ICONS.accounts });
        if (context.permissions?.sales)     items.push({ label: 'Sales',     shortLabel: 'Sales',    path: '/sales',     icon: ICONS.sales });
        if (context.permissions?.stats)     items.push({ label: 'Sales Stats', shortLabel: 'Stats',  path: '/stats',     icon: ICONS.stats });
        if (context.permissions?.reports)   items.push({ label: 'Reports',   shortLabel: 'Reports',  path: '/reports',   icon: ICONS.reports });
        if (context.permissions?.settings)  items.push({ label: 'Settings',  shortLabel: 'Settings', path: '/settings',  icon: ICONS.settings });
        if (context.permissions?.profile)   items.push({ label: 'Profile',   shortLabel: 'Profile',  path: '/profile',   icon: ICONS.profile });
        // if (context.permissions?.workout) items.push({ label: 'Workout', path: '/workout' });
        // if (context.permissions?.diet) items.push({ label: 'Diet', path: '/diet' });
        // if (context.permissions?.payments) items.push({ label: 'Payments', path: '/payments' });
        // if (context.permissions?.attendance) items.push({ label: 'Attendance', path: '/attendance' });

        return items;
    });

    const quickItems = computed(() => menuItems.value.slice(0, 4));

    return {
        context,
        menuItems,
        quickItems,
    };
}
