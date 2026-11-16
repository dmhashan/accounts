import type { CSSProperties } from 'react';
import { useCallback, useEffect, useRef, useState } from 'react';
import type { StaffAppContext } from '../../lib/api';
import { WorkspaceDrawer } from './WorkspaceDrawer';
import { WorkspaceHeader } from './WorkspaceHeader';
import { WorkspaceSidebar } from './WorkspaceSidebar';
import { WorkspaceWelcome } from './WorkspaceWelcome';

type WorkspaceShellProps = {
  context: StaffAppContext;
  onLogout: () => Promise<void>;
};

const tenantThemes = {
  crimson: { primary: '#ef4444', dark: '#991b1b', light: '#fca5a5' },
  ocean: { primary: '#0ea5e9', dark: '#075985', light: '#7dd3fc' },
  forest: { primary: '#22c55e', dark: '#166534', light: '#86efac' },
  violet: { primary: '#8b5cf6', dark: '#5b21b6', light: '#c4b5fd' },
  sunset: { primary: '#f97316', dark: '#9a3412', light: '#fdba74' },
  slate: { primary: '#64748b', dark: '#334155', light: '#cbd5e1' },
} as const;

export function WorkspaceShell({ context, onLogout }: WorkspaceShellProps) {
  const [drawerOpen, setDrawerOpen] = useState(false);
  const [loggingOut, setLoggingOut] = useState(false);
  const [error, setError] = useState('');
  const [prefersDark, setPrefersDark] = useState(false);
  const menuButtonRef = useRef<HTMLButtonElement>(null);
  const closeDrawer = useCallback(() => setDrawerOpen(false), []);
  const colors = tenantThemes[context.settings.colorTheme];
  const colorMode = context.settings.colorMode === 'system' ? (prefersDark ? 'dark' : 'light') : context.settings.colorMode;

  useEffect(() => {
    const media = window.matchMedia('(prefers-color-scheme: dark)');
    const updatePreference = () => setPrefersDark(media.matches);
    updatePreference();
    media.addEventListener('change', updatePreference);
    return () => media.removeEventListener('change', updatePreference);
  }, []);

  const themeStyle = {
    '--blue': colors.primary,
    '--blue-hover': colors.dark,
    '--blue-light': colors.light,
    '--blue-soft': `color-mix(in srgb, ${colors.primary} 9%, var(--surface))`,
    '--blue-ink': colorMode === 'dark' ? colors.light : colors.dark,
  } as CSSProperties & Record<'--blue' | '--blue-hover' | '--blue-light' | '--blue-soft' | '--blue-ink', string>;

  useEffect(() => {
    const previousTitle = document.title;
    document.title = `${context.tenant.name} · FitFlow`;
    return () => { document.title = previousTitle; };
  }, [context.tenant.name]);

  async function handleLogout() {
    setLoggingOut(true);
    setError('');
    try {
      await onLogout();
    } catch (reason) {
      setError(reason instanceof Error ? reason.message : 'Could not sign out. Try again.');
    } finally {
      setLoggingOut(false);
    }
  }

  return (
    <main className="workspace-shell" data-color-mode={colorMode} style={themeStyle}>
      <WorkspaceSidebar context={context} onLogout={() => void handleLogout()} loggingOut={loggingOut} />
      <section className="workspace-main" aria-label={`${context.tenant.name} staff workspace`}>
        <WorkspaceHeader
          context={context}
          onOpenMenu={() => setDrawerOpen(true)}
          menuButtonRef={menuButtonRef}
          onLogout={() => void handleLogout()}
          loggingOut={loggingOut}
        />
        {error && <div className="shell-alert" role="alert" aria-live="assertive">{error}</div>}
        <WorkspaceWelcome context={context} />
      </section>
      {drawerOpen && (
        <WorkspaceDrawer
          context={context}
          onClose={closeDrawer}
          onLogout={() => void handleLogout()}
          loggingOut={loggingOut}
          menuButtonRef={menuButtonRef}
        />
      )}
    </main>
  );
}