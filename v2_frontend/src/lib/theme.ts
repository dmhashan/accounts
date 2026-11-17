import type { CSSProperties } from 'react';
import type { TenantColorMode, TenantColorTheme } from './api';

const tenantThemes: Record<TenantColorTheme, { primary: string; dark: string; light: string }> = {
  crimson: { primary: '#ef4444', dark: '#991b1b', light: '#fca5a5' },
  ocean: { primary: '#0ea5e9', dark: '#075985', light: '#7dd3fc' },
  forest: { primary: '#22c55e', dark: '#166534', light: '#86efac' },
  violet: { primary: '#8b5cf6', dark: '#5b21b6', light: '#c4b5fd' },
  sunset: { primary: '#f97316', dark: '#9a3412', light: '#fdba74' },
  slate: { primary: '#64748b', dark: '#334155', light: '#cbd5e1' },
};

type TenantThemeStyle = CSSProperties & Record<
  '--blue' | '--blue-hover' | '--blue-light' | '--blue-soft' | '--blue-ink',
  string
>;

export function getTenantThemeStyle(theme: TenantColorTheme, colorMode: TenantColorMode): TenantThemeStyle {
  const colors = tenantThemes[theme];

  return {
    '--blue': colors.primary,
    '--blue-hover': colors.dark,
    '--blue-light': colors.light,
    '--blue-soft': `color-mix(in srgb, ${colors.primary} 9%, var(--surface))`,
    '--blue-ink': colorMode === 'dark' ? colors.light : colors.dark,
  };
}
