import { ArrowUpRight, Sparkles } from 'lucide-react';
import type { StaffAppContext } from '../../lib/api';
import { Brand } from '../Brand';
import { Card } from '../ui';

type WorkspaceWelcomeProps = {
  context: StaffAppContext;
};

function firstName(fullName: string): string {
  return fullName.trim().split(/\s+/)[0] || 'there';
}

export function WorkspaceWelcome({ context }: WorkspaceWelcomeProps) {
  return (
    <div className="workspace-content">
      <section className="workspace-welcome" aria-labelledby="workspace-welcome-title">
        <div className="welcome-copy">
          <span className="welcome-eyebrow"><Sparkles size={14} aria-hidden="true" /> STAFF WORKSPACE</span>
          <h1 id="workspace-welcome-title">Good to see you, <span>{firstName(context.user.name)}.</span></h1>
          <p>Your workspace for <strong>{context.tenant.name}</strong> is ready. More tools for your team will appear here as they’re added.</p>
          <div className="welcome-domain"><span className="tenant-status-dot" aria-hidden="true" /> Connected to <strong>{context.tenant.domain}</strong></div>
        </div>
        <Card className="welcome-brand-card" aria-label={`${context.tenant.name}, powered by FitFlow`}>
          <Brand tenantName={context.tenant.name} logoUrl={context.tenant.logo_url} poweredBy />
          <div className="welcome-brand-rule" />
          <span className="welcome-brand-caption">A better rhythm for your team.</span>
          <ArrowUpRight className="welcome-brand-arrow" size={19} aria-hidden="true" />
        </Card>
      </section>
      <div className="workspace-note"><span>YOUR WORKSPACE</span><p>This space is ready for your team. Your sign-in is protected by your tenant’s secure staff session.</p></div>
    </div>
  );
}