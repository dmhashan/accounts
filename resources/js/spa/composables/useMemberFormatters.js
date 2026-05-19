const moneyFormatter = new Intl.NumberFormat(undefined, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});

export function useMemberFormatters() {
    function capitalize(value = '') {
        return value ? value.charAt(0).toUpperCase() + value.slice(1) : '';
    }

    function displayValue(value) {
        return value === null || value === undefined || value === '' ? 'Not provided' : value;
    }

    function formatDate(value) {
        if (!value) return 'Not provided';
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return value;
        return date.toLocaleDateString(undefined, { year: 'numeric', month: 'long', day: 'numeric' });
    }

    function formatMoney(value) {
        if (value === null || value === undefined || value === '') return 'Not provided';
        return moneyFormatter.format(Number(value));
    }

    return { capitalize, displayValue, formatDate, formatMoney };
}
