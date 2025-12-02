FROM php:8.2-fpm-alpine

# Installer dépendances système et PHP
RUN apk add --no-cache \
    bash \
    git \
    unzip \
    curl \
    nodejs \
    npm \
    libzip-dev \
    oniguruma-dev \
    postgresql-dev \
    bash \
    shadow \
    libpng-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    icu-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_pgsql zip bcmath mbstring gd intl

# Installer Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Créer le dossier de travail
WORKDIR /var/www/html

# Copier les fichiers
COPY . .

# Installer les dépendances Laravel et Node
RUN composer install --no-dev --optimize-autoloader
RUN npm install
RUN npm run build

# Donner les droits à l'utilisateur www-data
RUN chown -R www-data:www-data /var/www/html

EXPOSE 9000
CMD ["php-fpm"]
