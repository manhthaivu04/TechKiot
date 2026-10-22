# TechKiot - Hệ thống quản lý nhà hàng & quán cafe

TechKiot là dự án hệ thống phần mềm hỗ trợ quản lý nhà hàng, quán ăn, quán cafe đa nền tảng, bao gồm hệ thống Backend API, Web quản lý (Frontend) và Ứng dụng di động (Mobile App) cho nhân viên order.

---

## 🏗 Cấu trúc hệ thống

Dự án được chia thành 3 phần chính:
- **ackend/**: Hệ thống API xây dựng bằng **PHP / Laravel**, xử lý xác thực, quản lý nhà hàng, thực đơn, bàn ăn, kho hàng, nhân viên và doanh thu.
- **rontend/**: Giao diện web quản trị dành cho quản lý và thu ngân POS, phát triển bằng **React, TypeScript, Ant Design, TailwindCSS**.
- **pp/**: Ứng dụng di động dành cho nhân viên phục vụ order trực tiếp tại bàn, phát triển bằng **Flutter**.

---

## 🚀 Hướng dẫn cài đặt và khởi chạy

### 1. Backend (Laravel)
`ash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
`

### 2. Frontend (React)
`ash
cd frontend
npm install
npm run dev
`

### 3. Mobile App (Flutter)
`ash
cd app
flutter pub get
flutter run
`

---

## 👨‍💻 Tác giả
- Sinh viên thực hiện: **manhthaivu04**
