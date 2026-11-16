import { Menu } from 'lucide-react';
import { ProfileMenu } from './ProfileMenu';

type WorkspaceHeaderProps = {
  onOpenMenu: () => void;
  menuButtonRef: React.RefObject<HTMLButtonElement | null>;
  onLogout: () => void;
  loggingOut: boolean;
};

export function WorkspaceHeader({ onOpenMenu, menuButtonRef, onLogout, loggingOut }: WorkspaceHeaderProps) {
  return (
    <header className="workspace-topbar">
      <button ref={menuButtonRef} className="icon-button menu-button" type="button" aria-label="Open navigation menu" onClick={onOpenMenu}>
        <Menu size={21} aria-hidden="true" />
      </button>
      <div className="workspace-breadcrumb"><strong>Workspace</strong></div>
      <ProfileMenu onLogout={onLogout} loggingOut={loggingOut} />
    </header>
  );
}