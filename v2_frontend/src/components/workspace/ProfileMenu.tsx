import { LoaderCircle, LogOut } from 'lucide-react';
import { useEffect, useRef, useState, type KeyboardEvent } from 'react';
import type { StaffAppContext } from '../../lib/api';
import { Avatar } from './Avatar';

type ProfileMenuProps = {
  context: StaffAppContext;
  onLogout: () => void;
  loggingOut: boolean;
};

export function ProfileMenu({ context, onLogout, loggingOut }: ProfileMenuProps) {
  const [open, setOpen] = useState(false);
  const triggerRef = useRef<HTMLButtonElement>(null);
  const menuRef = useRef<HTMLDivElement>(null);
  const logoutRef = useRef<HTMLButtonElement>(null);

  useEffect(() => {
    if (!open) return;
    logoutRef.current?.focus();

    function dismiss(event: PointerEvent) {
      if (!menuRef.current?.contains(event.target as Node) && !triggerRef.current?.contains(event.target as Node)) {
        setOpen(false);
      }
    }

    function onKeyDown(event: globalThis.KeyboardEvent) {
      if (event.key === 'Escape') {
        event.preventDefault();
        setOpen(false);
        triggerRef.current?.focus();
      }
    }

    document.addEventListener('pointerdown', dismiss);
    document.addEventListener('keydown', onKeyDown);
    return () => {
      document.removeEventListener('pointerdown', dismiss);
      document.removeEventListener('keydown', onKeyDown);
    };
  }, [open]);

  function handleMenuKeyDown(event: KeyboardEvent<HTMLDivElement>) {
    if (event.key === 'ArrowDown' || event.key === 'Home') {
      event.preventDefault();
      logoutRef.current?.focus();
    }
    if (event.key === 'ArrowUp' || event.key === 'End') {
      event.preventDefault();
      logoutRef.current?.focus();
    }
  }

  return (
    <div className="profile-menu-wrap">
      <button
        ref={triggerRef}
        className="profile-trigger"
        type="button"
        aria-label={`Open profile menu for ${context.user.name}`}
        aria-haspopup="menu"
        aria-expanded={open}
        onClick={() => setOpen((current) => !current)}
      >
        <Avatar name={context.user.name} />
      </button>

      {open && (
        <div ref={menuRef} className="profile-popover" role="menu" aria-label="Profile menu" onKeyDown={handleMenuKeyDown}>
          <div className="profile-summary">
            <Avatar name={context.user.name} size="large" />
            <span className="profile-details">
              <strong>{context.user.name}</strong>
              <small>{context.user.email}</small>
              <small>{context.tenant.name}</small>
            </span>
          </div>
          <div className="popover-divider" />
          <button
            ref={logoutRef}
            className="profile-menu-action"
            type="button"
            role="menuitem"
            onClick={() => {
              setOpen(false);
              onLogout();
            }}
            disabled={loggingOut}
          >
            {loggingOut ? <LoaderCircle className="spin" size={17} aria-hidden="true" /> : <LogOut size={17} aria-hidden="true" />}
            <span>{loggingOut ? 'Signing out…' : 'Sign out'}</span>
          </button>
        </div>
      )}
    </div>
  );
}