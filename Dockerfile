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
    libonig-dev \
    && docker-php-ext-install pdo pdo_pgsql mbstring zip \
    && rm -rf /var/lib/apt/lists/*

# Composer (autoriser l'usage en root)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /var/www/html

# 1. Installer les dépendances à partir de composer.json/lock
COPY composer.json composer.lock* ./
RUN composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-progress

# 2. Copier le reste du projet
COPY . .

# 3. Copier les assets buildés (si tu as une étape Node avant)
# COPY --from=node_builder /app/public/build ./public/build

# 4. Préparer les répertoires Laravel
RUN mkdir -p storage/framework/{cache,sessions,views} \
    && chown -R www-data:www-data storage bootstrap/cache

# 5. Caches Laravel (tolérer les erreurs si APP_KEY/DB non définis au build)
RUN php artisan config:cache || true \
    && php artisan route:cache || true \
    && php artisan view:cache || true

# Commande de démarrage : migrations + serveur Laravel
CMD php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
