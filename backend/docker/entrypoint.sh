#!/bin/sh

# Tự động chạy database migration khi container khởi động
echo "==> Running database migrations..."
php artisan migrate --force

# Tự động chạy database seeders
echo "==> Running database seeders..."
php artisan db:seed --force

# Khởi động supervisor để quản lý Nginx & PHP-FPM
echo "==> Starting Supervisor..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
