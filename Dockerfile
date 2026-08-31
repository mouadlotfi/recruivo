ARG PHP_VERSION=8.4
ARG NODE_VERSION=20
ARG FRANKENPHP_VERSION=1.4

FROM node:${NODE_VERSION}-bookworm-slim AS node-builder

WORKDIR /var/www/html

COPY package.json package-lock.json vite.config.ts postcss.config.js tailwind.config.ts tsconfig.json ./
RUN npm ci --prefer-offline --no-audit

COPY resources ./resources
RUN npm run build

FROM composer:2 AS composer-prod

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

FROM dunglas/frankenphp:${FRANKENPHP_VERSION}-php${PHP_VERSION}-bookworm AS php-runtime-base

RUN install-php-extensions \
    bcmath \
    exif \
    gd \
    mbstring \
    opcache \
    pcntl \
    pdo_mysql \
    zip \
    redis

WORKDIR /var/www/html

FROM php-runtime-base AS production

ENV APP_ENV=production \
    APP_DEBUG=0

COPY --from=composer-prod /var/www/html /var/www/html
COPY --from=node-builder /var/www/html/public/build /var/www/html/public/build
COPY Caddyfile /etc/caddy/Caddyfile
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
    CMD curl -f http://127.0.0.1/api/health >/dev/null 2>&1 || exit 1

EXPOSE 80 443 443/udp

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]

FROM production AS development

ENV APP_ENV=local \
    APP_DEBUG=1

COPY --from=composer-dev /var/www/html/vendor /var/www/html/vendor
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY docker/php/php.dev.ini /usr/local/etc/php/conf.d/zz-dev.ini

RUN rm -f bootstrap/cache/*.php
