# =========================================
# Stage 1: Build frontend assets with Node
# =========================================
FROM node:20-alpine AS frontend

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY vite.config.js ./
COPY resources/css resources/css
COPY resources/js resources/js

# Vite needs a minimal Laravel setup to resolve the manifest
COPY public public

RUN npm run build

# =========================================
# Stage 2: Production image (Nginx Unit)
# =========================================
FROM unit:1.34.1-php8.3

RUN apt update && apt install -y \
    curl unzip git \
    libicu-dev libzip-dev libpng-dev libjpeg-dev libfreetype6-dev libssl-dev libonig-dev libxml2-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    bcmath \
    gd \
    intl \
    mbstring \
    opcache \
    pcntl \
    sockets \
    zip \
    && apt clean && rm -rf /var/lib/apt/lists/*

# PHP production config
RUN echo "opcache.enable=1" > /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=128" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.interned_strings_buffer=16" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=10000" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.save_comments=1" >> /usr/local/etc/php/conf.d/opcache.ini

RUN echo "memory_limit=256M" > /usr/local/etc/php/conf.d/custom.ini \
    && echo "upload_max_filesize=20M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "post_max_size=20M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "max_execution_time=60" >> /usr/local/etc/php/conf.d/custom.ini

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/html

# Copy application code
COPY . .

# Copy built assets from frontend stage
COPY --from=frontend /app/public/build public/build

# Use local env during build, Coolify sets production at runtime
ENV APP_ENV=local

# Install PHP dependencies
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --ignore-platform-reqs

# Ensure storage & cache dirs are writable
RUN mkdir -p \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    && chown -R unit:unit \
        storage \
        bootstrap/cache \
    && chmod -R 775 \
        storage \
        bootstrap/cache

# Copy Unit config to entrypoint directory
COPY unit.json /docker-entrypoint.d/unit.json

EXPOSE 8000

CMD ["unitd", "--no-daemon"]
