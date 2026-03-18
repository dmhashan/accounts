import { computed } from 'vue';
import { useAppContext } from './useAppContext';

export function useNavigation() {
    const context = useAppContext();

    const menuItems = computed(() => {
        const items = [];

        if (context.permissions?.dashboard) items.push({ label: 'Dashboard', path: '/dashboard' });
        if (context.permissions?.users) items.push({ label: 'Users', path: '/users' });
        if (context.permissions?.members) items.push({ label: 'Members', path: '/members' });
        if (context.permissions?.roles) items.push({ label: 'Roles', path: '/roles' });
        if (context.permissions?.inventory) items.push({ label: 'Inventory', path: '/inventory' });
        if (context.permissions?.sales) items.push({ label: 'Sales', path: '/sales' });
        if (context.permissions?.stats) items.push({ label: 'Sales Stats', path: '/stats' });
        if (context.permissions?.reports) items.push({ label: 'Reports', path: '/reports' });
        if (context.permissions?.settings) items.push({ label: 'Settings', path: '/settings' });

        if (context.permissions?.profile) items.push({ label: 'Profile', path: '/profile' });
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
