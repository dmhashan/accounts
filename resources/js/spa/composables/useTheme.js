import { computed, ref } from 'vue';

export const COLOR_THEMES = [
    { value: 'crimson', label: 'Crimson', description: 'Bold and energetic', colors: ['#ef4444', '#991b1b', '#fca5a5'] },
    { value: 'ocean', label: 'Ocean', description: 'Clear and confident', colors: ['#0ea5e9', '#075985', '#7dd3fc'] },
    { value: 'forest', label: 'Forest', description: 'Grounded and focused', colors: ['#22c55e', '#166534', '#86efac'] },
    { value: 'violet', label: 'Violet', description: 'Modern and expressive', colors: ['#8b5cf6', '#5b21b6', '#c4b5fd'] },
    { value: 'sunset', label: 'Sunset', description: 'Warm and welcoming', colors: ['#f97316', '#9a3412', '#fdba74'] },
    { value: 'slate', label: 'Slate', description: 'Quiet and professional', colors: ['#64748b', '#334155', '#cbd5e1'] },
];

export const COLOR_MODES = [
    { value: 'system', label: 'System', description: 'Follow the device setting' },
    { value: 'light', label: 'Light', description: 'Always use light mode' },
    { value: 'dark', label: 'Dark', description: 'Always use dark mode' },
];

const isDark = ref(document.documentElement.classList.contains('dark'));
const colorTheme = ref(document.documentElement.dataset.theme || 'crimson');
const defaultMode = ref('system');
const tenantDomain = ref('');
let mediaQuery;

function storageKey() {
    return tenantDomain.value ? `theme:${tenantDomain.value}` : 'theme';
}

function effectiveMode() {
    return localStorage.getItem(storageKey()) || defaultMode.value;
}

function applyMode() {
    const mode = effectiveMode();
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    isDark.value = mode === 'dark' || (mode === 'system' && prefersDark);
    document.documentElement.classList.toggle('dark', isDark.value);
}

export function initializeTheme(context = {}) {
    tenantDomain.value = context.tenant?.domain || '';
    defaultMode.value = context.settings?.colorMode || 'system';
    setColorTheme(context.settings?.colorTheme || 'crimson');
    applyMode();

    mediaQuery?.removeEventListener('change', applyMode);
    mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
    mediaQuery.addEventListener('change', applyMode);
}

export function setColorTheme(theme) {
    colorTheme.value = COLOR_THEMES.some((item) => item.value === theme) ? theme : 'crimson';
    document.documentElement.dataset.theme = colorTheme.value;
}

export function setDefaultMode(mode) {
    defaultMode.value = COLOR_MODES.some((item) => item.value === mode) ? mode : 'system';
    applyMode();
}

export function setUserMode(mode) {
    if (mode === 'system') {
        localStorage.removeItem(storageKey());
    } else {
        localStorage.setItem(storageKey(), mode);
    }
    applyMode();
}

export function toggleTheme() {
    setUserMode(isDark.value ? 'light' : 'dark');
}

export function useTheme() {
    return {
        isDark: computed(() => isDark.value),
        colorTheme: computed(() => colorTheme.value),
        defaultMode: computed(() => defaultMode.value),
        toggleTheme,
        setColorTheme,
        setDefaultMode,
        setUserMode,
    };
}
