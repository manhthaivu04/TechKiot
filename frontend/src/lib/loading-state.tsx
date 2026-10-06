import type { ReactNode } from 'react';
import { Empty, Spin, Alert } from 'antd';
import { getErrorMessage } from './error-handlers';

interface LoadingStateProps {
    isLoading: boolean;
    isEmpty: boolean;
    error: unknown;
    emptyMessage?: string;
    children: ReactNode;
}

export const LoadingState = ({
    isLoading,
    isEmpty,
    error,
    emptyMessage = 'Không có dữ liệu',
    children,
}: LoadingStateProps) => {
    if (isLoading) {
        return (
            <div className="flex justify-center items-center py-10">
                <Spin />
            </div>
        );
    }

    if (error) {
        return (
            <Alert
                message="Lỗi"
                description={getErrorMessage(error)}
                type="error"
                showIcon
            />
        );
    }

    if (isEmpty) {
        return <Empty description={emptyMessage} style={{ marginTop: 24 }} />;
    }

    return <>{children}</>;
};
