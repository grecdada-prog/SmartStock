# ⚡ Guide de Démarrage Rapide - Déploiement Railway

## 🎯 Objectif
Déployer SmartStock sur Railway en **moins de 10 minutes** (plus simple que Render !).

---

## ✅ Checklist Avant de Commencer

- [ ] Compte GitHub créé
- [ ] Code poussé sur GitHub
- [ ] Compte Railway créé ([railway.app](https://railway.app))
- [ ] Compte SendGrid créé ([sendgrid.com](https://sendgrid.com))

---

## 🚀 Étape 1 : Pousser le Code sur GitHub (3 min)

```bash
cd c:\Users\hp\Desktop\FN\SmartStock

# Vérifier que tout est commité
git status

# Si besoin, ajouter et commiter
git add .
git commit -m "chore: Prepare for Railway deployment"

# Créer le repository sur GitHub (via l'interface web)
# Puis lier et pousser:
git remote add origin https://github.com/VOTRE-USERNAME/SmartStock.git
git branch -M main
git push -u origin main
```

---

## 🚂 Étape 2 : Déployer sur Railway (5 min)

### 2.1 Créer un Compte Railway

1. Aller sur [railway.app](https://railway.app)
2. Cliquer **Login with GitHub**
3. Autoriser Railway à accéder à vos repositories

### 2.2 Créer un Nouveau Projet

1. **Dashboard Railway** → **New Project**
2. Choisir **Deploy from GitHub repo**
3. Sélectionner votre repository `SmartStock`
4. Railway détecte automatiquement que c'est un projet Laravel ! ✨

### 2.3 Ajouter une Base de Données PostgreSQL

1. Dans votre projet → Cliquer **New** → **Database** → **Add PostgreSQL**
2. Railway crée automatiquement la DB et configure toutes les variables ! 🎉
3. **Pas besoin de configurer manuellement les variables DB_***

### 2.4 Configurer les Variables d'Environnement

1. Cliquer sur votre service **SmartStock**
2. Aller dans **Variables**
3. Ajouter les variables suivantes :

```bash
# Application
APP_NAME=SmartStock
APP_ENV=production
APP_DEBUG=false

# Railway génère automatiquement APP_KEY au premier déploiement

# Email SendGrid (voir étape 3)
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@votre-domaine.com
MAIL_FROM_NAME=SmartStock
SENDGRID_API_KEY=[À ajouter après étape 3]

# Cache & Session
CACHE_DRIVER=database
SESSION_DRIVER=database
QUEUE_CONNECTION=database

# Logs
LOG_CHANNEL=stack
LOG_LEVEL=error
```

**Important** : Railway configure automatiquement `DATABASE_URL` et toutes les variables de base de données. Pas besoin de les ajouter !

### 2.5 Générer un Domaine Public

1. Dans votre service → **Settings** → **Networking**
2. Cliquer **Generate Domain**
3. Copier l'URL : `https://smartstock-production.up.railway.app`
4. Retourner dans **Variables** et ajouter :
   ```
   APP_URL=https://votre-domaine.up.railway.app
   ```

### 2.6 Déployer

1. Railway commence automatiquement le déploiement !
2. Suivre les logs en temps réel : **View Logs**
3. ⏳ **Le premier déploiement prend 3-5 minutes**
4. Quand vous voyez "Application ready", c'est prêt ! ✅

---

## 📧 Étape 3 : Configurer SendGrid (2 min)

1. Aller sur [sendgrid.com](https://sendgrid.com) → S'inscrire
2. **Settings** → **API Keys** → **Create API Key**
3. Nom: `SmartStock Production`
4. Permissions: **Full Access** (ou juste "Mail Send")
5. **Créer** et **copier la clé** (commence par `SG.`)
6. **Railway** → Votre service → **Variables** → Ajouter:
   ```
   SENDGRID_API_KEY=SG.votre_cle_ici
   ```
7. Railway redéploie automatiquement

---

## 🎉 Étape 4 : Vérifier le Déploiement (1 min)

### 4.1 Accéder à l'application

Aller sur l'URL générée : `https://votre-domaine.up.railway.app`

### 4.2 Créer le SuperAdmin

**Option A : Via Railway CLI** (Recommandé)

```bash
# Installer Railway CLI
npm i -g @railway/cli

# Login
railway login

# Link au projet
railway link

# Exécuter le seeder
railway run php artisan db:seed --class=RolesAndPermissionsSeeder
```

**Option B : Via code (temporaire)**

Créer un fichier `routes/temp.php` :

```php
<?php
// TEMPORAIRE - Supprimer après usage
Route::get('/setup-admin', function() {
    Artisan::call('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);
    return 'SuperAdmin créé! Supprimez ce fichier routes/temp.php';
});
```

Puis :
1. Git push vers GitHub
2. Attendre le redéploiement (2-3 min)
3. Visiter `https://votre-app.up.railway.app/setup-admin`
4. **IMPORTANT** : Supprimer `routes/temp.php` et re-push immédiatement

### 4.3 Se connecter

1. Aller sur `https://votre-app.up.railway.app/login`
2. Utiliser les credentials de votre seeder
3. ✅ **Bravo, c'est en ligne !**

---

## 🤖 Étape 5 : Activer le CI/CD (Optionnel - 3 min)

Railway déploie **automatiquement** à chaque push sur `main`. Mais pour avoir des tests automatiques avant :

### 5.1 Obtenir le Token Railway

1. **Railway** → **Account Settings** → **Tokens**
2. Créer un nouveau token : **Create Token**
3. Copier le token

### 5.2 Trouver votre Project ID

```bash
railway login
railway status
```

Ou dans l'URL du projet : `https://railway.app/project/[PROJECT_ID]`

### 5.3 Ajouter dans GitHub

1. **GitHub** → **Votre repo** → **Settings** → **Secrets and variables** → **Actions**
2. Ajouter 2 secrets :
   - `RAILWAY_TOKEN` = `votre_token`
   - `RAILWAY_PROJECT_ID` = `votre_project_id`

### 5.4 Tester le pipeline

```bash
# Faire une petite modification
echo "# SmartStock - Déployé sur Railway" >> README.md

git add README.md
git commit -m "test: Trigger CI/CD pipeline"
git push origin main
```

**Vérifier** :
- **GitHub** → **Actions** → Voir le workflow
- Tests automatiques avant déploiement ✅

---

## ✅ Checklist Finale

- [ ] Application accessible en ligne
- [ ] SuperAdmin créé et login fonctionne
- [ ] Email de test envoyé et reçu
- [ ] GitHub Actions activées (optionnel)
- [ ] Pipeline CI/CD fonctionne (optionnel)

---

## 🎊 Bravo !

Votre application est maintenant :
- ✅ **Déployée en production** sur Railway
- ✅ **Base de données PostgreSQL** configurée automatiquement
- ✅ **Déploiement automatique** à chaque push
- ✅ **Emails** opérationnels via SendGrid
- ✅ **Logs** en temps réel sur Railway
- ✅ **SSL/HTTPS** gratuit et automatique

---

## 💡 Avantages de Railway vs Render

| Fonctionnalité | Railway | Render |
|----------------|---------|--------|
| Setup initial | ⚡ 5 min | 10 min |
| Config DB auto | ✅ Oui | ❌ Manuel |
| Variables auto | ✅ Oui | ❌ Manuel |
| Détection Laravel | ✅ Auto | ❌ Manuel |
| CLI intégré | ✅ Oui | ❌ Non |
| Logs temps réel | ✅ Excellent | ✅ Bon |
| Gratuit | ✅ $5 offerts | ✅ Limité |

---

## 📌 Prochaines Étapes (Optionnel)

1. **Domaine personnalisé** :
   - Railway → Settings → Custom Domain
   - Ajouter votre domaine

2. **Monitoring** :
   - Railway → Metrics → CPU/RAM/Disk

3. **Scaling** :
   - Railway détecte la charge et scale automatiquement

4. **Redis Cache** :
   - Railway → New → Database → Redis

5. **Backups** :
   - Railway fait des snapshots automatiques

---

## 🆘 Problèmes Courants

### "Application Error"
```bash
# Vérifier les logs
railway logs
```

### "Database connection failed"
```bash
# Railway configure automatiquement, mais vérifier :
railway variables
```

### "502 Bad Gateway"
```bash
# Souvent causé par migrations qui échouent
railway logs --filter="error"
```

### "Emails ne partent pas"
```bash
# Vérifier la variable
railway variables | grep SENDGRID
```

---

## 🚀 Commandes Railway Utiles

```bash
# Voir les logs en temps réel
railway logs

# Exécuter des commandes
railway run php artisan migrate
railway run php artisan cache:clear

# SSH dans le container
railway shell

# Voir les variables
railway variables

# Redéployer
railway up --detach
```

---

## 📞 Support

- **Documentation Railway** : [docs.railway.app](https://docs.railway.app)
- **Discord Railway** : [railway.app/discord](https://railway.app/discord)
- **GitHub Discussions** : Dans votre repo

---

**Pour plus de détails** → Voir [DEPLOYMENT.md](DEPLOYMENT.md)

**Railway est prêt ! 🎉**
