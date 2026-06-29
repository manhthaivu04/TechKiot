# Stage 1: Build composer dependencies
FROM php:8.2-fpm-alpine as builder

WORKDIR /var/www/html

# Install system dependencies for composer and php extensions
RUN apk add --no-cache \
    bash \
    curl \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    git \
    oniguruma-dev \
    libzip-dev

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy application files
COPY . .

# Install dependencies (exclude dev tools)
RUN composer install --no-interaction --optimize-autoloader --no-dev

# Stage 2: Production image
FROM php:8.2-fpm-alpine

WORKDIR /var/www/html

# Install Nginx, supervisor, and runtime dependencies
RUN apk add --no-cache nginx supervisor curl libpng libzip mariadb-connector-c-dev libxml2 oniguruma

# Install PHP extensions needed at runtime
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Copy code from builder stage
COPY --from=builder /var/www/html /var/www/html

# Copy configuration files
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Setup logs and configurations
RUN mkdir -p /var/log/nginx /var/log/supervisor

# Fix folder permissions for Laravel storage and bootstrap cache
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Expose port 80 for Nginx
EXPOSE 80

# Start Supervisor (which starts both Nginx and PHP-FPM)
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
