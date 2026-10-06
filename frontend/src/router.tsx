import { createBrowserRouter } from 'react-router-dom';
import { UserRole } from '@/types/auth';

// Layouts
import { PublicLayout } from '@/layouts/PublicLayout';
import { SuperAdminLayout } from '@/super-admin/layouts/SuperAdminLayout';
import { TenantLayout } from '@/tenant/layouts/TenantLayout';
import { MenuLayout } from '@/public-menu/layouts/MenuLayout';

// Auth
import { LoginPage } from '@/auth/pages/LoginPage';
import { ProtectedRoute } from '@/auth/pages/ProtectedRoute';

// Errors
import { NotFoundPage } from '@/pages/errors/NotFoundPage';
import { UnauthorizedPage } from '@/pages/errors/UnauthorizedPage';
import { UpgradeRequiredPage } from '@/pages/errors/UpgradeRequiredPage';

// Public Landing
import { PublicHomePage } from '@/pages/PublicHomePage';

// Super Admin Zone
import { SuperAdminDashboard } from '@/super-admin/pages/SuperAdminDashboard';
import { RestaurantListPage } from '@/super-admin/features/restaurants/pages/RestaurantListPage';
import { RestaurantCreatePage } from '@/super-admin/features/restaurants/pages/RestaurantCreatePage';
import { RestaurantDetailPage } from '@/super-admin/features/restaurants/pages/RestaurantDetailPage';
import { RestaurantEditPage } from '@/super-admin/features/restaurants/pages/RestaurantEditPage';
import { FeatureListPage } from '@/super-admin/features/feature-flags/pages/FeatureListPage';
import { FeatureCreatePage } from '@/super-admin/features/feature-flags/pages/FeatureCreatePage';
import { FeatureEditPage } from '@/super-admin/features/feature-flags/pages/FeatureEditPage';
import { PackageListPage } from '@/super-admin/features/packages/pages/PackageListPage';
import { PackageCreatePage } from '@/super-admin/features/packages/pages/PackageCreatePage';
import { PackageDetailPage } from '@/super-admin/features/packages/pages/PackageDetailPage';
import { PackageEditPage } from '@/super-admin/features/packages/pages/PackageEditPage';

// Tenant Zone
import { TenantDashboard } from '@/tenant/pages/TenantDashboard';
import { OrderListPage } from '@/tenant/features/orders/pages/OrderListPage';

// Public Menu Zone
import { MenuPage } from '@/public-menu/features/menu/pages/MenuPage';
import { OrderTrackingPage } from '@/public-menu/features/menu/pages/OrderTrackingPage';

import { MenuManagementPage } from '@/tenant/features/menu/pages/MenuManagementPage';
import { TableAreaPage } from '@/tenant/features/tables/pages/TableAreaPage';
import { RestaurantTablePage } from '@/tenant/features/tables/pages/RestaurantTablePage';
import { StaffListPage } from '@/tenant/features/staff/pages/StaffListPage';
import { PaymentMethodSettingsPage } from '@/tenant/features/settings/pages/PaymentMethodSettingsPage';
import { QrStaticSettingsPage } from '@/tenant/features/settings/pages/QrStaticSettingsPage';
import { WarehousePage } from '@/tenant/features/inventory/pages/WarehousePage';
import { InventoryDashboardPage } from '@/tenant/features/inventory/pages/InventoryDashboardPage';
import { StockDocumentPage } from '@/tenant/features/inventory/pages/StockDocumentPage';
import { FeatureGuard } from '@/auth/components/FeatureGuard';
import { RoleGuard } from '@/auth/components/RoleGuard';
import { FeatureCode } from '@/types';

