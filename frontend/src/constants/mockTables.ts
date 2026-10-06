import type { Table } from '../types';

/**
 * Danh sách bàn ảo (Mock Tables)
 * Vì backend hiện tại chưa xây dựng chức năng Quản lý Bàn (Table Management API - module Pro),
 * chúng ta sử dụng danh sách tĩnh ở Frontend với UUID ngẫu nhiên (hoặc cố định) 
 * để gán `table_id` cho các đơn hàng "Tại bàn".
 */
export const MOCK_TABLES: Table[] = [
    { id: 't-001', name: 'Bàn 1', status: 'available' },
    { id: 't-002', name: 'Bàn 2', status: 'available' },
    { id: 't-003', name: 'Bàn 3', status: 'available' },
    { id: 't-004', name: 'Bàn 4', status: 'available' },
    { id: 't-005', name: 'Bàn 5', status: 'available' },
    { id: 't-006', name: 'Bàn 6', status: 'available' },
    { id: 't-007', name: 'Bàn 7', status: 'available' },
    { id: 't-008', name: 'Bàn 8', status: 'available' },
    { id: 't-009', name: 'Bàn 9', status: 'available' },
    { id: 't-010', name: 'Bàn 10', status: 'available' },
    { id: 't-011', name: 'VIP 1', status: 'available' },
    { id: 't-012', name: 'VIP 2', status: 'available' },
];
