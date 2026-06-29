FROM php:8.2-fpm-alpine

WORKDIR /var/www/html

# Install system dependencies & development libraries
RUN apk add --no-cache \
    bash \
    curl \
    libpng \
    libpng-dev \
    libxml2 \
    libxml2-dev \
    zip \
    unzip \
    git \
    oniguruma \
    oniguruma-dev \
    libzip \
    libzip-dev \
    nginx \
    supervisor

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Clean up dev packages to keep the image size small
RUN apk del libpng-dev libxml2-dev oniguruma-dev libzip-dev

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy application files
COPY . .

# Install PHP dependencies (exclude dev tools)
RUN composer install --no-interaction --optimize-autoloader --no-dev

# Copy configuration files
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh

# Clean Windows line endings (CRLF) and make it executable
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint.sh \
    && chmod +x /usr/local/bin/entrypoint.sh

# Setup logs and configurations
RUN mkdir -p /var/log/nginx /var/log/supervisor

# Fix folder permissions for Laravel storage and bootstrap cache
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Expose port 80 for Nginx
EXPOSE 80

# Start via entrypoint script
CMD ["/usr/local/bin/entrypoint.sh"]


