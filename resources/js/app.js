import './bootstrap';
import { createApp } from 'vue';
import DashboardOverview from './components/dashboard/DashboardOverview.vue';

function parseJson(value, fallback = {}) {
    try {
        return value ? JSON.parse(value) : fallback;
    } catch {
        return fallback;
    }
}

// Dark mode functionality
if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
    document.documentElement.classList.add('dark');
} else {
    document.documentElement.classList.remove('dark');
}

window.toggleTheme = function() {
    if (document.documentElement.classList.contains('dark')) {
        document.documentElement.classList.remove('dark');
        localStorage.theme = 'light';
    } else {
        document.documentElement.classList.add('dark');
        localStorage.theme = 'dark';
    }
}

window.toggleSidebar = function() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    
    if (sidebar && overlay) {
        sidebar.classList.toggle('-translate-x-full');
        overlay.classList.toggle('hidden');

        const isCollapsed = sidebar.getAttribute('data-collapsed') === 'true';
        const nextState = isCollapsed ? 'false' : 'true';
        sidebar.setAttribute('data-collapsed', nextState);
        localStorage.setItem('sidebarCollapsed', nextState);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const dashboardRoot = document.getElementById('dashboard-overview');

    if (dashboardRoot) {
        createApp(DashboardOverview, {
            tenant: parseJson(dashboardRoot.dataset.tenant),
            user: parseJson(dashboardRoot.dataset.user),
            successMessage: dashboardRoot.dataset.successMessage || '',
            appDomain: dashboardRoot.dataset.appDomain || '',
        }).mount(dashboardRoot);
    }

    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const storedState = localStorage.getItem('sidebarCollapsed');

    if (sidebar && storedState) {
        sidebar.setAttribute('data-collapsed', storedState);
        if (storedState === 'true') {
            sidebar.classList.add('-translate-x-full');
            overlay?.classList.add('hidden');
        } else {
            sidebar.classList.remove('-translate-x-full');
        }
    }

    const salesLink = document.querySelector('[data-collapse-sidebar="true"]');
    if (salesLink && sidebar && overlay) {
        salesLink.addEventListener('click', () => {
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
            sidebar.setAttribute('data-collapsed', 'true');
            localStorage.setItem('sidebarCollapsed', 'true');
        });
    }
});
