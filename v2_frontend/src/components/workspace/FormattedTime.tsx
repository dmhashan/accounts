import type { TenantTimeFormat } from '../../lib/api';
import { formatTime, parseDateTime, type DateTimeInput } from '../../lib/dateTime';

type FormattedTimeProps = {
  value: DateTimeInput;
  format: TenantTimeFormat;
  fallback?: string;
};

export function FormattedTime({ value, format, fallback = '—' }: FormattedTimeProps) {
  const date = parseDateTime(value);
  return <time dateTime={date?.toISOString()}>{formatTime(value, format, fallback)}</time>;
}