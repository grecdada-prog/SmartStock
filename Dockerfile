# Utilise une image avec Nginx et PHP-FPM
FROM richarvey/nginx-php-fpm:3.1.6

# Copie les fichiers de votre projet
COPY . /var/www/html

# Définit le répertoire web public
ENV WEBROOT=/var/www/html/public
ENV APP_ENV=production
ENV PHP_ERRORS_STDERR=1
ENV RUN_SCRIPTS=1
ENV REAL_IP_HEADER=1

# Scripts à exécuter au démarrage
RUN echo '#!/bin/bash\n\
cd /var/www/html\n\
php artisan config:cache\n\
php artisan route:cache\n\
php artisan view:cache\n\
php artisan migrate --force\n\
php artisan storage:link || true' > /var/www/html/scripts/run.sh && chmod +x /var/www/html/scripts/run.sh

# Expose le port (Render utilise PORT env var)
EXPOSE 80

CMD ["/start.sh"]