import { useNavigate } from 'react-router-dom';
import { Result, Button } from 'antd';

export const NotFoundPage = () => {
    const navigate = useNavigate();

    return (
        <Result
            status="404"
            title="404"
            subTitle="Trang không tìm thấy."
            extra={
                <Button type="primary" onClick={() => navigate('/')}>
                    Quay lại trang chủ
                </Button>
            }
        />
    );
};
