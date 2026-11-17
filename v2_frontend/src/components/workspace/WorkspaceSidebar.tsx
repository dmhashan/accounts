import { LogOut, LoaderCircle } from 'lucide-react';
import type { StaffAppContext } from '../../lib/api';
import { Brand } from '../Brand';
import { PoweredByFitFlow } from './PoweredByFitFlow';
import { WorkspaceNavigation } from './WorkspaceNavigation';

type WorkspaceSidebarProps = {
  context: StaffAppContext;
  onLogout: () => void;
  loggingOut: boolean;
  path: string;
  navigate: (path: string) => void;
};

export function WorkspaceSidebar({ context, onLogout, loggingOut, path, navigate }: WorkspaceSidebarProps) {
  return (
    <aside className="sidebar" aria-label="Workspace sidebar">
      <div className="sidebar-brand"><Brand compact tenantName={context.tenant.name} logoUrl={context.tenant.logo_url} /></div>
      <div className="sidebar-caption">WORKSPACE</div>
      <WorkspaceNavigation context={context} path={path} navigate={navigate} />
      <PoweredByFitFlow />
      <button className="signout-button sidebar-signout" type="button" onClick={onLogout} disabled={loggingOut}>
        {loggingOut ? <LoaderCircle className="spin" size={18} aria-hidden="true" /> : <LogOut size={18} aria-hidden="true" />}
        <span>{loggingOut ? 'Signing out…' : 'Sign out'}</span>
      </button>
    </aside>
  );
}