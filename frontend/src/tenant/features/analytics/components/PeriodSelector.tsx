import { Segmented, DatePicker, Space } from 'antd';
import dayjs, { Dayjs } from 'dayjs';
import type { AnalyticsPeriod, AnalyticsParams } from '../types/analytics.types';

interface PeriodSelectorProps {
    params: AnalyticsParams;
    onChange: (params: AnalyticsParams) => void;
}

const PERIOD_OPTIONS = [
    { label: 'Hôm nay', value: 'today' },
    { label: 'Tuần này', value: 'week' },
    { label: 'Tháng này', value: 'month' },
    { label: 'Tùy chỉnh', value: 'custom' },
];

/**
 * PeriodSelector — Component chọn khoảng thời gian thống kê.
 * Dùng Ant Design Segmented + RangePicker.
 */
export const PeriodSelector = ({ params, onChange }: PeriodSelectorProps) => {
    const handlePeriodChange = (value: string | number) => {
        const period = value as AnalyticsPeriod;
        if (period !== 'custom') {
            onChange({ period });
        } else {
            onChange({
                period,
                start_date: dayjs().startOf('month').format('YYYY-MM-DD'),
                end_date: dayjs().format('YYYY-MM-DD'),
            });
        }
    };

    const handleRangeChange = (dates: [Dayjs | null, Dayjs | null] | null) => {
        if (dates && dates[0] && dates[1]) {
            onChange({
                period: 'custom',
                start_date: dates[0].format('YYYY-MM-DD'),
                end_date: dates[1].format('YYYY-MM-DD'),
            });
        }
    };

    return (
        <Space wrap>
            <Segmented
                options={PERIOD_OPTIONS}
                value={params.period}
                onChange={handlePeriodChange}
            />
            {params.period === 'custom' && (
                <DatePicker.RangePicker
                    format="DD/MM/YYYY"
                    value={
                        params.start_date && params.end_date
                            ? [dayjs(params.start_date), dayjs(params.end_date)]
                            : null
                    }
                    onChange={handleRangeChange}
                    disabledDate={(current) => current && current > dayjs().endOf('day')}
                    allowClear={false}
                />
            )}
        </Space>
    );
};
