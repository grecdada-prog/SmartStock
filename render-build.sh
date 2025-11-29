#!/usr/bin/env bash
# Script de build pour Render

set -o errexit

echo "🚀 Démarrage du build SmartStock..."

# 1. Installer les dépendances Composer
echo "📦 Installation des dépendances PHP..."
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# 2. Générer la clé d'application si elle n'existe pas
echo "🔑 Génération de la clé d'application..."
if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force
fi

# 3. Optimisations Laravel pour la production
echo "⚡ Optimisation de Laravel..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 4. Exécuter les migrations de base de données
echo "🗄️ Exécution des migrations..."
php artisan migrate --force --no-interaction

# 5. Créer les tables de cache et session
echo "💾 Création des tables de cache..."
php artisan cache:table --quiet || true
php artisan session:table --quiet || true
php artisan queue:table --quiet || true

# 6. Créer le lien symbolique pour le stockage
echo "🔗 Création du lien de stockage..."
php artisan storage:link || true

# 7. Seed la base de données (uniquement au premier déploiement)
# Décommenter si vous voulez seeder automatiquement
# echo "🌱 Seed de la base de données..."
# php artisan db:seed --force --class=RolesAndPermissionsSeeder

# 8. Nettoyer les caches
echo "🧹 Nettoyage des caches..."
php artisan optimize:clear

echo "✅ Build terminé avec succès!"
