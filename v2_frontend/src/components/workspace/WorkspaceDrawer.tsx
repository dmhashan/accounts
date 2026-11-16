import { X } from 'lucide-react';
import { useEffect, useRef } from 'react';
import type { StaffAppContext } from '../../lib/api';
import { Brand } from '../Brand';
import { PoweredByFitFlow } from './PoweredByFitFlow';
import { WorkspaceNavigation } from './WorkspaceNavigation';

type WorkspaceDrawerProps = {
  context: StaffAppContext;
  onClose: () => void;
  onLogout: () => void;
  loggingOut: boolean;
  menuButtonRef: React.RefObject<HTMLButtonElement | null>;
};

export function WorkspaceDrawer({ context, onClose, onLogout, loggingOut, menuButtonRef }: WorkspaceDrawerProps) {
  const drawerRef = useRef<HTMLElement>(null);
  const closeRef = useRef<HTMLButtonElement>(null);

  useEffect(() => {
    const previousFocus = document.activeElement as HTMLElement | null;
    closeRef.current?.focus();

    function onKeyDown(event: KeyboardEvent) {
      if (event.key === 'Escape') {
        event.preventDefault();
        onClose();
        menuButtonRef.current?.focus();
        return;
      }
      if (event.key !== 'Tab' || !drawerRef.current) return;

      const focusable = Array.from(drawerRef.current.querySelectorAll<HTMLElement>(
        'button:not(:disabled), a[href], [tabindex]:not([tabindex="-1"])',
      ));
      const first = focusable[0];
      const last = focusable[focusable.length - 1];
      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last?.focus();
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first?.focus();
      }
    }

    document.addEventListener('keydown', onKeyDown);
    return () => {
      document.removeEventListener('keydown', onKeyDown);
      if (previousFocus?.isConnected) previousFocus.focus();
    };
  }, [menuButtonRef, onClose]);

  return (
    <div className="drawer-layer">
      <button className="drawer-backdrop" type="button" aria-label="Close navigation" onClick={onClose} />
      <aside ref={drawerRef} className="mobile-drawer" role="dialog" aria-modal="true" aria-label="Workspace navigation">
        <div className="drawer-heading">
          <Brand compact tenantName={context.tenant.name} logoUrl={context.tenant.logo_url} />
          <button ref={closeRef} className="icon-button drawer-close" type="button" aria-label="Close navigation" onClick={onClose}>
            <X size={20} aria-hidden="true" />
          </button>
        </div>
        <div className="sidebar-caption">WORKSPACE</div>
        <WorkspaceNavigation />
        <PoweredByFitFlow />
        <button className="signout-button drawer-signout" type="button" onClick={onLogout} disabled={loggingOut}>
          <span>{loggingOut ? 'Signing out…' : 'Sign out'}</span>
        </button>
      </aside>
    </div>
  );
}