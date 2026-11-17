import type { ReactNode } from 'react';
import { HelperText, Label } from 'flowbite-react';
import { cx } from './fieldStyles';

export type FieldError = ReactNode;

export type FormFieldProps = {
  id: string;
  label: ReactNode;
  optional?: boolean;
  required?: boolean;
  error?: FieldError;
  errorId?: string;
  className?: string;
  labelClassName?: string;
  children: ReactNode;
};

export function FormField({ id, label, optional, required, error, errorId, className, labelClassName, children }: FormFieldProps) {
  return (
    <div className={cx('grid min-w-0 gap-2', className)}>
      <Label htmlFor={id} className={cx('text-xs font-semibold text-[var(--text)]', labelClassName)}>
        {label}
        {required && <span className="text-[var(--danger)]" aria-hidden="true"> *</span>}
        {optional && <span className="ml-1 font-normal text-[var(--muted)]">(optional)</span>}
      </Label>
      {children}
      {error ? <HelperText id={errorId} color="failure" role="alert" theme={{ root: { base: 'mt-0 text-[11px] font-medium leading-5 text-[var(--danger)]', colors: { failure: 'text-[var(--danger)]' } } }}>{error}</HelperText> : null}
    </div>
  );
}

export { TextField } from './TextField';
export { PasswordField } from './PasswordField';