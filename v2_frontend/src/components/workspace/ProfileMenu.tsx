import { LoaderCircle, LogOut } from 'lucide-react';
import type { StaffAppContext } from '../../lib/api';
import { Dropdown } from '../ui';
import { Avatar } from './Avatar';

type ProfileMenuProps = {
  context: StaffAppContext;
  onLogout: () => void;
  loggingOut: boolean;
};

export function ProfileMenu({ context, onLogout, loggingOut }: ProfileMenuProps) {
  const trigger = (
    <button
      className="profile-trigger"
      type="button"
      aria-label={`Open profile menu for ${context.user.name}`}
    >
      <Avatar name={context.user.name} />
    </button>
  );
  return (
    <div className="profile-menu-wrap">
      <Dropdown
        label="Profile menu"
        customTrigger={trigger}
        placement="bottom-end"
        dismissOnClick
        className="profile-dropdown"
      >
        <Dropdown.Header className="profile-summary">
          <Avatar name={context.user.name} size="large" />
          <span className="profile-details">
            <strong>{context.user.name}</strong>
            <small>{context.user.email}</small>
            <small>{context.tenant.name}</small>
          </span>
        </Dropdown.Header>
        <Dropdown.Divider />
        <Dropdown.Item
          as="button"
          className="profile-menu-action"
          onClick={onLogout}
          disabled={loggingOut}
        >
          {loggingOut ? (
            <LoaderCircle className="spin" size={17} aria-hidden="true" />
          ) : (
            <LogOut size={17} aria-hidden="true" />
          )}
          <span>{loggingOut ? 'Signing out…' : 'Sign out'}</span>
        </Dropdown.Item>
      </Dropdown>
    </div>
  );
}