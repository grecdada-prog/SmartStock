#!/bin/bash

set -e

echo "🚀 Starting SmartStock build process..."

# Install dependencies
echo "📦 Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Clear all caches
echo "🧹 Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# Create cache directory if not exists
echo "📁 Ensuring cache directory exists..."
mkdir -p bootstrap/cache

# Run database migrations
echo "🗄️  Running database migrations..."
php artisan migrate --force --no-interaction

# Create storage link
echo "🔗 Creating storage symbolic link..."
php artisan storage:link || true

# Optimize Laravel
echo "⚡ Optimizing Laravel..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Seed Super Admin (if needed)
echo "👤 Seeding Super Admin..."
php artisan db:seed --class=SuperAdminSeeder --force || echo "Super Admin already exists"

echo "✅ Build completed successfully!"
