# KiotTay Admin Frontend

Ứng dụng quản lý admin dành cho hệ thống KiotTay, xây dựng với React 19, TypeScript, Vite và Ant Design.

## 📋 Mục Lục

- [Giới thiệu](#giới-thiệu)
- [Yêu cầu hệ thống](#yêu-cầu-hệ-thống)
- [Cài đặt](#cài-đặt)
- [Chạy ứng dụng](#chạy-ứng-dụng)
- [Build cho production](#build-cho-production)
- [Cấu trúc dự án](#cấu-trúc-dự-án)
- [Các tính năng](#các-tính-năng)
- [API Integration](#api-integration)
- [Quy ước code](#quy-ước-code)

## 🎯 Giới thiệu

KiotTay Admin Frontend là ứng dụng web quản lý hoàn chỉnh để quản trị:

- 🏪 **Nhà hàng**: Tạo, chỉnh sửa, khóa/mở khóa
- 🎯 **Tính năng**: Quản lý các tính năng hệ thống
- 📦 **Gói dịch vụ**: Quản lý gói subscription với tính năng
- 📊 **Subscriptions**: Gán và quản lý subscription của nhà hàng
- 👤 **Xác thực**: Đăng nhập/đăng xuất với Laravel Sanctum

## 🛠️ Yêu cầu hệ thống

- **Node.js**: >= 18.0.0
- **npm**: >= 9.0.0 hoặc **yarn**: >= 3.0.0
- **Backend API**: KiotTay Backend API (http://localhost:8000)

## 📦 Cài đặt

### 1. Clone Repository

```bash
git clone <repository-url>
cd kiottay-frontend
```

### 2. Cài đặt Dependencies

```bash
npm install
# hoặc
yarn install
```

### 3. Cấu hình Environment

Tạo file `.env` từ `.env.example`:

```bash
cp .env.example .env
```

Cập nhật giá trị trong `.env`:

```env
# Backend API URL
VITE_API_BASE_URL=http://localhost:8000/api
```

### 4. Xác minh Cài đặt

```bash
npm run build
```

## 🚀 Chạy ứng dụng

### Development Mode

```bash
npm run dev
```

Ứng dụng sẽ chạy tại: `http://localhost:5173`

### Hot Module Replacement (HMR)

Các thay đổi code sẽ tự động reload trên browser.

## 🏗️ Build cho Production

```bash
npm run build
```

Output sẽ được tạo trong thư mục `dist/`.

### Preview Build

```bash
npm run preview
```

## 📂 Cấu trúc Dự án

```
src/
├── api/                          # API client & configuration
│   ├── http.ts                   # Axios client với interceptors
│   └── query-client.ts           # React Query configuration
├── features/                     # Feature modules
│   ├── auth/                     # Authentication
│   │   ├── pages/
│   │   │   ├── LoginPage.tsx
│   │   │   └── ProtectedRoute.tsx
│   │   └── services/
│   │       ├── auth.service.ts
│   │       └── auth.hooks.ts
│   ├── restaurants/              # Restaurant management
│   │   ├── pages/
│   │   ├── components/
│   │   └── services/
│   ├── features/                 # Feature management
│   ├── packages/                 # Package management
│   └── subscriptions/            # Subscription management
├── layouts/                      # Layout components
│   └── AdminLayout.tsx
├── lib/                          # Utilities
│   ├── formatters.ts            # Date, currency formatters
│   ├── error-handlers.ts        # Error utilities
│   └── loading-state.tsx        # Loading state component
├── pages/                        # Global pages
│   ├── DashboardPage.tsx
│   └── errors/
│       ├── NotFoundPage.tsx
│       └── UnauthorizedPage.tsx
├── store/                        # Zustand stores
│   └── auth.store.ts
├── types/                        # TypeScript types
│   ├── auth.ts
│   ├── restaurant.ts
│   ├── feature.ts
│   ├── package.ts
│   ├── subscription.ts
│   └── api.ts
├── App.tsx                       # Main app component
├── main.tsx                      # Entry point
└── router.tsx                    # Route configuration
```

## 🎨 Các tính năng

### ✅ Implemented

- [x] Hệ thống xác thực đầy đủ (login/logout)
- [x] Protected routes với role-based access
- [x] Dashboard với thống kê cơ bản
- [x] Quản lý Nhà hàng (CRUD, lock/unlock)
- [x] Quản lý Tính năng (CRUD, toggle)
- [x] Quản lý Gói dịch vụ (CRUD, toggle, manage features)
- [x] Quản lý Subscription (assign, cancel, view history)
- [x] Tìm kiếm và lọc dữ liệu
- [x] Phân trang
- [x] Form validation (client + server)
- [x] Error handling
- [x] Loading states
- [x] Responsive design
- [x] Dark/Light theme support (via Ant Design)

### 📋 Pages

#### Authentication
- `/login` - Login page

#### Admin Dashboard
- `/` - Dashboard (overview, stats)

#### Restaurants
- `/restaurants` - Danh sách nhà hàng
- `/restaurants/new` - Tạo nhà hàng
- `/restaurants/:id` - Chi tiết nhà hàng (Overview + Subscriptions)
- `/restaurants/:id/edit` - Sửa nhà hàng

#### Features
- `/features` - Danh sách tính năng
- `/features/new` - Tạo tính năng
- `/features/:id/edit` - Sửa tính năng

#### Packages
- `/packages` - Danh sách gói dịch vụ
- `/packages/new` - Tạo gói dịch vụ
- `/packages/:id` - Chi tiết gói dịch vụ
- `/packages/:id/edit` - Sửa gói dịch vụ

#### Error Pages
- `/unauthorized` - 403 page
- `*` - 404 page

## 🔌 API Integration

### Base URL

Tất cả requests được gửi đến: `{VITE_API_BASE_URL}/`

Mặc định: `http://localhost:8000/api/`

### Authentication

- **Method**: Laravel Sanctum
- **Header**: `Authorization: Bearer {token}`
- **Token Storage**: localStorage (via Zustand)

### Interceptors

#### Request Interceptor
- Tự động thêm token vào header

#### Response Interceptor
- Xử lý lỗi 401 (clear token, redirect login)
- Xử lý lỗi chung

### Error Handling

Các lỗi API được xử lý như sau:

```typescript
{
  message: string;
  errors?: Record<string, string[]>; // Form validation errors
}
```

## 🔧 Tech Stack

### Frontend Framework
- **React** 19.2.5
- **TypeScript** 6.0.2
- **Vite** 8.0.9

### UI & Styling
- **Ant Design** 5.17.3
- **Ant Design Icons** 5.3.7
- **Tailwind CSS** 3.4.1

### State Management
- **Zustand** 5.1.0 - Auth state
- **React Query** (@tanstack/react-query) 5.56.2 - Server state

### HTTP Client
- **Axios** 1.7.7

### Form & Validation
- **React Hook Form** 7.52.0
- **Zod** 3.23.8

### Utilities
- **Day.js** 1.11.13 - Date formatting
- **React Router** 7.0.0 - Routing

## 📝 Quy ước Code

### Naming Conventions

- **Components**: PascalCase (e.g., `RestaurantListPage.tsx`)
- **Hooks**: camelCase prefixed with `use` (e.g., `useRestaurants`)
- **Services**: camelCase with `.service` suffix (e.g., `restaurant.service.ts`)
- **Stores**: camelCase with `.store` suffix (e.g., `auth.store.ts`)
- **Types**: PascalCase (e.g., `Restaurant`, `CreateRestaurantRequest`)

### File Organization

- Một component per file
- Related hooks cùng file với service
- Services tách riêng từ components

### TypeScript

- Định nghĩa type cho tất cả props/state
- Sử dụng `type` cho object types, `interface` cho extensible types
- Không dùng `any` type

### React

- Functional components with hooks
- Custom hooks để logic tái sử dụng
- Memoization khi cần thiết

### Styling

- Ant Design components là ưu tiên
- Tailwind CSS để styling bổ sung
- Inline styles chỉ cho cases đơn giản

## 🔐 Security

- Token được lưu trong localStorage
- Automatic logout khi token hết hạn (401)
- Role-based access control (SUPER_ADMIN)
- CSRF protection via Sanctum

## 🐛 Debugging

### Environment Variables

```bash
# Check environment
echo $VITE_API_BASE_URL

# For development with local backend:
VITE_API_BASE_URL=http://localhost:8000/api npm run dev
```

### Browser DevTools

- React DevTools extension
- Redux DevTools (for Zustand inspection if needed)
- Network tab để monitor API calls

## 📚 Thêm tài liệu

- [Vite Documentation](https://vite.dev)
- [React Documentation](https://react.dev)
- [TypeScript Documentation](https://www.typescriptlang.org)
- [Ant Design Components](https://ant.design/components/overview)
- [React Query Documentation](https://tanstack.com/query/latest)
- [Zustand Documentation](https://zustand-demo.vercel.app)

## 🤝 Contribution

1. Create feature branch (`git checkout -b feature/AmazingFeature`)
2. Commit changes (`git commit -m 'Add AmazingFeature'`)
3. Push to branch (`git push origin feature/AmazingFeature`)
4. Open a Pull Request

## 📄 License

Proprietary - KiotTay

## 📞 Support

Để báo cáo bugs hoặc yêu cầu features, vui lòng tạo issue trên repository.

---

**Last Updated**: April 2026
**Version**: 0.0.1
