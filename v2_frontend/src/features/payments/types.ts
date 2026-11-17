export type Page<T> = {
  data: T[];
  meta: { current_page: number; last_page: number; per_page: number; total: number };
};

export type Payment = {
  id: number; member_name: string; member_phone: string | null;
  account_name: string | null; payment_method_name: string | null;
  payment_plan_name: string | null; amount: number; paid_amount: number; balance: number;
  payment_date: string | null; start_date: string | null; end_date: string | null;
  reference_number: string | null; notes: string | null; is_paid: boolean;
  type: 'membership' | 'other'; settlement_status: string | null;
  settlement_gross_amount?: number | null; settlement_deduction_amount?: number | null; settlement_net_amount?: number | null;
};

export type Sale = {
  id: number; customer_name: string | null; customer_type: string | null;
  payment_method_name: string | null; reference_number: string | null;
  total_amount: number; paid_amount: number; balance: number; is_paid: boolean;
  settlement_status: string | null; created_at: string | null;
  account_id?: number | null;
  items: { product_name: string | null; variation_name: string | null; quantity: number; unit_price: number; subtotal: number }[];
};

export type PaymentMeta = {
  members: { id: number; label: string }[];
  payment_methods: { id: number; name: string; account_name: string | null }[];
  plans: { id: number; name: string; price: number }[];
};

export type Preset = 'month' | 'last-month' | 'three-months' | 'today' | 'yesterday' | 'week' | 'last-week' | 'custom';

export function localDate(date: Date): string {
  return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
}

export function dateRange(preset: Preset, from: string, to: string): [string, string] {
  const now = new Date();
  const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
  const monday = new Date(today);
  monday.setDate(today.getDate() - ((today.getDay() + 6) % 7));
  const previous = new Date(today);
  previous.setDate(today.getDate() - 1);
  const lastMonday = new Date(monday);
  lastMonday.setDate(monday.getDate() - 7);
  const lastSunday = new Date(monday);
  lastSunday.setDate(monday.getDate() - 1);
  switch (preset) {
    case 'today': return [localDate(today), localDate(today)];
    case 'yesterday': return [localDate(previous), localDate(previous)];
    case 'week': return [localDate(monday), localDate(today)];
    case 'last-week': return [localDate(lastMonday), localDate(lastSunday)];
    case 'last-month': return [localDate(new Date(today.getFullYear(), today.getMonth() - 1, 1)), localDate(new Date(today.getFullYear(), today.getMonth(), 0))];
    case 'three-months': return [localDate(new Date(today.getFullYear(), today.getMonth() - 2, 1)), localDate(today)];
    case 'custom': return [from, to];
    default: return [localDate(new Date(today.getFullYear(), today.getMonth(), 1)), localDate(today)];
  }
}

// Sales are serialized by Laravel as "d M Y, H:i", not ISO. Reject invalid dates.
export function parseSaleDate(value: string | null): Date | null {
  if (!value) return null;
  const match = /^(\d{2}) (Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec) (\d{4}), (\d{2}):(\d{2})$/.exec(value);
  if (!match) return null;
  const month = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'].indexOf(match[2]);
  const year = Number(match[3]); const day = Number(match[1]); const hour = Number(match[4]); const minute = Number(match[5]);
  const date = new Date(year, month, day, hour, minute);
  return date.getFullYear() === year && date.getMonth() === month && date.getDate() === day && date.getHours() === hour && date.getMinutes() === minute ? date : null;
}