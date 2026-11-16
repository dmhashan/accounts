import type { TenantDateFormat, TenantTimeFormat } from './api';

const shortMonths = new Intl.DateTimeFormat('en', { month: 'short' });
export type DateTimeInput = Date | string | number;

export function parseDateTime(value: DateTimeInput): Date | null {
  if (value instanceof Date) return Number.isNaN(value.getTime()) ? null : value;
  if (typeof value === 'string') {
    const dateOnly = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value);
    if (dateOnly) {
      const year = Number(dateOnly[1]);
      const month = Number(dateOnly[2]);
      const day = Number(dateOnly[3]);
      const localDate = new Date(year, month - 1, day);
      return localDate.getFullYear() === year && localDate.getMonth() === month - 1 && localDate.getDate() === day ? localDate : null;
    }
  }
  const date = new Date(value);
  return Number.isNaN(date.getTime()) ? null : date;
}

function twoDigits(value: number): string {
  return String(value).padStart(2, '0');
}

export function formatDate(value: DateTimeInput, format: TenantDateFormat, fallback = '—'): string {
  const date = parseDateTime(value);
  if (!date) return fallback;

  const day = date.getDate();
  const month = date.getMonth() + 1;
  const year = date.getFullYear();
  const monthName = shortMonths.format(date);

  switch (format) {
    case 'DD/MM/YYYY': return `${twoDigits(day)}/${twoDigits(month)}/${year}`;
    case 'MM/DD/YYYY': return `${twoDigits(month)}/${twoDigits(day)}/${year}`;
    case 'YYYY-MM-DD': return `${year}-${twoDigits(month)}-${twoDigits(day)}`;
    case 'MMM D, YYYY': return `${monthName} ${day}, ${year}`;
    case 'D MMM YYYY': return `${day} ${monthName} ${year}`;
  }
}

export function formatTime(value: DateTimeInput, format: TenantTimeFormat, fallback = '—'): string {
  const date = parseDateTime(value);
  if (!date) return fallback;

  const hours = date.getHours();
  const minutes = twoDigits(date.getMinutes());
  if (format === 'HH:mm') return `${twoDigits(hours)}:${minutes}`;

  const period = hours >= 12 ? 'PM' : 'AM';
  const hour = hours % 12 || 12;
  return `${hour}:${minutes} ${period}`;
}

export function formatDateTime(value: DateTimeInput, dateFormat: TenantDateFormat, timeFormat: TenantTimeFormat, fallback = '—'): string {
  const date = parseDateTime(value);
  if (!date) return fallback;
  return `${formatDate(date, dateFormat, fallback)} · ${formatTime(date, timeFormat, fallback)}`;
}