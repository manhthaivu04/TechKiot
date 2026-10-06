import { useState, useMemo } from 'react';
import { Button, Typography } from 'antd';
import { PlusOutlined } from '@ant-design/icons';
import { StockDocumentTable } from '../components/StockDocumentTable';
import { StockDocumentDetailDrawer } from '../components/StockDocumentDetailDrawer';
import { CreateStockDocumentModal } from '../components/CreateStockDocumentModal';
import { useStockDocuments } from '../services/stock-document.hooks';
import type { StockDocument } from '@/types';

const { Title, Text } = Typography;

export const StockDocumentPage = () => {
    // --- State ---
    const [isCreateOpen, setIsCreateOpen] = useState(false);
    const [selectedDocument, setSelectedDocument] = useState<StockDocument | null>(null);

    // --- Queries ---
    const { data: docData, isLoading } = useStockDocuments();
    const documents = useMemo(() => docData?.data || [], [docData?.data]);

    return (
        <div>
            {/* Page Header */}
            <div style={{
                display: 'flex',
                justifyContent: 'space-between',
                alignItems: 'center',
                marginBottom: 24,
            }}>
                <div>
                    <Title level={3} style={{ margin: 0, color: '#1f2937' }}>
                        Chứng từ kho
                    </Title>
                    <Text type="secondary" style={{ fontSize: 14 }}>
                        Quản lý phiếu nhập, xuất, điều chỉnh, hủy và trả hàng
                    </Text>
                </div>
                <Button
                    type="primary"
                    icon={<PlusOutlined />}
                    onClick={() => setIsCreateOpen(true)}
                    size="large"
                    style={{
                        background: '#10b981',
                        borderColor: '#10b981',
                        borderRadius: 8,
                        fontWeight: 500,
                    }}
                >
                    Tạo phiếu mới
                </Button>
            </div>

            {/* Table */}
            <div style={{
                background: '#fff',
                borderRadius: 12,
                padding: 24,
            }}>
                <StockDocumentTable
                    documents={documents}
                    isLoading={isLoading}
                    onViewDetail={setSelectedDocument}
                />
            </div>

            {/* Detail Drawer */}
            <StockDocumentDetailDrawer
                open={!!selectedDocument}
                onClose={() => setSelectedDocument(null)}
                document={selectedDocument}
            />

            {/* Create Modal */}
            <CreateStockDocumentModal
                open={isCreateOpen}
                onClose={() => setIsCreateOpen(false)}
            />
        </div>
    );
};
