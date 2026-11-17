import { Textarea } from 'flowbite-react';
import type { ReactNode, TextareaHTMLAttributes } from 'react';
import { cx, textareaTheme } from './fieldStyles';
import { FormField, type FieldError } from './FormField';

export type TextareaFieldProps = Omit<TextareaHTMLAttributes<HTMLTextAreaElement>, 'id'> & {
  id: string;
  label: ReactNode;
  optional?: boolean;
  error?: FieldError;
  errorId?: string;
  className?: string;
  labelClassName?: string;
  textareaClassName?: string;
};

export function TextareaField({ id, label, optional, required, error, errorId, className, labelClassName, textareaClassName, ...textareaProps }: TextareaFieldProps) {
  return <FormField id={id} label={label} optional={optional} required={required} error={error} errorId={errorId} className={className} labelClassName={labelClassName}><Textarea id={id} required={required} {...textareaProps} theme={{ ...textareaTheme, base: cx(textareaTheme.base, 'resize-y', textareaClassName) }} /></FormField>;
}
