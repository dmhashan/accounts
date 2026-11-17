import { CreditCard, Dumbbell, House, Package, ShieldCheck, ShoppingBag, UsersRound } from 'lucide-react';
import type { StaffAppContext } from '../../lib/api';

type Props = { context: StaffAppContext; path: string; navigate: (path: string) => void };

export function WorkspaceNavigation({ context, path, navigate }: Props) {
  const permissions = context.permissions;
  const items = [
    { label: 'Dashboard', path: '/dashboard', icon: House, allowed: true, available: true },
    { label: 'Members', path: '/members', icon: UsersRound, allowed: !!permissions.members, available: true },
    { label: 'Payments', path: '/payments', icon: CreditCard, allowed: !!permissions.paymentsManage || !!permissions.sales, available: true },
    { label: 'POS', path: '/pos', icon: ShoppingBag, allowed: !!permissions.salesOutstanding || !!permissions.salesCreate || !!permissions.salesEdit, available: true },
    { label: 'Inventory management', path: '/inventory', icon: Package, allowed: !!permissions.inventory, available: false },
    { label: 'Coach Portal', path: '/coach', icon: Dumbbell, allowed: true, available: false },
    { label: 'Admin Portal', path: '/admin-portal', icon: ShieldCheck, allowed: true, available: false },
  ];

  return (
    <nav className="workspace-nav" aria-label="Main navigation">
      {items.filter((item) => item.allowed).map(({ label, path: destination, icon: Icon, available }) => {
        const active = path === destination || path.startsWith(`${destination}/`);
        return <button key={destination} className={`nav-item${active ? ' is-active' : ''}`} type="button"
          disabled={!available} title={!available ? 'Not available yet' : undefined}
          aria-current={active ? 'page' : undefined} onClick={() => navigate(destination)}>
          <Icon size={19} strokeWidth={1.9} aria-hidden="true" /><span>{label}</span>
          {!available && <small className="nav-coming-soon">Soon</small>}
        </button>;
      })}
    </nav>
  );
}