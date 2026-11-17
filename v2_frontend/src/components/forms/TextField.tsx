import { TextInput } from 'flowbite-react';
import type { InputHTMLAttributes, ReactNode } from 'react';
import { cx, textInputTheme } from './fieldStyles';
import { FormField, type FieldError } from './FormField';

export type TextFieldProps = Omit<InputHTMLAttributes<HTMLInputElement>, 'id' | 'type'> & {
  id: string;
  label: ReactNode;
  type?: 'text' | 'email' | 'tel' | 'search' | 'url';
  optional?: boolean;
  error?: FieldError;
  errorId?: string;
  className?: string;
  labelClassName?: string;
  inputClassName?: string;
  startAdornment?: ReactNode;
};

export function TextField({ id, label, type = 'text', optional, required, error, errorId, className, labelClassName, inputClassName, startAdornment, ...inputProps }: TextFieldProps) {
  return (
    <FormField id={id} label={label} optional={optional} required={required} error={error} errorId={errorId} className={className} labelClassName={labelClassName}>
      <div className={cx('relative min-w-0', !!startAdornment && 'group')}>
        {startAdornment && <span className="pointer-events-none absolute inset-y-0 left-3 z-[1] flex items-center text-[var(--muted)]">{startAdornment}</span>}
        <TextInput id={id} type={type} required={required} {...inputProps} theme={{ ...textInputTheme, field: { ...textInputTheme.field, input: { ...textInputTheme.field?.input, base: cx(textInputTheme.field?.input?.base, !!startAdornment && 'pl-10', inputClassName) } } }} />
      </div>
    </FormField>
  );
}
