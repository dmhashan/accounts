import { useEffect, useState } from 'react';
import { LoginScreen } from './components/login/LoginScreen';
import { WorkspaceShell } from './components/workspace/WorkspaceShell';
import { hasStaffSession, loginStaff, logoutStaff, warmCsrfToken } from './lib/api';

type SessionState = 'checking' | 'signed-out' | 'signed-in' | 'unavailable';

export default function App() {
  const [session, setSession] = useState<SessionState>('checking');
  const [connectionError, setConnectionError] = useState('');

  useEffect(() => {
    let active = true;

    async function initialize() {
      try {
        const signedIn = await hasStaffSession();
        if (!signedIn) await warmCsrfToken();
        if (active) setSession(signedIn ? 'signed-in' : 'signed-out');
      } catch {
        if (active) {
          setConnectionError('Could not connect to FitFlow. Check your connection and try again.');
          setSession('unavailable');
        }
      }
    }

    void initialize();
    return () => { active = false; };
  }, []);

  async function handleLogin(login: string, password: string) {
    await warmCsrfToken();
    await loginStaff(login, password);
    setConnectionError('');
    setSession('signed-in');
  }

  async function handleLogout() {
    await warmCsrfToken(true);
    await logoutStaff();
    setConnectionError('');
    setSession('signed-out');
    await warmCsrfToken();
  }

  if (session === 'checking') {
    return <main className="app-loading" aria-label="Loading FitFlow"><span className="loading-indicator" aria-hidden="true" /> Connecting to FitFlow…</main>;
  }

  if (session === 'signed-in') return <WorkspaceShell onLogout={handleLogout} />;

  return <LoginScreen onLogin={handleLogin} connectionError={session === 'unavailable' ? connectionError : ''} />;
}