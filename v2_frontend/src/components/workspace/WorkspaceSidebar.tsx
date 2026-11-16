import { LogOut, LoaderCircle } from 'lucide-react';
import type { StaffAppContext } from '../../lib/api';
import { Brand } from '../Brand';
import { PoweredByFitFlow } from './PoweredByFitFlow';
import { WorkspaceNavigation } from './WorkspaceNavigation';

type WorkspaceSidebarProps = {
  context: StaffAppContext;
  onLogout: () => void;
  loggingOut: boolean;
};

export function WorkspaceSidebar({ context, onLogout, loggingOut }: WorkspaceSidebarProps) {
  return (
    <aside className="sidebar" aria-label="Workspace sidebar">
      <div className="sidebar-brand"><Brand compact tenantName={context.tenant.name} logoUrl={context.tenant.logo_url} poweredBy /></div>
      <div className="sidebar-caption">WORKSPACE</div>
      <WorkspaceNavigation />
      <PoweredByFitFlow />
      <button className="signout-button sidebar-signout" type="button" onClick={onLogout} disabled={loggingOut}>
        {loggingOut ? <LoaderCircle className="spin" size={18} aria-hidden="true" /> : <LogOut size={18} aria-hidden="true" />}
        <span>{loggingOut ? 'Signing out…' : 'Sign out'}</span>
      </button>
    </aside>
  );
}