export const router = createBrowserRouter([
    {
        path: '/login',
        element: <LoginPage />,
    },
    {
        path: '/unauthorized',
        element: <UnauthorizedPage />,
    },
    
    // --- SUPER ADMIN ZONE ---
    {
        path: '/super-admin',
        element: (
            <ProtectedRoute allowedRoles={[UserRole.SUPER_ADMIN]}>
                <SuperAdminLayout />
            </ProtectedRoute>
        ),
        children: [
            {
                index: true,
                element: <SuperAdminDashboard />,
            },
            {
                path: 'restaurants',
                children: [
                    { index: true, element: <RestaurantListPage /> },
                    { path: 'new', element: <RestaurantCreatePage /> },
                    { path: ':id', element: <RestaurantDetailPage /> },
                    { path: ':id/edit', element: <RestaurantEditPage /> },
                ],
            },
            {
                path: 'features',
                children: [
                    { index: true, element: <FeatureListPage /> },
                    { path: 'new', element: <FeatureCreatePage /> },
                    { path: ':id/edit', element: <FeatureEditPage /> },
                ],
            },
            {
                path: 'packages',
                children: [
                    { index: true, element: <PackageListPage /> },
                    { path: 'new', element: <PackageCreatePage /> },
                    { path: ':id', element: <PackageDetailPage /> },
                    { path: ':id/edit', element: <PackageEditPage /> },
                ],
            },
        ],
    },

    // --- TENANT ZONE ---
    {
        path: '/portal',
        element: (
            <ProtectedRoute allowedRoles={[UserRole.OWNER, UserRole.MANAGER, UserRole.WAITER, UserRole.KITCHEN, UserRole.CASHIER]}>
                <TenantLayout />
            </ProtectedRoute>
        ),
        children: [
            {
                index: true,
                element: <TenantDashboard />,
            },
            {
                path: 'menu',
                element: (
                    <RoleGuard allowedRoles={[UserRole.OWNER, UserRole.MANAGER]}>
                        <FeatureGuard feature={FeatureCode.MENU_MANAGEMENT} fallback={<UnauthorizedPage />}>
                            <MenuManagementPage />
                        </FeatureGuard>
                    </RoleGuard>
                ),
            },
            {
                path: 'table-areas',
                element: (
                    <RoleGuard allowedRoles={[UserRole.OWNER]}>
                        <FeatureGuard feature={FeatureCode.TABLE_MANAGEMENT} fallback={<UnauthorizedPage />}>
                            <TableAreaPage />
                        </FeatureGuard>
                    </RoleGuard>
                ),
            },
            {
                path: 'restaurant-tables',
                element: (
                    <RoleGuard allowedRoles={[UserRole.OWNER]}>
                        <FeatureGuard feature={FeatureCode.TABLE_MANAGEMENT} fallback={<UnauthorizedPage />}>
                            <RestaurantTablePage />
                        </FeatureGuard>
                    </RoleGuard>
                ),
            },
            {
                path: 'staff',
                element: (
                    <RoleGuard allowedRoles={[UserRole.OWNER, UserRole.MANAGER]}>
                        <FeatureGuard feature={FeatureCode.STAFF_MANAGEMENT} fallback={<UpgradeRequiredPage />}>
                            <StaffListPage />
                        </FeatureGuard>
                    </RoleGuard>
                ),
            },
            {
                path: 'orders',
                children: [
                    {
                        index: true,
                        element: (
                            <FeatureGuard
                                feature={FeatureCode.POS_QUICK_ORDER}
                                fallback={<UnauthorizedPage />}
                            >
                                <OrderListPage />
                            </FeatureGuard>
                        ),
                    },
                ],
            },
            {
                path: 'settings',
                children: [
                    {
                        index: true,
                        element: (
                            <RoleGuard allowedRoles={[UserRole.OWNER, UserRole.MANAGER]}>
                                <PaymentMethodSettingsPage />
                            </RoleGuard>
                        ),
                    },
                    {
                        path: 'qr-static',
                        element: (
                            <RoleGuard allowedRoles={[UserRole.OWNER, UserRole.MANAGER]}>
                                <FeatureGuard feature={FeatureCode.QR_STATIC_ORDER} fallback={<UnauthorizedPage />}>
                                    <QrStaticSettingsPage />
                                </FeatureGuard>
                            </RoleGuard>
                        ),
                    },
                ],
            },
            {
                path: 'inventory',
                element: (
                    <RoleGuard allowedRoles={[UserRole.OWNER, UserRole.MANAGER]}>
                        <FeatureGuard feature={FeatureCode.INVENTORY_MANAGEMENT} fallback={<UpgradeRequiredPage />}>
                            <InventoryDashboardPage />
                        </FeatureGuard>
                    </RoleGuard>
                ),
            },
            {
                path: 'warehouses',
                element: (
                    <RoleGuard allowedRoles={[UserRole.OWNER, UserRole.MANAGER]}>
                        <FeatureGuard feature={FeatureCode.INVENTORY_MANAGEMENT} fallback={<UpgradeRequiredPage />}>
                            <WarehousePage />
                        </FeatureGuard>
                    </RoleGuard>
                ),
            },
            {
                path: 'stock-documents',
                element: (
                    <RoleGuard allowedRoles={[UserRole.OWNER, UserRole.MANAGER]}>
                        <FeatureGuard feature={FeatureCode.INVENTORY_MANAGEMENT} fallback={<UpgradeRequiredPage />}>
                            <StockDocumentPage />
                        </FeatureGuard>
                    </RoleGuard>
                ),
            },
        ],
    },

    // --- PUBLIC MENU ZONE ---
    {
        path: '/menu',
        element: <MenuLayout />,
        children: [
            {
                // /menu?public_token=xxx&type=qr_static|qr_table
                index: true,
                element: <MenuPage />,
            },
            {
                // /menu/order-tracking/:orderId?public_token=xxx
                path: 'order-tracking/:orderId',
                element: <OrderTrackingPage />,
            },
        ],
    },
    // Legacy slug-based route kept for backward compat
    {
        path: '/menu/:restaurantSlug',
        element: <MenuLayout />,
        children: [
            { index: true, element: <MenuPage /> },
            { path: ':tableId', element: <MenuPage /> },
        ],
    },

    // --- LANDING PAGE ---
    {
        path: '/',
        element: <PublicLayout />,
        children: [
            {
                index: true,
                element: <PublicHomePage />,
            },
        ],
    },

    // --- NOT FOUND ---
    {
        path: '*',
        element: <NotFoundPage />,
    },
]);
