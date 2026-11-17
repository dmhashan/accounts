import { Menu } from 'lucide-react';
import type { StaffAppContext } from '../../lib/api';
import { Button } from '../ui';
import { TenantDateTime } from './TenantDateTime';
import { ProfileMenu } from './ProfileMenu';

type WorkspaceHeaderProps = {
  context: StaffAppContext;
  onOpenMenu: () => void;
  menuButtonRef: React.RefObject<HTMLButtonElement | null>;
  onLogout: () => void;
  loggingOut: boolean;
  pageTitle: string;
};

export function WorkspaceHeader({ context, onOpenMenu, menuButtonRef, onLogout, loggingOut, pageTitle }: WorkspaceHeaderProps) {
  return (
    <header className="workspace-topbar">
      <Button ref={menuButtonRef} tone="ghost" className="icon-button menu-button" aria-label="Open navigation menu" onClick={onOpenMenu}>
        <Menu size={21} aria-hidden="true" />
      </Button>
      <div className="workspace-breadcrumb"><strong>{context.tenant.name}</strong><span>{pageTitle}</span></div>
      <TenantDateTime dateFormat={context.settings.dateFormat} timeFormat={context.settings.timeFormat} />
      <ProfileMenu context={context} onLogout={onLogout} loggingOut={loggingOut} />
    </header>
  );
}