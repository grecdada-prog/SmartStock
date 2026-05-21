#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."

rm -f bootstrap/cache/config.php \
    bootstrap/cache/events.php \
    bootstrap/cache/packages.php \
    bootstrap/cache/routes-v7.php \
    bootstrap/cache/services.php

php artisan down --retry=60 || true
trap 'php artisan up || true' EXIT

composer install --no-dev --prefer-dist --optimize-autoloader
npm ci
npm run build
php artisan optimize:clear
php artisan migrate --force
php artisan production:check
php artisan config:cache
php artisan route:cache
php artisan view:cache

php artisan up
trap - EXIT
