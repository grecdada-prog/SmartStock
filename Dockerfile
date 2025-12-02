# ------------------------------------------------------
# 1) Composer Build
# ------------------------------------------------------
FROM composer:2 AS build-php

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader

COPY . .
RUN composer dump-autoload --optimize


# ------------------------------------------------------
# 2) Vite Build
# ------------------------------------------------------
FROM node:18 AS build-node

WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --silent

COPY . .
RUN npm run build


# ------------------------------------------------------
# 3) Final Image (Debian + PHP-FPM + NGINX + Supervisor)
# ------------------------------------------------------
FROM php:8.2-fpm-bullseye

RUN apt-get update && apt-get install -y \
    nginx \
    supervisor \
    libicu-dev \
    libpq-dev \
    unzip \
    && docker-php-ext-install intl pdo pdo_pgsql bcmath

WORKDIR /var/www/html

# Copy Laravel
COPY --from=build-php /app /var/www/html

# Copy Vite Production Build
COPY --from=build-node /app/public/build /var/www/html/public/build

# Copy supervisor config
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Copy nginx config
COPY nginx.conf /etc/nginx/nginx.conf

# Permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80

CMD ["/usr/bin/supervisord"]
