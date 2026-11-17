import { ArrowRight, LoaderCircle } from 'lucide-react';
import { useState, type FormEvent } from 'react';
import { PasswordField, TextField } from '../forms/FormField';
import { Alert, Button } from '../ui';

type LoginFormProps = {
  onLogin: (login: string, password: string) => Promise<void>;
  connectionError: string;
};

export function LoginForm({ onLogin, connectionError }: LoginFormProps) {
  const [login, setLogin] = useState('');
  const [password, setPassword] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState('');

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setError('');
    setSubmitting(true);

    try {
      await onLogin(login.trim(), password);
    } catch (reason) {
      setError(reason instanceof Error ? reason.message : 'Sign in failed. Please try again.');
    } finally {
      setSubmitting(false);
    }
  }

  const visibleError = error || connectionError;

  return (
    <>
      {visibleError && (
        <Alert tone="error" className="form-alert" role="alert" aria-live="assertive">
          <span className="alert-mark" aria-hidden="true">!</span>
          <span>{visibleError}</span>
        </Alert>
      )}

      <form className="login-form" onSubmit={handleSubmit}>
        <TextField
          id="login"
          name="login"
          label="Email or username"
          type="text"
          autoComplete="username"
          autoCapitalize="none"
          spellCheck={false}
          placeholder="name@yourgym.com"
          value={login}
          onChange={(event) => setLogin(event.target.value)}
          disabled={submitting}
          required
        />

        <PasswordField
          id="password"
          name="password"
          label="Password"
          autoComplete="current-password"
          placeholder="Enter your password"
          value={password}
          onChange={(event) => setPassword(event.target.value)}
          disabled={submitting}
          required
        />

        <Button className="submit-button" tone="primary" type="submit" disabled={submitting || !login.trim() || !password}>
          {submitting ? (
            <><LoaderCircle className="spin" size={18} aria-hidden="true" /> Signing in…</>
          ) : (
            <>Sign in <ArrowRight size={18} aria-hidden="true" /></>
          )}
        </Button>
      </form>
    </>
  );
}