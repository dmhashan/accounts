import { Drawer, DrawerHeader, DrawerItems } from 'flowbite-react';
import type { StaffAppContext } from '../../lib/api';
import { Brand } from '../Brand';
import { Button } from '../ui';
import { PoweredByFitFlow } from './PoweredByFitFlow';
import { WorkspaceNavigation } from './WorkspaceNavigation';

type WorkspaceDrawerProps = {
  context: StaffAppContext;
  onClose: () => void;
  onLogout: () => void;
  loggingOut: boolean;
  menuButtonRef: React.RefObject<HTMLButtonElement | null>;
  path: string;
  navigate: (path: string) => void;
};

export function WorkspaceDrawer({ context, onClose, onLogout, loggingOut, menuButtonRef, path, navigate }: WorkspaceDrawerProps) {
  function close() {
    onClose();
    window.requestAnimationFrame(() => menuButtonRef.current?.focus());
  }

  return (
    <Drawer open onClose={close} position="left" className="mobile-drawer" aria-label="Workspace navigation">
      <DrawerHeader className="drawer-heading">
        <Brand compact tenantName={context.tenant.name} logoUrl={context.tenant.logo_url} />
      </DrawerHeader>
      <DrawerItems className="flex min-h-0 flex-1 flex-col">
        <div className="sidebar-caption">WORKSPACE</div>
        <WorkspaceNavigation context={context} path={path} navigate={(destination) => { navigate(destination); close(); }} />
        <PoweredByFitFlow />
        <Button tone="ghost" className="signout-button drawer-signout" type="button" onClick={onLogout} disabled={loggingOut}>
          <span>{loggingOut ? 'Signing out…' : 'Sign out'}</span>
        </Button>
      </DrawerItems>
    </Drawer>
  );
}