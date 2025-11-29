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

## 🚀 Étape 1 : Pousser le Code sur GitHub (5 min)

```bash
cd c:\Users\hp\Desktop\FN\SmartStock

# Vérifier que tout est commité
git status

# Ajouter tous les fichiers de déploiement
git add .
git commit -m "chore: Prepare for Render deployment with CI/CD"

# Créer le repository sur GitHub (via l'interface web)
# Puis lier et pousser:
git remote add origin https://github.com/VOTRE-USERNAME/SmartStock.git
git branch -M main
git push -u origin main
```

---

## 🗄️ Étape 2 : Créer la Base de Données (2 min)

1. **Render Dashboard** → **New** → **PostgreSQL**
2. Remplir :
   - Name: `smartstock-db`
   - Database: `smartstock`
   - Region: `Frankfurt`
   - Plan: `Starter` (gratuit)
3. Cliquer **Create Database**
4. ⏳ Attendre 2-3 minutes

---

## 🌐 Étape 3 : Créer le Web Service (5 min)

1. **Render Dashboard** → **New** → **Web Service**
2. Connecter le repository `SmartStock`
3. Remplir :
   - Name: `smartstock`
   - Region: `Frankfurt`
   - Branch: `main`
   - Build Command: `./render-build.sh`
   - Start Command: `php artisan serve --host=0.0.0.0 --port=$PORT`
   - Plan: `Starter`

4. **Ajouter les variables d'environnement** :

```bash
APP_NAME=SmartStock
APP_ENV=production
APP_DEBUG=false

# DB (copier depuis smartstock-db → "Info" tab)
DB_CONNECTION=pgsql
DB_HOST=dpg-xxxxx.frankfurt-postgres.render.com
DB_PORT=5432
DB_DATABASE=smartstock
DB_USERNAME=smartstock_user
DB_PASSWORD=[copier depuis DB Info]

# Cache
CACHE_DRIVER=database
SESSION_DRIVER=database
QUEUE_CONNECTION=database

# Email (voir étape 4)
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@votre-domaine.com
SENDGRID_API_KEY=[À ajouter après]
```

5. Cliquer **Create Web Service**
6. ⏳ Attendre 5-10 minutes pour le premier déploiement

---

## 📧 Étape 4 : Configurer SendGrid (3 min)

1. Aller sur [sendgrid.com](https://sendgrid.com) → S'inscrire
2. **Settings** → **API Keys** → **Create API Key**
3. Nom: `SmartStock Production`
4. Permissions: **Full Access** (ou juste "Mail Send")
5. **Créer** et **copier la clé** (commence par `SG.`)
6. **Render** → Votre service → **Environment** → Ajouter:
   - `SENDGRID_API_KEY` = `SG.votre_cle`
7. Sauvegarder (déclenche un redéploiement automatique)

---

## 🎉 Étape 5 : Vérifier le Déploiement (2 min)

### 5.1 Accéder à l'application

URL fournie par Render : `https://smartstock-xxxx.onrender.com`

### 5.2 Créer le SuperAdmin

**Option A : Via Shell Render**
```bash
# Render Dashboard → Votre service → Shell
php artisan db:seed --class=RolesAndPermissionsSeeder
```

**Option B : Via migration locale**
```bash
# Sur votre PC
php artisan db:seed --class=RolesAndPermissionsSeeder
# (si DB en local, sinon connecter à Render DB)
```

### 5.3 Se connecter

1. Aller sur `https://votre-app.onrender.com/login`
2. Utiliser les credentials de votre seeder
3. ✅ **Félicitations, c'est en ligne !**

---

## 🤖 Étape 6 : Activer le CI/CD (5 min)

### 6.1 Obtenir les credentials Render

1. **Render** → **Account Settings** → **API Keys** → **Create**
2. Copier la clé : `rnd_xxxxx`
3. Trouver le Service ID :
   - URL de votre service : `https://dashboard.render.com/web/srv-xxxxx`
   - Service ID = `srv-xxxxx`

### 6.2 Ajouter dans GitHub

1. **GitHub** → **Votre repo** → **Settings** → **Secrets and variables** → **Actions**
2. Ajouter 2 secrets :
   - `RENDER_API_KEY` = `rnd_xxxxx`
   - `RENDER_SERVICE_ID` = `srv-xxxxx`

### 6.3 Tester le pipeline

```bash
# Faire une petite modification
echo "# SmartStock" > TEST.md

git add TEST.md
git commit -m "test: Trigger CI/CD pipeline"
git push origin main
```

**Vérifier** :
- **GitHub** → **Actions** → Voir le workflow qui tourne
- Si tout est vert ✅ → Déploiement automatique sur Render

---

## ✅ Checklist Finale

- [ ] Application accessible en ligne
- [ ] SuperAdmin créé et login fonctionne
- [ ] Email de test envoyé et reçu
- [ ] GitHub Actions activées
- [ ] Pipeline CI/CD fonctionne

---

## 🎊 Bravo !

Votre application est maintenant :
- ✅ **Déployée en production** sur Render
- ✅ **CI/CD configuré** - déploiement automatique à chaque push
- ✅ **Base de données** PostgreSQL sécurisée
- ✅ **Emails** opérationnels via SendGrid
- ✅ **Logs** centralisés sur Render

---

## 📌 Prochaines Étapes (Optionnel)

1. **Domaine personnalisé** :
   - Render → Votre service → Settings → Custom Domain

2. **Alertes de monitoring** :
   - Render → Notifications → Configure

3. **Scaling** :
   - Passer au plan "Standard" pour plus de ressources

4. **Redis Cache** :
   - Ajouter un service Redis sur Render

5. **Backups automatiques** :
   - Déjà activés sur plan Starter (quotidiens)

---

## 🆘 Problèmes Courants

### "502 Bad Gateway"
→ Vérifier les logs Render, souvent causé par migrations échouées

### "Database connection failed"
→ Vérifier que les variables DB_* sont bien copiées depuis smartstock-db

### "Emails ne partent pas"
→ Vérifier SENDGRID_API_KEY et MAIL_FROM_ADDRESS

### "GitHub Actions fail"
→ Vérifier que RENDER_API_KEY et RENDER_SERVICE_ID sont dans GitHub Secrets

---

**Pour plus de détails** → Voir [DEPLOYMENT.md](DEPLOYMENT.md)
