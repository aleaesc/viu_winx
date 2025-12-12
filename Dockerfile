# Production Dockerfile for Laravel on Render using Apache
FROM php:8.2-apache

# Install system deps and PHP extensions
RUN apt-get update \
    && apt-get install -y \
       libzip-dev zip unzip git \
       libpng-dev libonig-dev libxml2-dev libicu-dev \
    && docker-php-ext-install pdo_mysql zip mbstring exif pcntl bcmath intl gd \
    && a2enmod rewrite \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Set working dir
WORKDIR /var/www/html
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV APP_KEY=base64:dGVtcG9yYXJ5a2V5Zm9yYnVpbGR0aW1lb25seQ==

# Copy composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy the rest of the application source first
COPY . /var/www/html

# Install PHP dependencies (now that full source is present)
RUN composer install --no-interaction --no-dev --prefer-dist --optimize-autoloader --no-scripts && \
    composer dump-autoload --optimize

# Set Apache document root to public
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf \
    && sed -ri -e 's!Directory /var/www/!Directory ${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf

# Ensure storage and bootstrap/cache are writable
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Optimize caches (non-fatal during build)
RUN php artisan config:cache || true \
    && php artisan route:cache || true \
    && php artisan view:cache || true

# Expose port
EXPOSE 80

# On container start, run migrations (non-fatal) then start Apache
CMD php artisan migrate --force || echo "Migration failed, continuing..." && apache2-foreground
