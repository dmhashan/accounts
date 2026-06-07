export function normalizeBaseUrl(value: string) {
  const trimmed = value.trim().replace(/\/+$/, '');
  if (!trimmed) return '';
  if (/^https?:\/\//i.test(trimmed)) return trimmed;
  return `https://${trimmed}`;
}

export function money(value: unknown) {
  if (value === null || value === undefined || value === '') return '0.00';
  const numeric = typeof value === 'number' ? value : Number(String(value).replace(/,/g, ''));
  if (Number.isNaN(numeric)) return String(value);
  return numeric.toLocaleString(undefined, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  });
}

export function greeting() {
  const hour = new Date().getHours();
  if (hour >= 5 && hour < 12) return 'Good morning';
  if (hour >= 12 && hour < 18) return 'Good afternoon';
  if (hour >= 18 && hour < 22) return 'Good evening';
  return 'Hello';
}

export function firstName(name: string) {
  return name.trim().split(/\s+/)[0] || name;
}

export function capitalize(value?: string | null) {
  if (!value) return '-';
  return value.charAt(0).toUpperCase() + value.slice(1);
}
