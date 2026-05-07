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

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
