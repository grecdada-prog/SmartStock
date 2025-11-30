# Image de base PHP 8.2 avec CLI (pas FPM pour Render)
FROM php:8.2-cli

# 1) Installer Node.js 20 LTS et les dépendances système
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    nodejs \
    libpq-dev \
    libzip-dev \
    libonig-dev \
    && docker-php-ext-install pdo pdo_pgsql mbstring zip \
    && rm -rf /var/lib/apt/lists/*

# 2) Installer Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /var/www/html

# 3) Copier les fichiers de dépendances
COPY composer.json composer.lock* ./
COPY package.json package-lock.json* ./

# 4) Installer les dépendances PHP (sans scripts artisan au build)
RUN composer install \
    --no-dev \
    --prefer-dist \
    --optimize-autoloader \
    --no-interaction \
    --no-progress \
    --no-scripts

# 5) Installer les dépendances Node.js et compiler les assets
# Note: On installe toutes les dépendances (y compris dev) car Vite/Tailwind sont nécessaires pour le build
RUN npm ci \
    && npm run build \
    && npm cache clean --force \
    && rm -rf node_modules

# 6) Copier le reste du projet
COPY . .

# 7) Préparer les dossiers Laravel avec les bonnes permissions
RUN mkdir -p storage/framework/{cache,sessions,views} \
    storage/logs \
    bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

# 8) Exposer le port (Render injectera la variable PORT)
EXPOSE 8000

# 9) Script de démarrage optimisé pour Render
# Les variables d'environnement (APP_KEY, DB_*, etc.) sont injectées par Render au runtime
# Créer un script de démarrage
RUN echo '#!/bin/sh\n\
set -e\n\
echo "🚀 Starting SmartStock application..."\n\
\n\
# Attendre que la base de données soit prête (optionnel, Render gère généralement cela)\n\
echo "📊 Running database migrations..."\n\
php artisan migrate --force || echo "⚠️  Migration failed, continuing..."\n\
\n\
# Créer le lien symbolique pour le stockage\n\
php artisan storage:link || echo "⚠️  Storage link already exists"\n\
\n\
# Optimiser Laravel pour la production\n\
echo "⚡ Optimizing Laravel..."\n\
php artisan config:cache || true\n\
php artisan route:cache || true\n\
php artisan view:cache || true\n\
\n\
# Démarrer le serveur (PORT est injecté par Render)\n\
PORT=${PORT:-8000}\n\
echo "🌐 Starting web server on port $PORT..."\n\
exec php artisan serve --host=0.0.0.0 --port=$PORT\n\
' > /usr/local/bin/start.sh && chmod +x /usr/local/bin/start.sh

CMD ["/usr/local/bin/start.sh"]
