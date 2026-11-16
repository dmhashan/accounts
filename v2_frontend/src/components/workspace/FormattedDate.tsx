import type { TenantDateFormat } from '../../lib/api';
import { formatDate, parseDateTime, type DateTimeInput } from '../../lib/dateTime';

type FormattedDateProps = {
  value: DateTimeInput;
  format: TenantDateFormat;
  fallback?: string;
};

export function FormattedDate({ value, format, fallback = '—' }: FormattedDateProps) {
  const date = parseDateTime(value);
  return <time dateTime={date?.toISOString()}>{formatDate(value, format, fallback)}</time>;
}