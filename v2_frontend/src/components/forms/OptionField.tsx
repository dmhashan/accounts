import type { AriaAttributes, ComponentProps, ReactNode } from 'react';
import { FormField, type FieldError } from './FormField';
import { SearchableSelect } from './SearchableSelect';

export type OptionFieldProps<TValue extends string = string> = Omit<ComponentProps<typeof SearchableSelect<TValue>>, 'inputId'> & {
  id: string;
  label: ReactNode;
  optional?: boolean;
  required?: boolean;
  error?: FieldError;
  errorId?: string;
  className?: string;
  labelClassName?: string;
};

export function OptionField<TValue extends string = string>({ id, label, optional, required, error, errorId, className, labelClassName, ...selectProps }: OptionFieldProps<TValue>) {
  const ariaRequired: AriaAttributes['aria-required'] = required || (selectProps as { 'aria-required'?: AriaAttributes['aria-required'] })['aria-required'];
  return <FormField id={id} label={label} optional={optional} required={required} error={error} errorId={errorId} className={className} labelClassName={labelClassName}><SearchableSelect<TValue> inputId={id} aria-required={ariaRequired} {...selectProps} /></FormField>;
}
