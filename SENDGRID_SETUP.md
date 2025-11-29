# Configuration SendGrid pour SmartStock

## 📋 Étapes de Configuration

### Étape 1 : Créer un compte SendGrid

1. Allez sur [SendGrid](https://signup.sendgrid.com/)
2. Créez un compte gratuit (100 emails/jour)
3. Vérifiez votre email
4. Complétez le questionnaire initial de SendGrid

---

### Étape 2 : Créer une clé API

1. Une fois connecté, allez dans **Settings** > **API Keys**
   - URL directe : https://app.sendgrid.com/settings/api_keys

2. Cliquez sur **Create API Key**

3. Configurez la clé :
   - **API Key Name** : `SmartStock Production` (ou un nom de votre choix)
   - **API Key Permissions** : Choisissez **Full Access** (ou au minimum **Mail Send**)

4. Cliquez sur **Create & View**

5. **IMPORTANT** : Copiez la clé API immédiatement !
   ```
   Elle ressemble à : SG.xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
   ```
   ⚠️ Vous ne pourrez plus la voir après avoir fermé cette fenêtre !

---

### Étape 3 : Vérifier votre domaine (Optionnel mais recommandé)

Pour éviter que vos emails arrivent en spam :

1. Allez dans **Settings** > **Sender Authentication**

2. Cliquez sur **Verify a Single Sender** (plus simple)

3. Remplissez le formulaire :
   - **From Name** : SmartStock
   - **From Email Address** : votre-email@votredomaine.com
   - **Reply To** : (même email ou un autre)
   - **Company Address**, **City**, **Country** : Vos informations

4. Vérifiez votre email et cliquez sur le lien de confirmation

**OU** pour un domaine complet (plus professionnel) :

1. Cliquez sur **Authenticate Your Domain**
2. Suivez les instructions pour ajouter les enregistrements DNS
3. Attendez la validation (quelques minutes à quelques heures)

---

### Étape 4 : Configurer le fichier .env

Ouvrez le fichier `.env` et remplacez la section MAIL par :

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=VOTRE_CLE_API_SENDGRID
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@votredomaine.com"
MAIL_FROM_NAME="SmartStock"
```

**Important :**
- `MAIL_USERNAME` doit TOUJOURS être `apikey` (c'est le username SendGrid)
- `MAIL_PASSWORD` = votre clé API complète (SG.xxx...)
- `MAIL_FROM_ADDRESS` = l'email que vous avez vérifié à l'étape 3

---

### Étape 5 : Tester la configuration

1. Démarrez le queue worker :
   ```bash
   php artisan queue:work
   ```

2. Dans un autre terminal, testez l'envoi d'un email :
   ```bash
   php artisan email:test user-created 1
   ```
   (Remplacez `1` par l'ID d'un utilisateur existant)

3. Vérifiez que l'email est bien arrivé dans la boîte email de l'utilisateur

4. Si ça ne marche pas, consultez les logs :
   ```bash
   tail -f storage/logs/laravel.log
   ```

---

## 🎯 Exemple de configuration .env complète

```env
# Configuration SendGrid
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=SG.xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@smartstock.com"
MAIL_FROM_NAME="SmartStock"
```

---

## 📊 Suivre vos emails sur SendGrid

1. Allez sur le [Dashboard SendGrid](https://app.sendgrid.com/)

2. Consultez **Activity** pour voir :
   - Emails envoyés
   - Emails livrés
   - Emails ouverts
   - Clics sur les liens
   - Bounces et erreurs

3. Consultez **Statistics** pour des graphiques détaillés

---

## ❌ Dépannage

### Erreur : "Failed to authenticate on SMTP server"

**Solution :**
- Vérifiez que `MAIL_USERNAME=apikey` (exactement)
- Vérifiez que la clé API est correcte et complète
- Vérifiez qu'il n'y a pas d'espaces avant/après la clé

### Erreur : "The from address does not match a verified Sender Identity"

**Solution :**
- Allez dans SendGrid > Settings > Sender Authentication
- Vérifiez que l'email dans `MAIL_FROM_ADDRESS` correspond à un sender vérifié
- Ou vérifiez un nouveau sender avec cet email

### Les emails arrivent en spam

**Solution :**
- Configurez l'authentification du domaine (SPF, DKIM, DMARC)
- Utilisez un domaine professionnel (pas Gmail, Yahoo, etc.)
- Évitez les mots comme "test", "gratuit" dans les sujets
- Assurez-vous que vos emails ont un bon ratio texte/HTML

### Les emails n'arrivent pas

**Solution :**
1. Vérifiez les logs SendGrid (Activity)
2. Vérifiez que le queue worker tourne
3. Consultez les jobs échoués : `php artisan queue:failed`
4. Vérifiez les logs Laravel : `tail -f storage/logs/laravel.log`

---

## 💡 Conseils

1. **Limite gratuite** : 100 emails/jour
   - Si vous dépassez, créez plusieurs comptes ou passez au plan payant

2. **Monitoring** :
   - Surveillez régulièrement le dashboard SendGrid
   - Activez les webhooks pour recevoir des notifications

3. **Production** :
   - Utilisez un domaine dédié pour les emails
   - Configurez SPF, DKIM et DMARC
   - Testez toujours dans un environnement de test avant la production

4. **Sécurité** :
   - Ne committez JAMAIS votre clé API sur Git
   - Ajoutez `.env` dans `.gitignore`
   - Régénérez la clé si elle est compromise

---

## 🔄 Après configuration

Une fois configuré, tous ces événements enverront automatiquement des emails :

✅ Création d'utilisateur → Email de bienvenue + identifiants
✅ Réinitialisation de mot de passe → Nouveau mot de passe
✅ Activation/Désactivation de compte → Notification
✅ Invitation 2FA (pour managers et super admins)

---

## 📞 Support

- [Documentation SendGrid](https://docs.sendgrid.com/)
- [Support SendGrid](https://support.sendgrid.com/)
- [Status SendGrid](https://status.sendgrid.com/)

---

**🎉 C'est tout ! Votre application peut maintenant envoyer de vrais emails professionnels !**
