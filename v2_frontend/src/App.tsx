import { useEffect, useState } from 'react';
import { LoginScreen } from './components/login/LoginScreen';
import { WorkspaceShell } from './components/workspace/WorkspaceShell';
import { getAppContext, loginStaff, logoutStaff, warmCsrfToken, type AppContext, type StaffAppContext } from './lib/api';

type SessionState = 'checking' | 'signed-out' | 'signed-in' | 'unavailable';

export default function App() {
  const [session, setSession] = useState<SessionState>('checking');
  const [connectionError, setConnectionError] = useState('');
  const [context, setContext] = useState<AppContext | null>(null);

  useEffect(() => {
    let active = true;

    async function initialize() {
      try {
        const appContext = await getAppContext();
        if (!appContext?.user) await warmCsrfToken();
        if (active) {
          setContext(appContext);
          setSession(appContext?.user ? 'signed-in' : 'signed-out');
        }
      } catch (reason) {
        if (active) {
          setConnectionError(reason instanceof Error && reason.name === 'ApiError'
            ? reason.message
            : 'Could not connect to FitFlow. Check your connection and try again.');
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
    const staffContext = await getAppContext();
    if (!staffContext?.user) throw new Error('Your sign-in could not be verified. Please try again.');
    setConnectionError('');
    setContext(staffContext);
    setSession('signed-in');
  }

  async function handleLogout() {
    await warmCsrfToken(true);
    await logoutStaff();
    setConnectionError('');
    const tenantContext = await getAppContext();
    setContext(tenantContext);
    setSession('signed-out');
    await warmCsrfToken();
  }

  if (session === 'checking') {
    return <main className="app-loading" aria-label="Loading FitFlow"><span className="loading-indicator" aria-hidden="true" /> Connecting to FitFlow…</main>;
  }

  if (session === 'signed-in' && context?.user) {
    return <WorkspaceShell context={context as StaffAppContext} onLogout={handleLogout} />;
  }

  return <LoginScreen context={context} onLogin={handleLogin} connectionError={session === 'unavailable' ? connectionError : ''} />;
}