# 🚀 Configuration Rapide SendGrid

## Option 1 : Configuration Manuelle (Recommandée)

### 1. Obtenez votre clé API SendGrid

Allez sur : https://app.sendgrid.com/settings/api_keys

Créez une clé avec **Full Access** et copiez-la.

### 2. Modifiez votre fichier .env

Ouvrez `.env` et changez la section MAIL :

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=SG.votre_cle_api_ici
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@votre-domaine.com"
MAIL_FROM_NAME="SmartStock"
```

**Remplacez :**
- `SG.votre_cle_api_ici` par votre vraie clé API SendGrid
- `noreply@votre-domaine.com` par l'email que vous avez vérifié sur SendGrid

### 3. Testez la configuration

```bash
# Dans un terminal
php artisan queue:work

# Dans un autre terminal
php artisan email:test user-created 1
```

Vérifiez que l'email arrive bien !

---

## Option 2 : Utiliser le Template

Si vous voulez repartir de zéro :

```bash
# Sauvegardez votre .env actuel
cp .env .env.backup

# Utilisez le template SendGrid
cp .env.sendgrid.template .env

# Éditez .env et remplacez :
# - VOTRE_CLE_API_SENDGRID par votre clé
# - noreply@smartstock.com par votre email vérifié
```

---

## ✅ Checklist de Configuration

- [ ] Compte SendGrid créé
- [ ] Clé API créée (commence par `SG.`)
- [ ] Email sender vérifié sur SendGrid
- [ ] `.env` modifié avec la clé API
- [ ] `MAIL_FROM_ADDRESS` correspond à l'email vérifié
- [ ] Queue worker démarré (`php artisan queue:work`)
- [ ] Test effectué (`php artisan email:test user-created 1`)
- [ ] Email reçu avec succès

---

## 🎯 Configuration Minimale Requise

Voici les **4 lignes essentielles** à modifier dans `.env` :

```env
MAIL_MAILER=smtp                                    # Changer de "log" à "smtp"
MAIL_HOST=smtp.sendgrid.net                         # Serveur SendGrid
MAIL_USERNAME=apikey                                # Toujours "apikey"
MAIL_PASSWORD=SG.xxxxxxxxxxxxxxxxxxxxxxxxxx         # VOTRE clé API SendGrid
```

Le reste peut rester par défaut :
```env
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@votredomaine.com"
MAIL_FROM_NAME="SmartStock"
```

---

## 📧 Vérifier un Sender sur SendGrid

1. Allez sur : https://app.sendgrid.com/settings/sender_auth/senders

2. Cliquez sur **Create New Sender**

3. Remplissez le formulaire :
   ```
   From Name: SmartStock
   From Email: noreply@votredomaine.com
   Reply To: support@votredomaine.com  (optionnel)
   Company Address: Votre adresse
   City: Votre ville
   Country: Cameroun (ou votre pays)
   ```

4. Vérifiez votre email (cliquez sur le lien reçu)

5. Utilisez cet email dans `MAIL_FROM_ADDRESS`

---

## 🔥 Démarrage en 60 secondes

```bash
# 1. Créez un compte sur SendGrid (2 min)
# 2. Créez une clé API (30 sec)
# 3. Modifiez .env (30 sec)

# 4. Démarrez le worker
php artisan queue:work

# 5. Testez (dans un autre terminal)
php artisan email:test all

# 6. Vérifiez votre boîte email !
```

---

## ❓ Besoin d'aide ?

Consultez : **SENDGRID_SETUP.md** pour le guide complet avec captures d'écran et dépannage.
