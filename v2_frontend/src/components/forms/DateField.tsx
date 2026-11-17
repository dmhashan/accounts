import { TextInput } from 'flowbite-react';
import type { InputHTMLAttributes, ReactNode } from 'react';
import { cx, textInputTheme } from './fieldStyles';
import { FormField, type FieldError } from './FormField';

export type DateFieldProps = Omit<InputHTMLAttributes<HTMLInputElement>, 'id' | 'type'> & {
  id: string;
  label: ReactNode;
  optional?: boolean;
  error?: FieldError;
  errorId?: string;
  className?: string;
  labelClassName?: string;
  inputClassName?: string;
};

export function DateField({ id, label, optional, required, error, errorId, className, labelClassName, inputClassName, ...inputProps }: DateFieldProps) {
  return <FormField id={id} label={label} optional={optional} required={required} error={error} errorId={errorId} className={className} labelClassName={labelClassName}><TextInput id={id} type="date" required={required} {...inputProps} theme={{ ...textInputTheme, field: { ...textInputTheme.field, input: { ...textInputTheme.field?.input, base: cx(textInputTheme.field?.input?.base, inputClassName) } } }} /></FormField>;
}
