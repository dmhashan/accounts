import { TextInput } from 'flowbite-react';
import { Eye, EyeOff } from 'lucide-react';
import { useState, type InputHTMLAttributes, type ReactNode } from 'react';
import { cx, textInputTheme } from './fieldStyles';
import { FormField, type FieldError } from './FormField';

export type PasswordFieldProps = Omit<InputHTMLAttributes<HTMLInputElement>, 'id' | 'type'> & {
  id: string;
  label: ReactNode;
  optional?: boolean;
  error?: FieldError;
  errorId?: string;
  className?: string;
  labelClassName?: string;
  inputClassName?: string;
};

export function PasswordField({ id, label, optional, required, error, errorId, className, labelClassName, inputClassName, disabled, ...inputProps }: PasswordFieldProps) {
  const [visible, setVisible] = useState(false);
  return (
    <FormField id={id} label={label} optional={optional} required={required} error={error} errorId={errorId} className={className} labelClassName={labelClassName}>
      <div className="relative min-w-0">
        <TextInput id={id} type={visible ? 'text' : 'password'} required={required} disabled={disabled} {...inputProps} theme={{ ...textInputTheme, field: { ...textInputTheme.field, input: { ...textInputTheme.field?.input, base: cx(textInputTheme.field?.input?.base, 'pr-12', inputClassName) } } }} />
        <button type="button" aria-label={visible ? 'Hide password' : 'Show password'} aria-pressed={visible} onClick={() => setVisible((current) => !current)} disabled={disabled} className="absolute right-0.5 top-0.5 grid size-10 place-items-center rounded-lg text-[var(--muted)] outline-none hover:bg-[var(--surface-hover)] hover:text-[var(--text)] focus-visible:ring-[3px] focus-visible:ring-[color-mix(in_srgb,var(--blue)_35%,transparent)] disabled:cursor-not-allowed disabled:opacity-55">
          {visible ? <EyeOff size={18} aria-hidden="true" /> : <Eye size={18} aria-hidden="true" />}
        </button>
      </div>
    </FormField>
  );
}
