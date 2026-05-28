import { inject } from 'vue';

const MONTHS_SHORT = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

function applyDateFmt(date, fmt) {
    const d = date.getDate();
    const m = date.getMonth();
    const y = date.getFullYear();
    const mm = String(m + 1).padStart(2, '0');
    const dd = String(d).padStart(2, '0');

    switch (fmt) {
        case 'DD/MM/YYYY':  return `${dd}/${mm}/${y}`;
        case 'MM/DD/YYYY':  return `${mm}/${dd}/${y}`;
        case 'YYYY-MM-DD':  return `${y}-${mm}-${dd}`;
        case 'MMM D, YYYY': return `${MONTHS_SHORT[m]} ${d}, ${y}`;
        default:            return `${d} ${MONTHS_SHORT[m]} ${y}`; // D MMM YYYY
    }
}

function applyTimeFmt(date, fmt) {
    const h = date.getHours();
    const min = String(date.getMinutes()).padStart(2, '0');
    if (fmt === 'h:mm A') {
        const hour12 = h % 12 || 12;
        return `${hour12}:${min} ${h < 12 ? 'AM' : 'PM'}`;
    }
    return `${String(h).padStart(2, '0')}:${min}`;
}

export function useDateTimeFormat() {
    const ctx = inject('appContext', {});
    const dateFmt = ctx.settings?.dateFormat ?? 'D MMM YYYY';
    const timeFmt = ctx.settings?.timeFormat ?? 'HH:mm';

    function parseValue(value) {
        const str = String(value);
        // Date-only strings (YYYY-MM-DD) must be parsed as local midnight to avoid
        // UTC→local day shift in negative-UTC-offset timezones.
        return new Date(/^\d{4}-\d{2}-\d{2}$/.test(str) ? `${str}T00:00:00` : str);
    }

    function formatDate(value, fallback = 'Not provided') {
        if (!value) return fallback;
        const date = parseValue(value);
        if (Number.isNaN(date.getTime())) return String(value);
        return applyDateFmt(date, dateFmt);
    }

    function formatDateTime(value, fallback = '—') {
        if (!value) return fallback;
        const date = parseValue(value);
        if (Number.isNaN(date.getTime())) return String(value);
        return `${applyDateFmt(date, dateFmt)} ${applyTimeFmt(date, timeFmt)}`;
    }

    function formatTime(value, fallback = '—') {
        if (!value) return fallback;
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return String(value);
        return applyTimeFmt(date, timeFmt);
    }

    return { formatDate, formatDateTime, formatTime };
}
