import dayjs from 'dayjs';
import utc from 'dayjs/plugin/utc';
import timezone from 'dayjs/plugin/timezone';

dayjs.extend(utc);
dayjs.extend(timezone);

export const formatDate = (date: string | null | undefined): string => {
    if (!date) return '—';
    return dayjs(date).format('DD/MM/YYYY');
};

export const formatDateTime = (
    date: string | null | undefined,
    format = 'DD/MM/YYYY HH:mm'
): string => {
    if (!date) return '—';
    return dayjs(date).format(format);
};

export const formatCurrency = (value: number | string): string => {
    const num = typeof value === 'string' ? parseFloat(value) : value;
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(num);
};

export const getDaysRemaining = (endDate: string | null | undefined): number => {
    if (!endDate) return 0;
    return dayjs(endDate).diff(dayjs(), 'day');
};

export const isSubscriptionExpired = (
    endDate: string | null | undefined
): boolean => {
    if (!endDate) return false;
    return dayjs(endDate).isBefore(dayjs());
};
