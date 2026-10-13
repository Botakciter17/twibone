FROM php:8.4-fpm-alpine

# Install build dependencies for GD & zip
RUN apk add --no-cache \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    libwebp-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) gd pcntl opcache

# Copy custom PHP-FPM and OPcache configurations
COPY docker/php/zz-docker.conf /usr/local/etc/php-fpm.d/zz-docker.conf
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html

# Ensure proper storage & cache directories and permissions
RUN mkdir -p storage/framework/sessions \
    storage/framework/views \
    storage/framework/cache \
    storage/app/public/twibbons \
    bootstrap/cache \
    && chmod -R 777 storage bootstrap/cache

# Re-link storage relative to public
RUN rm -rf public/storage && php artisan storage:link

EXPOSE 9000

CMD ["php-fpm"]
