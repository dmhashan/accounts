import { useDateTimeFormat } from './useDateTimeFormat';

const moneyFormatter = new Intl.NumberFormat(undefined, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});

export function useMemberFormatters() {
    const { formatDate } = useDateTimeFormat();

    function capitalize(value = '') {
        return value ? value.charAt(0).toUpperCase() + value.slice(1) : '';
    }

    function displayValue(value) {
        return value === null || value === undefined || value === '' ? 'Not provided' : value;
    }

    function formatMoney(value) {
        if (value === null || value === undefined || value === '') return 'Not provided';
        return moneyFormatter.format(Number(value));
    }

    return { capitalize, displayValue, formatDate, formatMoney };
}
