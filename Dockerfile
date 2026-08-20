ARG PHP_VERSION=8.4
ARG NODE_VERSION=20

FROM node:${NODE_VERSION}-bookworm-slim AS node-builder

WORKDIR /var/www/html

COPY package.json package-lock.json vite.config.ts postcss.config.js tailwind.config.ts tsconfig.json ./
RUN npm ci --prefer-offline --no-audit

COPY resources ./resources
RUN npm run build

FROM php:${PHP_VERSION}-cli AS composer-prod

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libzip-dev \
    && docker-php-ext-install zip \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --no-interaction \
    --no-progress

COPY . .
RUN composer dump-autoload --classmap-authoritative --no-dev --no-scripts

FROM composer-prod AS composer-dev

RUN composer install \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --no-scripts \
    && composer dump-autoload --classmap-authoritative --no-scripts

FROM php:${PHP_VERSION}-fpm-bookworm AS php-runtime-base

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        curl \
        libcurl4-openssl-dev \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libonig-dev \
        libpng-dev \
        libxml2-dev \
        libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        curl \
        exif \
        gd \
        mbstring \
        opcache \
        pcntl \
        pdo_mysql \
        zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

FROM php-runtime-base AS production

ENV APP_ENV=production \
    APP_DEBUG=0

COPY --from=composer-prod /var/www/html /var/www/html
COPY --from=node-builder /var/www/html/public/build /var/www/html/public/build
COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/zz-opcache.ini

RUN mkdir -p \
        storage/app/public \
        storage/app/private \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/framework/testing \
        storage/logs \
        bootstrap/cache \
    && rm -rf public/storage \
    && ln -s /var/www/html/storage/app/public public/storage \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwX storage bootstrap/cache

COPY docker/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

HEALTHCHECK --interval=10s --timeout=5s --start-period=15s --retries=3 \
    CMD php-fpm -t >/dev/null 2>&1 || exit 1

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["php-fpm", "-F"]

FROM production AS development

ENV APP_ENV=local \
    APP_DEBUG=1

COPY --from=composer-dev /var/www/html/vendor /var/www/html/vendor
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY docker/php/php.dev.ini /usr/local/etc/php/conf.d/zz-dev.ini

RUN rm -f bootstrap/cache/*.php

FROM nginx:1.27-alpine AS nginx

COPY --from=production /var/www/html/public /var/www/html/public
RUN rm -rf /var/www/html/public/storage \
    && mkdir -p /var/www/html/storage/app/public \
    && ln -s /var/www/html/storage/app/public /var/www/html/public/storage

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf

HEALTHCHECK --interval=30s --timeout=10s --start-period=20s --retries=3 \
    CMD wget -q -O - http://127.0.0.1/api/health >/dev/null || exit 1