# ============================
# 1) Build des assets (Vite)
# ============================
FROM node:20 AS node_builder

WORKDIR /app

# Copie des fichiers nécessaires au build front
COPY package.json package-lock.json* yarn.lock* pnpm-lock.yaml* .npmrc* ./
RUN npm install || yarn install || pnpm install || echo "No lockfile matched, npm install done"

COPY vite.config.* postcss.config.* tailwind.config.* ./
COPY resources ./resources
COPY public ./public

RUN npm run build || yarn build || pnpm build

# ============================
# 2) Image PHP/Laravel finale
# ============================
FROM php:8.2-fpm

# Extensions nécessaires pour Laravel + PostgreSQL
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    libpq-dev \
    libzip-dev \
    && docker-php-ext-install pdo pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copier uniquement composer.* d'abord pour profiter du cache Docker
COPY composer.json composer.lock* ./

RUN composer install --no-dev --prefer-dist --optimize-autoloader

# Copier tout le projet
COPY . .

# Copier les assets buildés
COPY --from=node_builder /app/public/build ./public/build

# Donner les bons droits sur storage et cache
RUN mkdir -p storage/framework/{cache,sessions,views} \
    && chown -R www-data:www-data storage bootstrap/cache

# Caches Laravel (config, routes, vues)
RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache || echo "Artisan cache failed (probably no .env yet), continuing"

# Commande de démarrage :
# - migrations
# - serveur Laravel
CMD php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
