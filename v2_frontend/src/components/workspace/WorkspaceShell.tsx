import { lazy, Suspense, useCallback, useEffect, useRef, useState } from 'react';
import type { StaffAppContext } from '../../lib/api';
import { getTenantThemeStyle } from '../../lib/theme';
import { WorkspaceDrawer } from './WorkspaceDrawer';
import { WorkspaceHeader } from './WorkspaceHeader';
import { WorkspaceSidebar } from './WorkspaceSidebar';
import { WorkspaceWelcome } from './WorkspaceWelcome';

const MembersPage = lazy(() => import('../../features/members/MembersPage'));
const PaymentsPage = lazy(() => import('../../features/payments/PaymentsPage').then((module) => ({ default: module.PaymentsPage })));
const POSPage = lazy(() => import('../../features/pos/POSPage'));

function currentPath(): string {
  return window.location.hash.slice(1).split('?')[0] || '/dashboard';
}

type WorkspaceShellProps = {
  context: StaffAppContext;
  onLogout: () => Promise<void>;
};

export function WorkspaceShell({ context, onLogout }: WorkspaceShellProps) {
  const [drawerOpen, setDrawerOpen] = useState(false);
  const [loggingOut, setLoggingOut] = useState(false);
  const [error, setError] = useState('');
  const [prefersDark, setPrefersDark] = useState(false);
  const [path, setPath] = useState(currentPath);
  const [posVisited, setPosVisited] = useState(() => currentPath() === '/pos');
  const menuButtonRef = useRef<HTMLButtonElement>(null);
  const closeDrawer = useCallback(() => setDrawerOpen(false), []);
  const navigate = useCallback((destination: string) => {
    if (currentPath() === destination) return;
    window.location.hash = destination;
  }, []);
  const colorMode = context.settings.colorMode === 'system' ? (prefersDark ? 'dark' : 'light') : context.settings.colorMode;

  useEffect(() => {
    const updatePath = () => {
      const next = currentPath();
      if (next === '/pos') setPosVisited(true);
      setPath(next);
    };
    window.addEventListener('hashchange', updatePath);
    return () => window.removeEventListener('hashchange', updatePath);
  }, []);

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
      <WorkspaceSidebar context={context} onLogout={() => void handleLogout()} loggingOut={loggingOut} path={path} navigate={navigate} />
      <section className="workspace-main" aria-label={`${context.tenant.name} staff workspace`}>
        <WorkspaceHeader
          context={context}
          onOpenMenu={() => setDrawerOpen(true)}
          menuButtonRef={menuButtonRef}
          onLogout={() => void handleLogout()}
          loggingOut={loggingOut}
          pageTitle={path.startsWith('/members') ? 'Members' : path === '/payments' ? 'Payments' : path === '/pos' ? 'POS' : 'Dashboard'}
        />
        {error && <div className="shell-alert" role="alert" aria-live="assertive">{error}</div>}
        <Suspense fallback={<div className="app-loading" role="status">Loading page…</div>}>
          {path === '/dashboard' ? <WorkspaceWelcome context={context} />
            : path === '/members' || path.startsWith('/members/') ? <MembersPage context={context} path={path} navigate={navigate} />
              : path === '/payments' ? <PaymentsPage context={context} navigate={navigate} />
                : path === '/pos' ? null
                : <div className="workspace-content"><h1>Page not found</h1><button type="button" className="nav-item" onClick={() => navigate('/dashboard')}>Back to dashboard</button></div>}
          {posVisited && <div hidden={path !== '/pos'}><POSPage context={context} /></div>}
        </Suspense>
      </section>
      {drawerOpen && (
        <WorkspaceDrawer
          context={context}
          onClose={closeDrawer}
          onLogout={() => void handleLogout()}
          loggingOut={loggingOut}
          menuButtonRef={menuButtonRef}
          path={path}
          navigate={navigate}
        />
      )}
    </main>
  );
}