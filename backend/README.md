<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## KiotTay Backend — API Reference

### Base URL

- **Prefix**: `/api`

### Authentication

- **Auth**: Laravel Sanctum (token)
- **Header**: `Authorization: Bearer <token>`

### Authorization / Role

- Nhóm API `/api/admin/*` yêu cầu:
  - `auth:sanctum`
  - `role:SUPER_ADMIN`

### Endpoints

#### Auth

- **POST** `/api/auth/login`
  - **Body (required)**:
    - `email` (string, email)
    - `password` (string)

- **POST** `/api/auth/logout`
  - **Auth**: required
  - **Body**: none

- **GET** `/api/auth/me`
  - **Auth**: required
  - **Query/Body**: none

#### Public — QR Menu

> **Auth/Role**: Public (Không cần đăng nhập)
>
> Prefix: `/api/public/menu`

- **GET** `/api/public/menu`
  - **Mô tả**: Lấy danh sách thực đơn của nhà hàng cho khách quét mã QR, tự động gom nhóm theo danh mục.
  - **Query (required)**:
    - `public_token` (string, uuid) — UUID của nhà hàng (nếu quét mã QR tĩnh) hoặc UUID của bàn (nếu quét mã QR tại bàn).
    - `type` (string) — Loại mã QR: `qr_static` (QR tĩnh, mặc định hỗ trợ).
  - **Ví dụ Request**:
    ```
    GET /api/public/menu?public_token=123e4567-e89b-12d3-a456-426614174000&type=qr_static
    ```
  - **Response 200**:
    ```json
    {
      "success": true,
      "data": [
        {
          "group_id": "018e1234-...",
          "group_name": "Món chính",
          "display_order": 1,
          "items": [
            {
              "id": "018e5678-...",
              "name": "Phở bò",
              "item_type": "MENU_ITEM",
              "unit": "tô",
              "image_url": "https://res.cloudinary.com/.../pho-bo.jpg",
              "description": "Phở bò truyền thống",
              "sale_price": "55000.00",
              "availability_status": "IN_STOCK"
            }
          ]
        },
        {
          "group_id": null,
          "group_name": "Khác",
          "display_order": 2147483647,
          "items": [ ... ]
        }
      ]
    }
    ```
  - **Response 404**: Trả về khi `public_token` (nhà hàng) không tồn tại.
  - **Response 422**: Trả về khi thiếu query param hoặc `type` không hợp lệ.

#### Admin — Restaurants

- **GET** `/api/admin/restaurants`
  - **Auth/Role**: SUPER_ADMIN
  - **Query (optional)**:
    - `status` (string)
    - `search` (string)
    - `per_page` (integer, default 15)

- **POST** `/api/admin/restaurants`
  - **Auth/Role**: SUPER_ADMIN
  - **Body**:
    - **required**: `name` (string, max 150)
    - **optional**: `address` (string, max 255)
    - **optional**: `phone` (string, max 20)

- **GET** `/api/admin/restaurants/{id}`
  - **Auth/Role**: SUPER_ADMIN
  - **Body**: none

- **PUT** `/api/admin/restaurants/{id}`
  - **Auth/Role**: SUPER_ADMIN
  - **Body (all optional)**:
    - `name` (string, max 150)
    - `address` (string, max 255, nullable)
    - `phone` (string, max 20, nullable)

- **PATCH** `/api/admin/restaurants/{id}/lock`
  - **Auth/Role**: SUPER_ADMIN
  - **Body**: none

- **PATCH** `/api/admin/restaurants/{id}/unlock`
  - **Auth/Role**: SUPER_ADMIN
  - **Body**: none

#### Admin — Subscriptions

- **GET** `/api/admin/restaurants/{restaurantId}/subscriptions`
  - **Auth/Role**: SUPER_ADMIN
  - **Body**: none

- **GET** `/api/admin/restaurants/{restaurantId}/subscriptions/active`
  - **Auth/Role**: SUPER_ADMIN
  - **Body**: none

- **POST** `/api/admin/restaurants/{restaurantId}/subscriptions`
  - **Auth/Role**: SUPER_ADMIN
  - **Body (required)**:
    - `package_id` (uuid, exists: `packages.id`)

- **PATCH** `/api/admin/subscriptions/{id}/cancel`
  - **Auth/Role**: SUPER_ADMIN
  - **Body**: none

