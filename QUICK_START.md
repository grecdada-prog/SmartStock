# ⚡ Guide de Démarrage Rapide - Déploiement Render

## 🎯 Objectif
Déployer SmartStock sur Render en **moins de 15 minutes**.

---

## ✅ Checklist Avant de Commencer

- [ ] Compte GitHub créé
- [ ] Code poussé sur GitHub
- [ ] Compte Render créé ([render.com](https://render.com))
- [ ] Compte SendGrid créé ([sendgrid.com](https://sendgrid.com))

---

## 🚀 Étape 1 : Pousser le Code sur GitHub (3 min)

```bash
cd c:\Users\hp\Desktop\FN\SmartStock

# Vérifier que tout est commité
git status

# Si besoin, ajouter et commiter
git add .
git commit -m "chore: Prepare for Render deployment"

# Créer le repository sur GitHub (via l'interface web)
# Puis lier et pousser:
git remote add origin https://github.com/VOTRE-USERNAME/SmartStock.git
git branch -M main
git push -u origin main
```

---

## 🎨 Étape 2 : Déployer sur Render (8 min)

### 2.1 Créer un Compte Render

1. Aller sur [render.com](https://render.com)
2. Cliquer **Get Started**
3. Choisir **Sign up with GitHub**
4. Autoriser Render à accéder à vos repositories

### 2.2 Créer un Nouveau Blueprint

1. **Dashboard Render** → **New** → **Blueprint**
2. Connecter votre repository **SmartStock**
3. Render détecte automatiquement le fichier `render.yaml` ! ✨
4. Cliquer **Apply**

### 2.3 Render Crée Automatiquement

Render va créer automatiquement :
- ✅ Service Web Laravel
- ✅ Base de données PostgreSQL
- ✅ Variables d'environnement de base
- ✅ Connexion entre le service et la DB

**⏳ Le déploiement initial prend 5-8 minutes.**

### 2.4 Configurer les Variables Manquantes

Pendant le déploiement, ajouter les variables manquantes :

1. **Dashboard** → Votre service **smartstock** → **Environment**
2. Ajouter ces variables :

```bash
# URL de votre application (sera générée par Render)
APP_URL=https://smartstock.onrender.com

# Email SendGrid (voir étape 3)
MAIL_PASSWORD=VOTRE_CLE_API_SENDGRID
MAIL_FROM_ADDRESS=noreply@votre-domaine.com
```

3. Cliquer **Save Changes**

**Important** : Render configure automatiquement toutes les variables de base de données (`DB_HOST`, `DB_PORT`, etc.) grâce au fichier `render.yaml`.

### 2.5 Vérifier le Build

1. Aller dans **Logs** pour suivre le déploiement
2. Vous devriez voir :
   ```
   🚀 Starting SmartStock build process...
   📦 Installing Composer dependencies...
   🗄️  Running database migrations...
   👤 Seeding Super Admin...
   ✅ Build completed successfully!
   ```

3. Quand vous voyez **"Live"** en vert, c'est prêt ! ✅

---

## 📧 Étape 3 : Configurer SendGrid (2 min)

1. Aller sur [sendgrid.com](https://sendgrid.com) → S'inscrire
2. **Settings** → **API Keys** → **Create API Key**
3. Nom: `SmartStock Production`
4. Permissions: **Full Access** (ou juste "Mail Send")
5. **Créer** et **copier la clé** (commence par `SG.`)
6. **Render** → Votre service → **Environment** → Modifier:
   ```
   MAIL_PASSWORD=SG.votre_cle_ici
   ```
7. **Save Changes** → Render redéploie automatiquement

---

## 🎉 Étape 4 : Vérifier le Déploiement (2 min)

### 4.1 Accéder à l'application

1. Sur votre Dashboard Render, copier l'URL du service
2. Ouvrir dans le navigateur : `https://smartstock.onrender.com`
3. Vous devriez voir la page de login SmartStock ✅

### 4.2 Se connecter en tant que SuperAdmin

Le SuperAdmin a été créé automatiquement pendant le build !

**Credentials par défaut** (définis dans `SuperAdminSeeder.php`) :

```
Email: admin@smartstock.com
Password: Admin@2024
```

**⚠️ IMPORTANT** : Changez ce mot de passe immédiatement après la première connexion !

### 4.3 Tester l'envoi d'email

1. Connectez-vous en tant que SuperAdmin
2. Allez dans **Utilisateurs** → **Créer un utilisateur**
3. Créez un Manager ou Vendeur
4. Vérifiez que l'email de bienvenue est reçu ✅

---

## 🤖 Étape 5 : Activer le CI/CD avec GitHub Actions (Optionnel - 3 min)

Le repository contient déjà les workflows GitHub Actions configurés dans `.github/workflows/`.

### 5.1 Obtenir le Render Deploy Hook

1. **Dashboard Render** → Votre service **smartstock**
2. **Settings** → **Deploy Hook**
3. Copier l'URL du hook (ex: `https://api.render.com/deploy/srv-xxx?key=yyy`)

### 5.2 Ajouter dans GitHub Secrets

1. **GitHub** → **Votre repo SmartStock** → **Settings** → **Secrets and variables** → **Actions**
2. Cliquer **New repository secret**
3. Ajouter :
   - Name: `RENDER_DEPLOY_HOOK`
   - Value: `https://api.render.com/deploy/srv-xxx?key=yyy`

### 5.3 Tester le Pipeline

```bash
# Faire une petite modification
echo "# SmartStock - Déployé sur Render" >> README.md

git add README.md
git commit -m "test: Trigger CI/CD pipeline"
git push origin main
```

**Vérifier** :
- **GitHub** → **Actions** → Voir le workflow `deploy.yml` s'exécuter
- Tests automatiques → Build → Déploiement sur Render ✅

---

## ✅ Checklist Finale

- [ ] Application accessible en ligne sur Render
- [ ] SuperAdmin créé et login fonctionne
- [ ] Email de bienvenue envoyé et reçu
- [ ] Mot de passe SuperAdmin changé
- [ ] GitHub Actions activées (optionnel)
- [ ] Pipeline CI/CD fonctionne (optionnel)

---

## 🎊 Bravo !

Votre application SmartStock est maintenant :
- ✅ **Déployée en production** sur Render
- ✅ **Base de données PostgreSQL** configurée automatiquement
- ✅ **Déploiement automatique** via `render.yaml`
- ✅ **Emails** opérationnels via SendGrid
- ✅ **Logs** en temps réel sur Render
- ✅ **SSL/HTTPS** gratuit et automatique
- ✅ **SuperAdmin** créé automatiquement

---

## 📌 Prochaines Étapes (Optionnel)

### 1. Domaine Personnalisé

1. **Render** → Votre service → **Settings** → **Custom Domain**
2. Ajouter votre domaine (ex: `smartstock.votredomaine.com`)
3. Configurer les DNS chez votre registrar :
   ```
   Type: CNAME
   Name: smartstock
   Value: smartstock.onrender.com
   ```

### 2. Monitoring et Logs

1. **Metrics** : Render → Votre service → **Metrics**
   - CPU, RAM, Requêtes/sec
2. **Logs** : Render → Votre service → **Logs**
   - Logs en temps réel
   - Filtres par niveau (error, warning, info)

### 3. Backups Automatiques

1. **Render** → Base de données **smartstock-db** → **Backups**
2. Activer les backups automatiques quotidiens
3. Définir la rétention (7, 14, 30 jours)

### 4. Scaling (Plan Payant)

1. **Settings** → **Instance Type**
2. Passer à un plan supérieur pour :
   - Plus de RAM/CPU
   - Auto-scaling
   - Replicas multiples

### 5. Cache Redis (Optionnel)

1. **Dashboard** → **New** → **Redis**
2. Modifier les variables d'environnement :
   ```
   CACHE_DRIVER=redis
   SESSION_DRIVER=redis
   REDIS_HOST=${REDIS_HOST}
   REDIS_PORT=${REDIS_PORT}
   ```

---

## 🆘 Problèmes Courants

### ❌ "Application Error" ou "502 Bad Gateway"

**Cause** : Build échoué ou migrations ratées

**Solution** :
1. Vérifier les logs : **Logs** → Chercher les erreurs rouges
2. Vérifier les variables d'environnement : **Environment**
3. Redéployer manuellement : **Manual Deploy** → **Deploy latest commit**

### ❌ "Database connection failed"

**Cause** : Variables de base de données mal configurées

**Solution** :
1. Vérifier que `render.yaml` est correctement configuré
2. Vérifier que la base de données **smartstock-db** existe
3. Vérifier les variables : `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`

### ❌ "Emails ne partent pas"

**Cause** : SendGrid mal configuré

**Solution** :
1. Vérifier la variable `MAIL_PASSWORD` (doit contenir la clé API SendGrid)
2. Vérifier `MAIL_FROM_ADDRESS` (doit être un email valide)
3. Vérifier sur SendGrid que la clé API a les permissions "Mail Send"
4. Regarder les logs SendGrid : [https://app.sendgrid.com/email_activity](https://app.sendgrid.com/email_activity)

### ❌ "APP_KEY not set"

**Cause** : Variable `APP_KEY` manquante

**Solution** :
1. Render génère automatiquement `APP_KEY` si `generateValue: true` dans `render.yaml`
2. Ou générer manuellement :
   ```bash
   php artisan key:generate --show
   ```
3. Ajouter dans **Environment** :
   ```
   APP_KEY=base64:la_clé_générée
   ```

### ❌ "Storage link not found"

**Cause** : Lien symbolique `storage` non créé

**Solution** : C'est automatique dans `render-build.sh`, mais si besoin :
1. **Render Shell** (plan payant) → Exécuter :
   ```bash
   php artisan storage:link
   ```

### ❌ Build lent (> 10 minutes)

**Cause** : Dépendances Composer volumineuses

**Solution** :
1. C'est normal pour le premier build (5-8 min)
2. Les builds suivants sont plus rapides (cache)
3. Si trop lent, vérifier `composer.json` pour des dépendances inutiles

---

## 🚀 Commandes Utiles

### Via Render Dashboard

```bash
# Voir les logs en temps réel
Dashboard → Votre service → Logs

# Redéployer manuellement
Dashboard → Votre service → Manual Deploy → Deploy latest commit

# Variables d'environnement
Dashboard → Votre service → Environment

# Métriques
Dashboard → Votre service → Metrics
```

### Via Render Shell (Plan Payant)

```bash
# Accéder au shell
Dashboard → Votre service → Shell

# Exécuter des commandes Laravel
php artisan migrate
php artisan cache:clear
php artisan db:seed --class=SuperAdminSeeder

# Voir les logs Laravel
tail -f storage/logs/laravel.log
```

---

## 📞 Support

- **Documentation Render** : [docs.render.com](https://docs.render.com)
- **Community Render** : [community.render.com](https://community.render.com)
- **Status Page** : [status.render.com](https://status.render.com)
- **Support Email** : support@render.com

---

## 🔄 Workflow de Développement

### 1. Développement Local

```bash
# Travailler sur une branche feature
git checkout -b feature/nouvelle-fonctionnalite

# Développer, tester en local
php artisan serve

# Commit
git add .
git commit -m "feat: Ajouter nouvelle fonctionnalité"

# Push vers GitHub
git push origin feature/nouvelle-fonctionnalite
```

### 2. Pull Request et Tests

1. Créer une PR sur GitHub
2. GitHub Actions exécute automatiquement les tests (`.github/workflows/tests.yml`)
3. Review du code
4. Merge vers `main`

### 3. Déploiement Automatique

1. Dès que `main` est mis à jour, Render déploie automatiquement
2. Ou utiliser GitHub Actions (`.github/workflows/deploy.yml`) avec `RENDER_DEPLOY_HOOK`
3. Vérifier les logs de déploiement
4. Tester en production

---

**Pour plus de détails** → Voir [DEPLOYMENT.md](DEPLOYMENT.md)

**Render est prêt ! 🎉**
