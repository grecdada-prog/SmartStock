#!/usr/bin/env bash
composer install --no-dev --working-dir=/var/www/html
php artisan key:generate
php artisan config:cache
php artisan route:cache
php artisan migrate --force