#### Admin — Features

- **GET** `/api/admin/features`
  - **Auth/Role**: SUPER_ADMIN
  - **Query (optional)**:
    - `is_active` (boolean)
    - `search` (string)
    - `per_page` (integer, default 50)

- **POST** `/api/admin/features`
  - **Auth/Role**: SUPER_ADMIN
  - **Body**:
    - **required**: `code` (string, max 100, regex `^[A-Z0-9_]+$`)
    - **required**: `name` (string, max 150)
    - **optional**: `description` (string)
    - **optional**: `is_active` (boolean)

- **GET** `/api/admin/features/{id}`
  - **Auth/Role**: SUPER_ADMIN
  - **Body**: none

- **PUT** `/api/admin/features/{id}`
  - **Auth/Role**: SUPER_ADMIN
  - **Body (all optional)**:
    - `code` (string, max 100, regex `^[A-Z0-9_]+$`)
    - `name` (string, max 150)
    - `description` (string, nullable)
    - `is_active` (boolean)

- **PATCH** `/api/admin/features/{id}/toggle`
  - **Auth/Role**: SUPER_ADMIN
  - **Body**: none

#### Admin — Packages

- **GET** `/api/admin/packages`
  - **Auth/Role**: SUPER_ADMIN
  - **Query (optional)**:
    - `is_active` (boolean)
    - `search` (string)
    - `per_page` (integer, default 15)

- **POST** `/api/admin/packages`
  - **Auth/Role**: SUPER_ADMIN
  - **Body**:
    - **required**: `code` (string, max 50, regex `^[A-Z0-9_]+$`)
    - **required**: `name` (string, max 100)
    - **required**: `price` (numeric, min 0)
    - **required**: `duration_days` (integer, min 1)
    - **optional**: `description` (string)
    - **optional**: `is_active` (boolean)
    - **optional**: `feature_ids` (array of uuid, exists: `features.id`)

- **GET** `/api/admin/packages/{id}`
  - **Auth/Role**: SUPER_ADMIN
  - **Body**: none

- **PUT** `/api/admin/packages/{id}`
  - **Auth/Role**: SUPER_ADMIN
  - **Body (all optional)**:
    - `code` (string, max 50, regex `^[A-Z0-9_]+$`)
    - `name` (string, max 100)
    - `price` (numeric, min 0)
    - `duration_days` (integer, min 1)
    - `description` (string, nullable)
    - `is_active` (boolean)
    - `feature_ids` (array of uuid, exists: `features.id`)

- **PATCH** `/api/admin/packages/{id}/toggle`
  - **Auth/Role**: SUPER_ADMIN
  - **Body**: none

- **PUT** `/api/admin/packages/{id}/features`
  - **Auth/Role**: SUPER_ADMIN
  - **Body (required)**:
    - `feature_ids` (array)
    - `feature_ids.*` (uuid, exists: `features.id`)

#### Tenant — Item Groups

> **Auth/Role**: `auth:sanctum` + `role:OWNER,MANAGER` + `feature:MENU_MANAGEMENT`
>
> Prefix: `/api/tenant/item-groups`

- **GET** `/api/tenant/item-groups`
  - **Mô tả**: Lấy danh sách tất cả nhóm món của restaurant hiện tại.
  - **Query**: none
  - **Response**: `{ "data": [ ... ] }`

- **POST** `/api/tenant/item-groups`
  - **Mô tả**: Tạo nhóm món mới.
  - **Body**:
    - **required**: `name` (string, max 255)
    - **optional**: `display_order` (integer, min 0)
    - **optional**: `is_active` (boolean)
  - **Response 201**: `{ "data": { ... }, "message": "Group created successfully" }`

- **GET** `/api/tenant/item-groups/{id}`
  - **Mô tả**: Lấy chi tiết một nhóm món theo ID.
  - **Response**: `{ "data": { ... } }`

- **PUT** `/api/tenant/item-groups/{id}`
  - **Mô tả**: Cập nhật thông tin nhóm món.
  - **Body (all optional / sometimes)**:
    - `name` (string, max 255)
    - `display_order` (integer, min 0, nullable)
    - `is_active` (boolean)
  - **Response**: `{ "data": { ... }, "message": "Group updated successfully" }`

