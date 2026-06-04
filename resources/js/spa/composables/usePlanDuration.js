/**
 * Helpers for working with the configurable plan duration model:
 * a `duration_value` (integer) + `duration_unit` ('day' | 'week' | 'month' | 'year').
 *
 * Note: month/year arithmetic is calendar-aware to match the backend
 * (`PaymentPlan::endDateFrom`).  1 month from Jan 31 → Feb 28/29.
 */

const UNIT_LABELS = {
    day: ['day', 'days'],
    week: ['week', 'weeks'],
    month: ['month', 'months'],
    year: ['year', 'years'],
};

export function formatPlanDuration(plan) {
    if (!plan) return '—';

    const unit = plan.duration_unit;
    const value = Number(plan.duration_value || 0);

    if (unit && value > 0 && UNIT_LABELS[unit]) {
        const [singular, plural] = UNIT_LABELS[unit];
        return `${value} ${value === 1 ? singular : plural}`;
    }

    // Fallback for legacy responses that only include duration_days
    const days = Number(plan.duration_days || 0);

    if (!days) return '—';
    if (days === 1)   return '1 day';
    if (days === 7)   return '1 week';
    if (days === 30)  return '1 month';
    if (days === 90)  return '3 months';
    if (days === 180) return '6 months';
    if (days === 365) return '1 year';
    return `${days} days`;
}

/**
 * Inclusive membership end date for the given plan starting on `startDate` (YYYY-MM-DD).
 * Mirrors `PaymentPlan::endDateFrom`: addUnits(value) − 1 day.
 * Returns '' if inputs are missing.
 */
export function calcPlanEndDate(startDate, plan) {
    if (!startDate || !plan) return '';

    const value = Math.max(1, Number(plan.duration_value || 0));
    const unit = plan.duration_unit;
    const d = new Date(startDate + 'T00:00:00');

    switch (unit) {
        case 'year':
            d.setFullYear(d.getFullYear() + value);
            break;
        case 'month':
            d.setMonth(d.getMonth() + value);
            break;
        case 'week':
            d.setDate(d.getDate() + value * 7);
            break;
        case 'day':
            d.setDate(d.getDate() + value);
            break;
        default: {
            // legacy: plan only carries duration_days
            const days = Number(plan.duration_days || 0);
            if (!days) return '';
            d.setDate(d.getDate() + days);
        }
    }

    // Inclusive end → subtract one day
    d.setDate(d.getDate() - 1);
    return d.toISOString().slice(0, 10);
}

/**
 * Day after the inclusive end date — useful as the next start date.
 */
export function calcNextStartDate(startDate, plan) {
    const end = calcPlanEndDate(startDate, plan);
    if (!end) return '';
    const d = new Date(end + 'T00:00:00');
    d.setDate(d.getDate() + 1);
    return d.toISOString().slice(0, 10);
}

export const PLAN_UNIT_OPTIONS = [
    { value: 'day', label: 'Day(s)' },
    { value: 'week', label: 'Week(s)' },
    { value: 'month', label: 'Month(s)' },
    { value: 'year', label: 'Year(s)' },
];
