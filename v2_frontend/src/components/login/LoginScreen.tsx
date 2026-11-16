import { LockKeyhole, ShieldCheck } from 'lucide-react';
import { Brand } from '../Brand';
import { LoginForm } from './LoginForm';

type LoginScreenProps = {
  onLogin: (login: string, password: string) => Promise<void>;
  connectionError: string;
};

export function LoginScreen({ onLogin, connectionError }: LoginScreenProps) {
  return (
    <main className="login-page">
      <header className="login-topbar">
        <Brand adminLabel />
        <span className="tenant-label"><span aria-hidden="true" /> TENANT WORKSPACE</span>
      </header>

      <section className="login-layout" aria-label="FitFlow Admin sign in">
        <div className="login-intro">
          <div className="intro-icon" aria-hidden="true"><LockKeyhole size={21} strokeWidth={1.8} /></div>
          <p className="intro-eyebrow">YOUR GYM, IN FLOW</p>
          <h1>Good work<br />starts <span>here.</span></h1>
          <p className="intro-description">A clear space for the people who keep your community moving.</p>
          <div className="intro-note"><ShieldCheck size={17} aria-hidden="true" /> A secure space for your team</div>
          <div className="intro-art" aria-hidden="true"><span /><span /><span /></div>
        </div>

        <section className="login-panel" aria-labelledby="login-title">
          <div className="mobile-brand"><Brand adminLabel /></div>
          <div className="login-card">
            <div className="login-card-heading">
              <div className="login-kicker">WELCOME BACK</div>
              <h2 id="login-title">Sign in</h2>
              <p>Enter your account details to access your workspace.</p>
            </div>

            <LoginForm onLogin={onLogin} connectionError={connectionError} />

            <div className="login-security"><ShieldCheck size={16} aria-hidden="true" /> Your sign-in is protected by your gym’s secure session.</div>
          </div>
          <footer className="login-footer">© {new Date().getFullYear()} FitFlow <span>·</span> Made for movement</footer>
        </section>
      </section>
    </main>
  );
}