import type { AriaAttributes } from 'react';
import { TENANT_DATE_FORMATS, TENANT_TIME_FORMATS, type TenantDateFormat, type TenantTimeFormat } from '../../lib/api';
import { formatDate, formatTime } from '../../lib/dateTime';
import { OptionField } from './OptionField';

type SharedFormatFieldProps = {
  id: string;
  name?: string;
  className?: string;
  disabled?: boolean;
  required?: boolean;
  'aria-invalid'?: AriaAttributes['aria-invalid'];
  'aria-describedby'?: string;
};

type DateFormatFieldProps = SharedFormatFieldProps & {
  value: TenantDateFormat;
  onChange: (value: TenantDateFormat) => void;
};

export function DateFormatField({ value, onChange, ...selectProps }: DateFormatFieldProps) {
  return (
    <OptionField<TenantDateFormat>
      id={selectProps.id}
      label="Date format"
      name={selectProps.name}
      className={selectProps.className}
      isDisabled={selectProps.disabled}
      required={selectProps.required}
      aria-invalid={selectProps['aria-invalid']}
      aria-describedby={selectProps['aria-describedby']}
      value={value}
      onValueChange={onChange}
      options={TENANT_DATE_FORMATS.map((format) => ({ value: format, label: `${format} · ${formatDate(new Date(2026, 4, 28), format)}` }))}
    />
  );
}

type TimeFormatFieldProps = SharedFormatFieldProps & {
  value: TenantTimeFormat;
  onChange: (value: TenantTimeFormat) => void;
};

export function TimeFormatField({ value, onChange, ...selectProps }: TimeFormatFieldProps) {
  return (
    <OptionField<TenantTimeFormat>
      id={selectProps.id}
      label="Time format"
      name={selectProps.name}
      className={selectProps.className}
      isDisabled={selectProps.disabled}
      required={selectProps.required}
      aria-invalid={selectProps['aria-invalid']}
      aria-describedby={selectProps['aria-describedby']}
      value={value}
      onValueChange={onChange}
      options={TENANT_TIME_FORMATS.map((format) => ({ value: format, label: `${format} · ${formatTime(new Date(2026, 4, 28, 14, 30), format)}` }))}
    />
  );
}