- **DELETE** `/api/tenant/item-groups/{id}`
  - **Mô tả**: Xoá nhóm món.
  - **Response 204**: `{ "message": "Group deleted successfully" }`

---

#### Tenant — Items

> **Auth/Role**: `auth:sanctum` + `role:OWNER,MANAGER` + `feature:MENU_MANAGEMENT`
>
> Prefix: `/api/tenant/items`
>
> ⚠️ Các request có upload ảnh phải gửi dạng `multipart/form-data`.

**Enum values:**

| Field | Giá trị hợp lệ |
|---|---|
| `item_type` | `MENU_ITEM`, `INGREDIENT` |
| `availability_status` | `IN_STOCK`, `OUT_OF_STOCK`, `SUSPENDED` |

- **GET** `/api/tenant/items`
  - **Mô tả**: Lấy danh sách món (có phân trang).
  - **Query (optional)**:
    - `item_group_id` (uuid) — lọc theo nhóm
    - `item_type` (string) — `MENU_ITEM` | `INGREDIENT`
    - `per_page` (integer, default 15)
  - **Response**: Paginated JSON

- **POST** `/api/tenant/items`
  - **Mô tả**: Tạo món mới. Gửi dạng `multipart/form-data` nếu có ảnh.
  - **Body**:
    - **required**: `item_group_id` (uuid, phải thuộc restaurant hiện tại)
    - **required**: `name` (string, max 255)
    - **required**: `item_type` (`MENU_ITEM` | `INGREDIENT`)
    - **required**: `unit` (string, max 50) — ví dụ: `"phần"`, `"ly"`, `"cái"`
    - **required**: `sale_price` (numeric, min 0)
    - **required**: `availability_status` (`IN_STOCK` | `OUT_OF_STOCK` | `SUSPENDED`)
    - **optional**: `image` (file: jpeg/png/jpg/webp, max 2 MB)
    - **optional**: `description` (string)
    - **optional**: `cost_price` (numeric, min 0)
    - **optional**: `is_active` (boolean)
  - **Response 201**: `{ "data": { ... }, "message": "Item created successfully" }`

- **GET** `/api/tenant/items/{id}`
  - **Mô tả**: Lấy chi tiết một món (kèm quan hệ `itemGroup`).
  - **Response**: `{ "data": { ..., "item_group": { ... } } }`

- **PUT** `/api/tenant/items/{id}`
  - **Mô tả**: Cập nhật món. Gửi dạng `multipart/form-data` nếu thay ảnh.
  - **Body (all optional / sometimes)**:
    - `item_group_id` (uuid, phải thuộc restaurant hiện tại)
    - `name` (string, max 255)
    - `item_type` (`MENU_ITEM` | `INGREDIENT`)
    - `unit` (string, max 50)
    - `sale_price` (numeric, min 0)
    - `availability_status` (`IN_STOCK` | `OUT_OF_STOCK` | `SUSPENDED`)
    - `image` (file: jpeg/png/jpg/webp, max 2 MB, nullable)
    - `description` (string, nullable)
    - `cost_price` (numeric, min 0, nullable)
    - `is_active` (boolean)
  - **Response**: `{ "data": { ... }, "message": "Item updated successfully" }`

- **DELETE** `/api/tenant/items/{id}`
  - **Mô tả**: Xoá món.
  - **Response 204**: `{ "message": "Item deleted successfully" }`

#### Public — QR Order

> **Auth/Role**: Public (Không cần đăng nhập)
>
> Prefix: `/api/public/orders`

- **POST** `/api/public/orders`
  - **Mô tả**: Khách hàng quét mã QR để đặt món.
  - **Body**:
    - **required**: `public_token` (string, uuid)
    - **required**: `source_channel` (`qr_static` | `qr_table`)
    - **required**: `items` (array)
    - `items.*.item_id` (uuid)
    - `items.*.quantity` (integer)
    - `items.*.note` (string, optional)
    - **optional**: `customer_name` (string)
    - **optional**: `customer_phone` (string)
    - **optional**: `note` (string)
  - **Response 201**: `{ "code": "CREATED", "message": "Đặt món thành công! Đơn hàng của bạn đang được chuẩn bị.", "data": { ... } }`

- **GET** `/api/public/orders/{id}`
  - **Mô tả**: Khách hàng xem lại trạng thái đơn hàng và các món đã đặt thông qua ID đơn hàng (UUID).
  - **Response**: `{ "code": "SUCCESS", "message": "Thao tác thành công.", "data": { ... } }`

