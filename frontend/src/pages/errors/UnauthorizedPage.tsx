import { useNavigate } from 'react-router-dom';
import { Result, Button } from 'antd';

export const UnauthorizedPage = () => {
    const navigate = useNavigate();

    return (
        <Result
            status="403"
            title="403"
            subTitle="Bạn không có quyền truy cập trang này."
            extra={
                <Button type="primary" onClick={() => navigate('/login')}>
                    Đăng nhập
                </Button>
            }
        />
    );
};
