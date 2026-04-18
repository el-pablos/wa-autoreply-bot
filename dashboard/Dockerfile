FROM php:8.3-fpm-alpine

# Install system dependencies dan PHP extensions dalam satu layer
RUN apk add --no-cache \
    nginx \
    supervisor \
    nodejs \
    npm \
    curl \
    libpng-dev \
    libzip-dev \
    libxml2-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    icu-dev \
    zip \
    unzip \
    oniguruma-dev \
    # Dependencies untuk pecl/redis
    autoconf \
    g++ \
    make \
    linux-headers \
    # Bersihkan setelah build
    && docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        mbstring \
        zip \
        gd \
        xml \
        intl \
        opcache \
        pcntl \
        bcmath \
    # Install php-redis via pecl
    && pecl install redis \
    && docker-php-ext-enable redis \
    # Bersihkan build artifacts
    && apk del autoconf g++ make linux-headers \
    && rm -rf /var/cache/apk/* /tmp/pear

# Konfigurasi OPcache untuk production performance
RUN { \
    echo 'opcache.memory_consumption=128'; \
    echo 'opcache.interned_strings_buffer=8'; \
    echo 'opcache.max_accelerated_files=4000'; \
    echo 'opcache.revalidate_freq=60'; \
    echo 'opcache.fast_shutdown=1'; \
    echo 'opcache.enable_cli=1'; \
} > /usr/local/etc/php/conf.d/opcache.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN mkdir -p bootstrap/cache storage/framework/cache storage/framework/sessions storage/framework/views storage/logs && \
    chmod -R 775 bootstrap storage

RUN composer install --optimize-autoloader --no-dev --no-interaction

RUN cp .env.example .env && \
    php artisan key:generate

RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

COPY docker/nginx.conf    /etc/nginx/nginx.conf
COPY docker/php-fpm.conf  /etc/php83/php-fpm.d/www.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=10s --start-period=60s --retries=3 \
  CMD curl -f http://localhost/login || exit 1

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
