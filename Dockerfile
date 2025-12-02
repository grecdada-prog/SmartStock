# --------------------
# 1) PHP dependencies build
# --------------------
FROM composer:2 AS build-php

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader

COPY . .
RUN composer dump-autoload --optimize

# --------------------
# 2) Node/Vite build
# --------------------
FROM node:18 AS build-node

WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --silent

COPY . .
RUN npm run build

# --------------------
# 3) Final container (PHP-FPM + Nginx + Supervisord)
# --------------------
FROM php:8.2-fpm

# Installer extensions PHP nécessaires et outils
RUN apt-get update && apt-get install -y \
    nginx \
    libpq-dev \
    zip unzip git curl supervisor \
    libonig-dev libzip-dev libicu-dev libxml2-dev \
    && docker-php-ext-install pdo pdo_pgsql bcmath mbstring intl opcache \
    && docker-php-ext-enable opcache

# Définir le working directory
WORKDIR /var/www/html

# Copier Laravel et les dépendances PHP
COPY --from=build-php /app /var/www/html

# Copier build Vite
COPY --from=build-node /app/public/build /var/www/html/public/build

# Copier config Nginx et Supervisord
COPY ./nginx.conf /etc/nginx/nginx.conf
COPY ./supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Exposer le port HTTP
EXPOSE 80

# Démarrage avec supervisord (PHP-FPM + Nginx)
CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
