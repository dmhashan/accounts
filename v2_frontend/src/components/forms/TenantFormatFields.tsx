import type { SelectHTMLAttributes } from 'react';
import { TENANT_DATE_FORMATS, TENANT_TIME_FORMATS, type TenantDateFormat, type TenantTimeFormat } from '../../lib/api';
import { formatDate, formatTime } from '../../lib/dateTime';

type DateFormatFieldProps = Omit<SelectHTMLAttributes<HTMLSelectElement>, 'value' | 'onChange'> & {
  id: string;
  value: TenantDateFormat;
  onChange: (value: TenantDateFormat) => void;
};

export function DateFormatField({ value, onChange, ...selectProps }: DateFormatFieldProps) {
  return (
    <div className="field-group">
      <label htmlFor={selectProps.id}>Date format</label>
      <select
        {...selectProps}
        value={value}
        onChange={(event) => onChange(event.target.value as TenantDateFormat)}
      >
        {TENANT_DATE_FORMATS.map((format) => (
          <option value={format} key={format}>{format} · {formatDate(new Date(2026, 4, 28), format)}</option>
        ))}
      </select>
    </div>
  );
}

type TimeFormatFieldProps = Omit<SelectHTMLAttributes<HTMLSelectElement>, 'value' | 'onChange'> & {
  id: string;
  value: TenantTimeFormat;
  onChange: (value: TenantTimeFormat) => void;
};

export function TimeFormatField({ value, onChange, ...selectProps }: TimeFormatFieldProps) {
  return (
    <div className="field-group">
      <label htmlFor={selectProps.id}>Time format</label>
      <select
        {...selectProps}
        value={value}
        onChange={(event) => onChange(event.target.value as TenantTimeFormat)}
      >
        {TENANT_TIME_FORMATS.map((format) => (
          <option value={format} key={format}>{format} · {formatTime(new Date(2026, 4, 28, 14, 30), format)}</option>
        ))}
      </select>
    </div>
  );
}