---

#### Tenant — Orders

> **Auth/Role**: `auth:sanctum` + `role:OWNER,MANAGER,WAITER,KITCHEN,CASHIER` + `feature:POS_QUICK_ORDER`
>
> Prefix: `/api/tenant/orders`

- **GET** `/api/tenant/orders`
  - **Mô tả**: Lấy danh sách đơn hàng.
  - **Query (optional)**: Phân trang `per_page`
  - **Response**: Paginated JSON

- **POST** `/api/tenant/orders`
  - **Mô tả**: Nhân viên tạo đơn hàng mới.
  - **Body**:
    - **required**: `source_channel` (`cashier` | `pos`)
    - **required**: `items` (array)
    - **optional**: `table_id`, `customer_name`, `customer_phone`, `note`, `guest_count`, `discount_amount`
  - **Response 201**: `{ "code": "CREATED", "message": "Đặt đơn hàng thành công.", "data": { ... } }`

- **GET** `/api/tenant/orders/{id}`
  - **Mô tả**: Lấy chi tiết đơn hàng (bao gồm items, payments).
  - **Response**: `{ "code": "SUCCESS", "message": "Thao tác thành công.", "data": { ... } }`

- **PATCH** `/api/tenant/orders/{id}/status`
  - **Mô tả**: Cập nhật trạng thái đơn hàng (áp dụng State Pattern).
  - **Body**:
    - **required**: `status` (`cooking`, `served`, `paid`, `cancelled`)
  - **Response**: `{ "code": "SUCCESS", "message": "Đơn hàng đã chuyển sang trạng thái...", "data": { ... } }`

- **POST** `/api/tenant/orders/{id}/items`
  - **Mô tả**: Thêm món vào đơn hàng hiện tại (chỉ được khi trạng thái chưa đóng).
  - **Body**:
    - **required**: `items` (array)
    - `items.*.item_id` (uuid)
    - `items.*.quantity` (integer)
    - `items.*.note` (string, optional)
  - **Response**: `{ "code": "SUCCESS", "message": "Đã thêm món vào đơn hàng thành công.", "data": { ... } }`

- **POST** `/api/tenant/orders/{id}/payments`
  - **Mô tả**: Ghi nhận thanh toán cho đơn hàng.
  - **Body**:
    - **required**: `amount` (numeric)
    - **required**: `payment_method` (`cash`, `transfer`, `card`)
    - **optional**: `reference_no` (string)
  - **Response 201**: `{ "code": "CREATED", "message": "Ghi nhận thanh toán thành công.", "data": { ... } }`

---

#### Tenant — Staff

> **Auth/Role**: `auth:sanctum` + `role:OWNER,MANAGER` + `feature:STAFF_MANAGEMENT`
>
> Prefix: `/api/tenant/staff`

- **GET** `/api/tenant/staff`
  - **Mô tả**: Lấy danh sách nhân viên của nhà hàng.
  - **Query (optional)**: Phân trang `per_page`, lọc theo `role`
  - **Response**: Paginated JSON

- **POST** `/api/tenant/staff`
  - **Mô tả**: Tạo tài khoản nhân viên mới.
  - **Body**:
    - **required**: `name` (string)
    - **required**: `email` (string, unique)
    - **required**: `password` (string)
    - **required**: `role` (`MANAGER`, `WAITER`, `KITCHEN`, `CASHIER`)
  - **Response 201**: `{ "code": "CREATED", "message": "Tạo nhân viên thành công.", "data": { ... } }`

- **GET** `/api/tenant/staff/{id}`
  - **Mô tả**: Lấy chi tiết thông tin nhân viên.
  - **Response**: `{ "code": "SUCCESS", "message": "Thao tác thành công.", "data": { ... } }`

- **PUT** `/api/tenant/staff/{id}`
  - **Mô tả**: Cập nhật thông tin nhân viên.
  - **Body**:
    - **optional**: `name`, `email`, `role`, `password`, `is_active`
  - **Response**: `{ "code": "SUCCESS", "message": "Cập nhật nhân viên thành công.", "data": { ... } }`

- **DELETE** `/api/tenant/staff/{id}`
  - **Mô tả**: Xóa (hoặc vô hiệu hóa) nhân viên.
  - **Response 204**: No Content

