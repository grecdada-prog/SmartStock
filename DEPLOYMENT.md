# 🚀 Guide de Déploiement SmartStock sur Render

Ce guide vous accompagne étape par étape pour déployer SmartStock sur Render avec un pipeline CI/CD automatisé.

## 📋 Table des Matières

1. [Prérequis](#prérequis)
2. [Préparation du Projet](#préparation-du-projet)
3. [Configuration Render](#configuration-render)
4. [Configuration CI/CD](#configuration-cicd)
5. [Premier Déploiement](#premier-déploiement)
6. [Vérifications Post-Déploiement](#vérifications-post-déploiement)
7. [Maintenance](#maintenance)
8. [Troubleshooting](#troubleshooting)

---

## 1. Prérequis

### Comptes Nécessaires

- ✅ **GitHub Account** (gratuit) - Pour héberger le code
- ✅ **Render Account** (gratuit) - [render.com](https://render.com)
- ✅ **SendGrid Account** (gratuit) - Pour les emails

### Outils Locaux

```bash
# Vérifier PHP
php -v  # Doit afficher 8.2 ou supérieur

# Vérifier Composer
composer -V

# Vérifier Git
git --version

# Vérifier Node.js (pour les assets)
node -v  # Recommandé: v20+
npm -v
```

---

## 2. Préparation du Projet

### Étape 2.1: Initialiser Git (si pas déjà fait)

```bash
cd c:\Users\hp\Desktop\FN\SmartStock

# Initialiser le dépôt Git
git init

# Ajouter tous les fichiers
git add .

# Premier commit
git commit -m "Initial commit: SmartStock ready for deployment"
```

### Étape 2.2: Créer le Dépôt GitHub

1. **Aller sur GitHub** : [github.com/new](https://github.com/new)
2. **Créer un nouveau repository** :
   - Nom: `SmartStock`
   - Visibilité: Private (recommandé)
   - Ne pas initialiser avec README (on a déjà le code)

3. **Lier le dépôt local à GitHub** :

```bash
# Remplacer "votre-username" par votre nom d'utilisateur GitHub
git remote add origin https://github.com/votre-username/SmartStock.git

# Pousser le code
git branch -M main
git push -u origin main
```

### Étape 2.3: Rendre le Script de Build Exécutable

```bash
# Sur Windows (PowerShell)
git update-index --chmod=+x render-build.sh

# Commit
git add render-build.sh
git commit -m "Make build script executable"
git push
```

### Étape 2.4: Optimiser .gitignore

Le fichier `.gitignore` doit contenir :

```gitignore
/node_modules
/public/build
/public/hot
/public/storage
/storage/*.key
/vendor
.env
.env.backup
.env.production
.phpunit.result.cache
Homestead.json
Homestead.yaml
auth.json
npm-debug.log
yarn-error.log
/.fleet
/.idea
/.vscode
```

---

## 3. Configuration Render

### Étape 3.1: Créer un Compte Render

1. Aller sur [render.com](https://render.com)
2. Cliquer sur **Sign Up**
3. Choisir "Sign up with GitHub"
4. Autoriser l'accès à vos repositories

### Étape 3.2: Créer la Base de Données PostgreSQL

1. **Dashboard Render** → **New** → **PostgreSQL**
2. Configurer :
   - **Name**: `smartstock-db`
   - **Database**: `smartstock`
   - **User**: (généré automatiquement)
   - **Region**: `Frankfurt` (ou le plus proche)
   - **Plan**: `Starter` (gratuit)
3. Cliquer sur **Create Database**
4. **Attendre 2-3 minutes** que la base soit créée

### Étape 3.3: Créer le Service Web

1. **Dashboard Render** → **New** → **Web Service**
2. Connecter votre repository GitHub `SmartStock`
3. Configurer :

```yaml
Name: smartstock
Region: Frankfurt
Branch: main
Runtime: PHP
Build Command: ./render-build.sh
Start Command: php artisan serve --host=0.0.0.0 --port=$PORT
Plan: Starter (gratuit)
```

### Étape 3.4: Configurer les Variables d'Environnement

Dans **Environment** → **Environment Variables**, ajouter :

```bash
# Application
APP_NAME=SmartStock
APP_ENV=production
APP_DEBUG=false
APP_URL=https://votre-app.onrender.com  # Sera fourni après création

# Base de données (copier depuis smartstock-db)
DB_CONNECTION=pgsql
DB_HOST=[copier depuis la DB]
DB_PORT=[copier depuis la DB]
DB_DATABASE=smartstock
DB_USERNAME=[copier depuis la DB]
DB_PASSWORD=[copier depuis la DB]

# Cache & Session
CACHE_DRIVER=database
SESSION_DRIVER=database
QUEUE_CONNECTION=database

# Email SendGrid
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@votre-domaine.com
MAIL_FROM_NAME=SmartStock
SENDGRID_API_KEY=[votre clé SendGrid]

# Logs
LOG_CHANNEL=stack
LOG_LEVEL=error
```

**Important** : Render génère automatiquement `APP_KEY`, pas besoin de l'ajouter manuellement.

### Étape 3.5: Obtenir la Clé SendGrid

1. Aller sur [sendgrid.com](https://sendgrid.com)
2. Créer un compte gratuit
3. **Settings** → **API Keys** → **Create API Key**
4. Donner les permissions "Mail Send"
5. Copier la clé (vous ne la verrez qu'une fois)
6. Ajouter dans Render comme `SENDGRID_API_KEY`

### Étape 3.6: Déployer

1. Cliquer sur **Create Web Service**
2. **Le premier déploiement prendra 5-10 minutes**
3. Suivre les logs en temps réel

---

## 4. Configuration CI/CD

### Étape 4.1: Obtenir les Secrets Render

1. **Render Dashboard** → **Account Settings** → **API Keys**
2. Créer une nouvelle clé API
3. Copier la clé (format: `rnd_...`)

4. Trouver votre Service ID :
   - Aller sur votre service web `smartstock`
   - L'URL sera: `https://dashboard.render.com/web/srv-XXXXX`
   - Le Service ID est `srv-XXXXX`

### Étape 4.2: Ajouter les Secrets GitHub

1. **GitHub Repository** → **Settings** → **Secrets and variables** → **Actions**
2. Cliquer sur **New repository secret**
3. Ajouter :

```
RENDER_API_KEY = rnd_votre_cle_api
RENDER_SERVICE_ID = srv-votre_service_id
```

### Étape 4.3: Activer GitHub Actions

1. **GitHub Repository** → **Actions**
2. Cliquer sur **I understand my workflows, go ahead and enable them**
3. Le workflow se déclenchera automatiquement au prochain push

### Étape 4.4: Tester le Pipeline

```bash
# Faire une petite modification
echo "# SmartStock - Inventory Management" > README.md

# Commit et push
git add README.md
git commit -m "test: Trigger CI/CD pipeline"
git push origin main
```

**Vérifier** :
1. **GitHub** → **Actions** → Voir le workflow en cours
2. Si tout est vert ✅, le déploiement sur Render se fait automatiquement
3. **Render Dashboard** → **Events** → Voir le nouveau déploiement

---

## 5. Premier Déploiement

### Étape 5.1: Vérifier que l'App est En Ligne

1. Aller sur l'URL fournie par Render (ex: `https://smartstock.onrender.com`)
2. Vous devriez voir la page de login

### Étape 5.2: Créer le Premier SuperAdmin

**Option 1 : Via Shell Render**

1. **Render Dashboard** → Votre service → **Shell**
2. Exécuter :

```bash
php artisan db:seed --class=RolesAndPermissionsSeeder
```

**Option 2 : Via Migration Locale + Push**

```bash
# Sur votre machine locale
php artisan db:seed --class=RolesAndPermissionsSeeder

# Puis déployer
git push origin main
```

### Étape 5.3: Se Connecter

1. Aller sur `https://votre-app.onrender.com/login`
2. Utiliser les credentials du seeder :
   - Email: (voir dans votre seeder)
   - Password: (voir dans votre seeder)

---

## 6. Vérifications Post-Déploiement

### Checklist de Santé

```bash
# Sur Render Shell
php artisan config:clear
php artisan cache:clear
php artisan route:cache
php artisan view:cache

# Vérifier la configuration
php artisan config:show database
php artisan config:show mail

# Vérifier les migrations
php artisan migrate:status

# Tester l'envoi d'email
php artisan tinker
>>> \Illuminate\Support\Facades\Mail::raw('Test', function($msg) { $msg->to('votre@email.com')->subject('Test SmartStock'); });
```

### Tests Fonctionnels

- ✅ Login SuperAdmin fonctionne
- ✅ Création de Manager fonctionne
- ✅ Email de bienvenue reçu
- ✅ Création de Vendeur par Manager fonctionne
- ✅ Upload d'images fonctionne
- ✅ Création de produits fonctionne
- ✅ Gestion de stock fonctionne
- ✅ Création de ventes fonctionne

---

## 7. Maintenance

### Mise à Jour de l'Application

```bash
# 1. Développer localement
git checkout -b feature/nouvelle-fonctionnalite

# 2. Tester localement
php artisan test

# 3. Commit
git add .
git commit -m "feat: Ajout nouvelle fonctionnalité"

# 4. Merger dans main
git checkout main
git merge feature/nouvelle-fonctionnalite

# 5. Push (déclenche automatiquement le CI/CD)
git push origin main
```

### Surveiller les Logs

**Render Dashboard** → Votre service → **Logs**

Filtrer par :
- `error` - Voir les erreurs
- `warning` - Voir les avertissements
- `info` - Informations générales

### Sauvegardes Base de Données

Render fait des sauvegardes automatiques quotidiennes (plan Starter).

**Backup manuel** :
1. **Render Dashboard** → `smartstock-db` → **Backups**
2. Cliquer sur **Create Backup**

---

## 8. Troubleshooting

### Problème: "502 Bad Gateway"

**Solution** :
```bash
# Vérifier les logs Render
# Souvent causé par :
1. APP_KEY manquante → Render la génère automatiquement
2. Migrations échouées → Vérifier les logs de build
3. Port incorrect → Utiliser $PORT dans start command
```

### Problème: "SQLSTATE Connection refused"

**Solution** :
```bash
# Vérifier que les variables DB_* sont correctes
# Dans Render → Environment → copier depuis smartstock-db
```

### Problème: "Storage link not found"

**Solution** :
```bash
# Dans Render Shell
php artisan storage:link
```

### Problème: Emails ne partent pas

**Solution** :
```bash
# Vérifier SendGrid API key
# Vérifier que MAIL_FROM_ADDRESS est valide
# Tester avec tinker (voir section 6.1)
```

### Problème: GitHub Actions échouent

**Solution** :
```bash
# Vérifier que les secrets sont bien configurés
# RENDER_API_KEY et RENDER_SERVICE_ID dans GitHub Secrets
```

---

## 📞 Support

- **Documentation Laravel** : [laravel.com/docs](https://laravel.com/docs)
- **Documentation Render** : [render.com/docs](https://render.com/docs)
- **GitHub Actions** : [docs.github.com/actions](https://docs.github.com/en/actions)

---

## 🎉 Félicitations !

Votre application SmartStock est maintenant déployée en production avec :
- ✅ Déploiement automatique sur chaque push
- ✅ Tests automatisés
- ✅ Base de données PostgreSQL sécurisée
- ✅ Emails via SendGrid
- ✅ Logs centralisés
- ✅ Sauvegardes automatiques

**Prochaines étapes recommandées** :
1. Configurer un nom de domaine personnalisé
2. Activer HTTPS (gratuit sur Render)
3. Mettre en place des alertes de monitoring
4. Configurer Redis pour le cache (plan payant)
5. Ajouter un CDN pour les assets statiques
