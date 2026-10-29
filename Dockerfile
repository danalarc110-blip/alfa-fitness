# syntax=docker/dockerfile:1
FROM php:8.3-apache-bookworm AS php-runtime

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libfreetype6-dev libjpeg62-turbo-dev libpng-dev libwebp-dev \
        libpq-dev libzip-dev unzip util-linux \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" gd pdo_pgsql zip \
    && php -r '$required = ["curl", "ctype", "dom", "fileinfo", "filter", "gd", "hash", "iconv", "json", "libxml", "mbstring", "openssl", "PDO", "pdo_pgsql", "pdo_sqlite", "session", "SimpleXML", "tokenizer", "xml", "xmlreader", "xmlwriter", "zip", "zlib", "Zend OPcache"]; foreach ($required as $extension) { if (!extension_loaded($extension)) { throw new RuntimeException("Missing PHP extension: ".$extension); } } if (!function_exists("imagewebp")) { throw new RuntimeException("GD WebP support is required."); }' \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /var/www/html

FROM php-runtime AS dependencies
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --no-scripts --no-autoloader

FROM node:22-bookworm-slim AS frontend
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY vite.config.js ./
COPY resources ./resources
COPY public ./public
COPY --from=dependencies /var/www/html/vendor/laravel/framework/src/Illuminate/Pagination/resources/views ./vendor/laravel/framework/src/Illuminate/Pagination/resources/views
RUN npm run build

FROM php-runtime AS production
ENV APP_ENV=production \
    APP_DEBUG=false \
    PORT=10000 \
    ALPHA_DATA_DIR=/tmp/alpha-data \
    DB_CONNECTION=pgsql \
    DB_DATABASE=alpha_fitness \
    DB_URL="" \
    LOG_CHANNEL=stderr \
    LOG_LEVEL=warning \
    QUEUE_CONNECTION=sync

COPY . ./
COPY --from=dependencies /var/www/html/vendor ./vendor
COPY --from=frontend /app/public/build ./public/build
COPY docker/php-production.ini /usr/local/etc/php/conf.d/zz-alpha-production.ini
COPY docker/apache-security.conf /etc/apache2/conf-available/alpha-security.conf
COPY docker/entrypoint.sh /usr/local/bin/alpha-entrypoint

RUN mkdir -p storage/app/private storage/framework/cache/data storage/framework/sessions \
        storage/framework/views storage/logs bootstrap/cache \
    && composer dump-autoload --no-dev --optimize --classmap-authoritative --no-scripts \
    && php artisan package:discover --ansi \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod +x /usr/local/bin/alpha-entrypoint \
    && a2enconf alpha-security

EXPOSE 10000
ENTRYPOINT ["/usr/local/bin/alpha-entrypoint"]
CMD ["apache2-foreground"]
