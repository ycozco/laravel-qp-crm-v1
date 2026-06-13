FROM php:8.3-cli-bookworm AS base

WORKDIR /var/www/html

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libicu-dev \
        libzip-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" bcmath gd intl pcntl pdo_mysql zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER=1

COPY composer.json ./

RUN composer install \
    --no-interaction \
    --no-security-blocking \
    --no-dev \
    --prefer-dist \
    --no-progress

COPY . .

RUN composer dump-autoload --optimize

CMD ["php", "-v"]
