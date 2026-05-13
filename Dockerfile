FROM composer:2 AS vendor

WORKDIR /build
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --optimize --classmap-authoritative

# -----------------------------------------------------------
FROM php:8.4-fpm-bookworm AS base

RUN apt-get update && apt-get install -y --no-install-recommends \
        libpng-dev libjpeg-dev libfreetype6-dev libzip-dev libicu-dev \
        libonig-dev unzip curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql opcache pcntl bcmath intl zip gd mbstring \
    && pecl install redis igbinary \
    && docker-php-ext-enable redis igbinary \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

RUN useradd -m -u 1000 app
WORKDIR /var/www

# -----------------------------------------------------------
FROM base AS production

COPY --from=vendor /build/vendor /var/www/vendor
COPY . /var/www

RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan event:cache \
    && php artisan view:cache

USER app
EXPOSE 9000
CMD ["php-fpm"]

# -----------------------------------------------------------
FROM base AS development

RUN pecl install pcov xdebug \
    && docker-php-ext-enable pcov xdebug

COPY docker/php/php-dev.ini /usr/local/etc/php/conf.d/zz-dev.ini

USER app
EXPOSE 9000
CMD ["php-fpm"]
