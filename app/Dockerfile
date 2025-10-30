# syntax=docker/dockerfile:1.7

FROM php:8.2-fpm-bullseye AS base

ENV DEBIAN_FRONTEND=noninteractive \
    COMPOSER_ALLOW_SUPERUSER=1

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        curl \
        libpq-dev \
        libzip-dev \
        libonig-dev \
        libxml2-dev \
        libssl-dev \
    && docker-php-ext-install pdo_mysql zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-progress --prefer-dist

COPY . .

RUN composer dump-autoload --optimize \
    && php artisan package:discover --ansi

RUN chown -R www-data:www-data storage bootstrap/cache

CMD ["php-fpm"]

FROM base AS dev

RUN composer install --no-progress --prefer-dist

CMD ["php-fpm"]
