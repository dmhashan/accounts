import { LogOut, LoaderCircle } from 'lucide-react';
import { Brand } from '../Brand';
import { WorkspaceNavigation } from './WorkspaceNavigation';

type WorkspaceSidebarProps = {
  onLogout: () => void;
  loggingOut: boolean;
};

export function WorkspaceSidebar({ onLogout, loggingOut }: WorkspaceSidebarProps) {
  return (
    <aside className="sidebar" aria-label="Workspace sidebar">
      <div className="sidebar-brand"><Brand compact /></div>
      <div className="sidebar-caption">WORKSPACE</div>
      <WorkspaceNavigation />
      <button className="signout-button sidebar-signout" type="button" onClick={onLogout} disabled={loggingOut}>
        {loggingOut ? <LoaderCircle className="spin" size={18} aria-hidden="true" /> : <LogOut size={18} aria-hidden="true" />}
        <span>{loggingOut ? 'Signing out…' : 'Sign out'}</span>
      </button>
    </aside>
  );
}