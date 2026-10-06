import { RouterProvider } from 'react-router-dom';
import { QueryClientProvider } from '@tanstack/react-query';
import { ConfigProvider, App as AntdApp } from 'antd';
import viVN from 'antd/locale/vi_VN';
import { router } from './router';
import { queryClient } from './api/query-client';
import { AntdStaticBridge } from './lib/AntdStaticBridge';
import './App.css';

function App() {
  return (
    <QueryClientProvider client={queryClient}>
      <ConfigProvider locale={viVN}>
        {/* AntdApp bắt buộc để notification/message/modal static API hoạt động trong Ant Design v5 */}
        <AntdApp>
          {/* Bridge lấy instance từ context, không render UI */}
          <AntdStaticBridge />
          <RouterProvider router={router} />
        </AntdApp>
      </ConfigProvider>
    </QueryClientProvider>
  );
}

export default App;
