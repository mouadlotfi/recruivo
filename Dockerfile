# =============================================================================
# Stage 1: Build frontend assets
# =============================================================================
FROM node:20-alpine AS node-builder

WORKDIR /app

# Copy package files first for better caching
COPY package.json package-lock.json ./

# Install dependencies
RUN npm ci --prefer-offline --no-audit

# Copy frontend source files
COPY resources/ ./resources/
COPY vite.config.js postcss.config.js tailwind.config.js ./

# Build production assets
RUN npm run build

# =============================================================================
# Stage 2: Install PHP dependencies
# =============================================================================
FROM php:8.2-cli AS composer-builder

# Copy composer binary from official image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install required PHP extensions for composer
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    libzip-dev \
    && docker-php-ext-install zip \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

# Copy composer files first for better caching
COPY composer.json composer.lock ./

# Install dependencies without dev packages for production
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --no-interaction

# Copy application code
COPY . .

# Generate optimized autoloader
RUN composer dump-autoload --optimize --no-dev

# =============================================================================
# Stage 3: Final production image
# =============================================================================
FROM php:8.2-apache AS final

# Build arguments for flexibility
ARG APP_ENV=production
ARG APP_DEBUG=false

# Environment variables
ENV APP_ENV=${APP_ENV} \
    APP_DEBUG=${APP_DEBUG} \
    APACHE_DOCUMENT_ROOT=/var/www/html/public

# Install system dependencies (minimal set for production)
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    jpegoptim optipng pngquant gifsicle \
    curl \
    netcat-openbsd \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        zip \
        gd \
        opcache \
    && pecl install redis \
    && docker-php-ext-enable redis

# Configure OPcache for production
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=128" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.interned_strings_buffer=8" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=10000" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.revalidate_freq=0" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/opcache.ini

# Configure PHP for production
RUN echo "expose_php=Off" >> /usr/local/etc/php/conf.d/security.ini \
    && echo "display_errors=Off" >> /usr/local/etc/php/conf.d/security.ini \
    && echo "log_errors=On" >> /usr/local/etc/php/conf.d/security.ini \
    && echo "error_log=/var/log/php_errors.log" >> /usr/local/etc/php/conf.d/security.ini \
    && echo "upload_max_filesize=10M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size=12M" >> /usr/local/etc/php/conf.d/uploads.ini

# Enable Apache modules
RUN a2enmod rewrite headers expires

# Configure Apache document root
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Copy Apache configuration
COPY docker/apache-config.conf /etc/apache2/sites-available/000-default.conf

# Set working directory
WORKDIR /var/www/html

# Copy application from composer builder
COPY --from=composer-builder /app .

# Copy built assets from node builder
COPY --from=node-builder /app/public/build ./public/build

# Create Laravel directories with correct permissions
RUN mkdir -p \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/framework/testing \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Copy and set up entrypoint script
COPY docker/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=60s --retries=3 \
    CMD curl -f http://localhost/api/health || exit 1

# Expose port
EXPOSE 80

# Run as non-root user for security (optional, uncomment if needed)
# USER www-data

# Entrypoint
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
