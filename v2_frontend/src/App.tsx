import { useEffect, useState } from 'react';
import { LoginScreen } from './components/login/LoginScreen';
import { WorkspaceShell } from './components/workspace/WorkspaceShell';
import { getStaffAppContext, loginStaff, logoutStaff, warmCsrfToken, type StaffAppContext } from './lib/api';

type SessionState = 'checking' | 'signed-out' | 'signed-in' | 'unavailable';

export default function App() {
  const [session, setSession] = useState<SessionState>('checking');
  const [connectionError, setConnectionError] = useState('');
  const [context, setContext] = useState<StaffAppContext | null>(null);

  useEffect(() => {
    let active = true;

    async function initialize() {
      try {
        const staffContext = await getStaffAppContext();
        if (!staffContext) await warmCsrfToken();
        if (active) {
          setContext(staffContext);
          setSession(staffContext ? 'signed-in' : 'signed-out');
        }
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
    const staffContext = await getStaffAppContext();
    if (!staffContext) throw new Error('Your sign-in could not be verified. Please try again.');
    setConnectionError('');
    setContext(staffContext);
    setSession('signed-in');
  }

  async function handleLogout() {
    await warmCsrfToken(true);
    await logoutStaff();
    setConnectionError('');
    setContext(null);
    setSession('signed-out');
    await warmCsrfToken();
  }

  if (session === 'checking') {
    return <main className="app-loading" aria-label="Loading FitFlow"><span className="loading-indicator" aria-hidden="true" /> Connecting to FitFlow…</main>;
  }

  if (session === 'signed-in' && context) return <WorkspaceShell context={context} onLogout={handleLogout} />;

  return <LoginScreen onLogin={handleLogin} connectionError={session === 'unavailable' ? connectionError : ''} />;
}