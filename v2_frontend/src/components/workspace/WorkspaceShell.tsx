import { useCallback, useEffect, useRef, useState } from 'react';
import type { StaffAppContext } from '../../lib/api';
import { getTenantThemeStyle } from '../../lib/theme';
import { WorkspaceDrawer } from './WorkspaceDrawer';
import { WorkspaceHeader } from './WorkspaceHeader';
import { WorkspaceSidebar } from './WorkspaceSidebar';
import { WorkspaceWelcome } from './WorkspaceWelcome';

type WorkspaceShellProps = {
  context: StaffAppContext;
  onLogout: () => Promise<void>;
};

export function WorkspaceShell({ context, onLogout }: WorkspaceShellProps) {
  const [drawerOpen, setDrawerOpen] = useState(false);
  const [loggingOut, setLoggingOut] = useState(false);
  const [error, setError] = useState('');
  const [prefersDark, setPrefersDark] = useState(false);
  const menuButtonRef = useRef<HTMLButtonElement>(null);
  const closeDrawer = useCallback(() => setDrawerOpen(false), []);
  const colorMode = context.settings.colorMode === 'system' ? (prefersDark ? 'dark' : 'light') : context.settings.colorMode;

  useEffect(() => {
    const media = window.matchMedia('(prefers-color-scheme: dark)');
    const updatePreference = () => setPrefersDark(media.matches);
    updatePreference();
    media.addEventListener('change', updatePreference);
    return () => media.removeEventListener('change', updatePreference);
  }, []);

  const themeStyle = getTenantThemeStyle(context.settings.colorTheme, colorMode);

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