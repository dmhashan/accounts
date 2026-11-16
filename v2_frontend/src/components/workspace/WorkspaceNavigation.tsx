import { House } from 'lucide-react';

export function WorkspaceNavigation() {
  return (
    <nav className="workspace-nav" aria-label="Main navigation">
      <button className="nav-item is-active" type="button" aria-current="page">
        <House size={19} strokeWidth={1.9} aria-hidden="true" />
        <span>Workspace</span>
      </button>
    </nav>
  );
}