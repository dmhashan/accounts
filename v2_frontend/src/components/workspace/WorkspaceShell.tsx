import { useCallback, useRef, useState } from 'react';
import { WorkspaceDrawer } from './WorkspaceDrawer';
import { WorkspaceHeader } from './WorkspaceHeader';
import { WorkspaceSidebar } from './WorkspaceSidebar';

type WorkspaceShellProps = {
  onLogout: () => Promise<void>;
};

export function WorkspaceShell({ onLogout }: WorkspaceShellProps) {
  const [drawerOpen, setDrawerOpen] = useState(false);
  const [loggingOut, setLoggingOut] = useState(false);
  const [error, setError] = useState('');
  const menuButtonRef = useRef<HTMLButtonElement>(null);
  const closeDrawer = useCallback(() => setDrawerOpen(false), []);

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
    <main className="workspace-shell">
      <WorkspaceSidebar onLogout={() => void handleLogout()} loggingOut={loggingOut} />
      <section className="workspace-main" aria-label="FitFlow admin workspace">
        <WorkspaceHeader
          onOpenMenu={() => setDrawerOpen(true)}
          menuButtonRef={menuButtonRef}
          onLogout={() => void handleLogout()}
          loggingOut={loggingOut}
        />
        {error && <div className="shell-alert" role="alert" aria-live="assertive">{error}</div>}
        <div className="workspace-empty">
          <span className="empty-overline">FITFLOW ADMIN</span>
          <h1>Your workspace is ready</h1>
          <p>Your team’s workspace will appear here.</p>
        </div>
      </section>
      {drawerOpen && (
        <WorkspaceDrawer
          onClose={closeDrawer}
          onLogout={() => void handleLogout()}
          loggingOut={loggingOut}
          menuButtonRef={menuButtonRef}
        />
      )}
    </main>
  );
}