import { Button, Result } from 'antd';
import { LockOutlined } from '@ant-design/icons';
import { useNavigate } from 'react-router-dom';

interface UpgradeRequiredPageProps {
    title?: string;
    subTitle?: string;
}

export const UpgradeRequiredPage = ({
    title = 'Tính năng cao cấp',
    subTitle = 'Gói cước hiện tại của bạn không hỗ trợ tính năng này. Vui lòng nâng cấp gói cước để tiếp tục sử dụng.',
}: UpgradeRequiredPageProps) => {
    const navigate = useNavigate();

    return (
        <div className="flex items-center justify-center h-full w-full bg-gray-50">
            <Result
                icon={<LockOutlined className="text-yellow-500" />}
                title={<span className="text-2xl font-bold text-gray-800">{title}</span>}
                subTitle={<span className="text-base text-gray-500">{subTitle}</span>}
                extra={[
                    <Button type="primary" key="upgrade" size="large" onClick={() => navigate('/portal/settings')}>
                        Nâng cấp gói cước
                    </Button>,
                    <Button key="back" size="large" onClick={() => navigate(-1)}>
                        Quay lại
                    </Button>,
                ]}
                className="bg-white p-12 rounded-2xl shadow-sm border border-gray-100 max-w-2xl"
            />
        </div>
    );
};
