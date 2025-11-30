# 🐳 Guide de Déploiement Docker - SmartStock sur Render

Ce guide vous accompagne pour déployer SmartStock sur Render en utilisant le **runtime Docker**.

## 📋 Prérequis

- ✅ **Compte Render** (gratuit) - [render.com](https://render.com)
- ✅ **Compte GitHub** - Pour héberger le code
- ✅ **Base de données PostgreSQL** - Déjà déployée sur Render (comme mentionné)
- ✅ **Compte SendGrid** (optionnel) - Pour les emails

---

## 🚀 Déploiement Rapide

### Étape 1: Préparer le Code

Assurez-vous que votre code est sur GitHub :

```bash
# Vérifier que tout est commité
git status

# Si nécessaire, commit et push
git add .
git commit -m "Ready for Docker deployment on Render"
git push origin main
```

### Étape 2: Créer le Service Web sur Render

1. **Aller sur [render.com](https://render.com)** et se connecter
2. **Dashboard** → **New** → **Web Service**
3. **Connecter votre repository GitHub** `SmartStock`
4. **Configurer le service** :
   - **Name**: `smartstock` (ou votre choix)
   - **Region**: `Frankfurt` (ou la région la plus proche)
   - **Branch**: `main`
   - **Runtime**: **Docker** ⚠️ (Important: choisir Docker, pas PHP)
   - **Dockerfile Path**: `./Dockerfile` (par défaut)
   - **Docker Context**: `.` (par défaut)
   - **Plan**: `Free` (ou `Starter` pour de meilleures performances)

### Étape 3: Configurer les Variables d'Environnement

Dans **Environment** → **Environment Variables**, ajouter les variables suivantes :

#### Variables Automatiques (via render.yaml)

Si vous utilisez `render.yaml`, certaines variables sont déjà configurées. Sinon, ajoutez-les manuellement :

```bash
# Application
APP_NAME=SmartStock
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...  # Render peut le générer automatiquement
APP_URL=https://votre-app.onrender.com  # Sera fourni après création
APP_TIMEZONE=UTC
APP_LOCALE=fr

# Base de données PostgreSQL (depuis votre BD Render existante)
DB_CONNECTION=pgsql
DB_HOST=dpg-xxxxx-a.frankfurt-postgres.render.com  # Depuis votre BD Render
DB_PORT=5432
DB_DATABASE=smartstock  # Nom de votre base
DB_USERNAME=smartstock_user  # Depuis votre BD Render
DB_PASSWORD=votre_mot_de_passe  # Depuis votre BD Render

# Cache & Session
CACHE_DRIVER=database
SESSION_DRIVER=database
QUEUE_CONNECTION=database
SESSION_LIFETIME=120

# Email SendGrid (optionnel)
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=SG.xxxxx  # Votre clé API SendGrid
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@votre-domaine.com
MAIL_FROM_NAME=SmartStock

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=error
BROADCAST_DRIVER=log
FILESYSTEM_DISK=local
```

#### Comment obtenir les informations de la base de données ?

1. **Render Dashboard** → Votre base de données PostgreSQL
2. Dans la section **Connections**, vous trouverez :
   - **Internal Database URL** : Contient toutes les infos
   - Ou copiez individuellement : Host, Port, Database, Username, Password

### Étape 4: Déployer

1. Cliquer sur **Create Web Service**
2. **Le premier déploiement prendra 5-10 minutes**
3. Suivre les logs en temps réel dans **Logs**

### Étape 5: Vérifier le Déploiement

Une fois le déploiement terminé :

1. **Vérifier l'URL** : `https://votre-app.onrender.com`
2. **Vérifier les logs** pour s'assurer qu'il n'y a pas d'erreurs
3. **Tester l'application** : Aller sur l'URL et vérifier que la page de login s'affiche

---

## 🔧 Utilisation de render.yaml (Recommandé)

Si vous utilisez `render.yaml`, Render peut créer automatiquement le service avec la configuration :

1. **Render Dashboard** → **New** → **Blueprint**
2. **Connecter votre repository GitHub**
3. Render détectera automatiquement `render.yaml`
4. **Configurer manuellement** les variables marquées `sync: false` :
   - `APP_URL` (après le premier déploiement)
   - `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
   - `MAIL_PASSWORD` (clé SendGrid)
   - `MAIL_FROM_ADDRESS`

---

## 📊 Structure du Dockerfile

Le Dockerfile est optimisé pour Render :

1. **Base**: PHP 8.2 CLI (pas FPM, car Render utilise `php artisan serve`)
2. **Dépendances**: Node.js 20, Composer, extensions PHP nécessaires
3. **Build**: Installation des dépendances PHP et Node.js, compilation des assets
4. **Runtime**: Script de démarrage qui :
   - Exécute les migrations
   - Crée le lien symbolique de stockage
   - Optimise Laravel (cache config, routes, views)
   - Démarre le serveur sur le port injecté par Render

---

## 🐛 Troubleshooting

### Problème: "502 Bad Gateway"

**Causes possibles** :
- Migrations échouées
- Variables d'environnement manquantes (APP_KEY, DB_*)
- Port incorrect

**Solution** :
```bash
# Vérifier les logs Render
# Vérifier que toutes les variables d'environnement sont configurées
# Vérifier que la base de données est accessible
```

### Problème: "SQLSTATE Connection refused"

**Solution** :
1. Vérifier que les variables `DB_*` sont correctes
2. Vérifier que la base de données est bien démarrée sur Render
3. Vérifier que le host de la BD est accessible depuis le service web

### Problème: "Storage link not found"

**Solution** :
Le script de démarrage crée automatiquement le lien. Si le problème persiste :
```bash
# Via Render Shell
php artisan storage:link
```

### Problème: "Assets not found (CSS/JS)"

**Solution** :
1. Vérifier que `npm run build` s'est exécuté correctement lors du build
2. Vérifier les logs de build Docker
3. Vérifier que `/public/build` existe dans l'image

### Problème: Build Docker échoue

**Causes possibles** :
- Erreur dans le Dockerfile
- Problème de permissions
- Dépendances manquantes

**Solution** :
1. Vérifier les logs de build
2. Tester localement : `docker build -t smartstock .`
3. Vérifier que tous les fichiers nécessaires sont présents

---

## 🔄 Mise à Jour

Pour mettre à jour l'application :

```bash
# 1. Développer localement
git checkout -b feature/nouvelle-fonctionnalite

# 2. Tester
php artisan test

# 3. Commit et push
git add .
git commit -m "feat: Nouvelle fonctionnalité"
git push origin feature/nouvelle-fonctionnalite

# 4. Merger dans main
git checkout main
git merge feature/nouvelle-fonctionnalite
git push origin main
```

**Render déploiera automatiquement** si vous avez activé l'auto-deploy sur la branche `main`.

---

## 📝 Notes Importantes

### Variables d'Environnement

- **PORT** : Injecté automatiquement par Render, ne pas définir manuellement
- **APP_KEY** : Peut être généré automatiquement par Render (`generateValue: true`)
- **APP_URL** : À configurer après le premier déploiement

### Base de Données

- La base de données doit être **déjà créée** sur Render
- Utiliser les **Internal Database URL** pour la connexion (plus sécurisé)
- Les migrations s'exécutent automatiquement au démarrage

### Performance

- Le plan **Free** peut avoir des limitations (spin down après inactivité)
- Le plan **Starter** offre de meilleures performances
- Pour la production, considérer un plan payant

### Sécurité

- Ne jamais commiter le fichier `.env`
- Utiliser les secrets Render pour les informations sensibles
- Activer HTTPS (gratuit sur Render)

---

## ✅ Checklist de Déploiement

- [ ] Code sur GitHub
- [ ] Base de données PostgreSQL créée sur Render
- [ ] Service web créé avec runtime Docker
- [ ] Variables d'environnement configurées
- [ ] Première migration réussie
- [ ] Application accessible via l'URL Render
- [ ] Page de login s'affiche correctement
- [ ] Assets (CSS/JS) chargés correctement
- [ ] Connexion à la base de données fonctionnelle
- [ ] Emails configurés (si SendGrid activé)

---

## 🎉 Félicitations !

Votre application SmartStock est maintenant déployée sur Render avec Docker !

**Prochaines étapes recommandées** :
1. Créer le premier SuperAdmin (via seeder ou interface)
2. Configurer un nom de domaine personnalisé
3. Mettre en place des alertes de monitoring
4. Configurer des sauvegardes automatiques

---

## 📞 Support

- **Documentation Render** : [render.com/docs](https://render.com/docs)
- **Documentation Laravel** : [laravel.com/docs](https://laravel.com/docs)
- **Documentation Docker** : [docs.docker.com](https://docs.docker.com)

