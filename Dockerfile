# Utilise une image avec Nginx et PHP-FPM
FROM richarvey/nginx-php-fpm:3.1.6
# Copie les fichiers de votre projet
COPY . .
# Définit le répertoire web public
ENV WEBROOT /var/www/html/public
ENV APP_ENV production
CMD ["/start.sh"]