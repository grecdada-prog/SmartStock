# Documentation du Système d'Emails - SmartStock

## Vue d'ensemble

SmartStock dispose d'un système complet d'envoi d'emails automatiques pour informer les utilisateurs des actions importantes effectuées sur leur compte.

## Notifications disponibles

### 1. UserCreatedNotification
**Quand ?** Envoyé lors de la création d'un nouveau compte utilisateur.

**Contenu :**
- Message de bienvenue personnalisé
- Informations de connexion (email + mot de passe temporaire)
- Recommandation de changer le mot de passe
- Lien vers la page de modification du profil
- Nom de l'administrateur qui a créé le compte

**Rôles concernés :** Tous (super_admin, manager, seller)

**Bonus :** Les Super Admins et Managers reçoivent également une invitation à activer le 2FA.

---

### 2. Enable2FANotification
**Quand ?** Envoyé aux nouveaux Super Admins et Managers pour encourager l'activation du 2FA.

**Contenu :**
- Explication des avantages de l'authentification à deux facteurs
- Lien vers la page de configuration du 2FA
- Instructions simples d'activation

**Rôles concernés :** super_admin, manager

---

### 3. PasswordResetNotification
**Quand ?** Envoyé lorsqu'un administrateur réinitialise le mot de passe d'un utilisateur.

**Contenu :**
- Nouveau mot de passe temporaire
- Recommandation de changer le mot de passe immédiatement
- Lien vers la page de connexion
- Avertissement de sécurité

**Rôles concernés :** Tous

---

### 4. AccountStatusChangedNotification
**Quand ?** Envoyé lorsqu'un compte est activé ou désactivé.

**Contenu :**
- Notification du changement de statut (activé/désactivé)
- Si activé : Lien vers la connexion
- Si désactivé : Information sur l'impossibilité d'accéder au compte
- Nom de l'administrateur qui a effectué le changement

**Rôles concernés :** Tous

---

## Configuration de l'envoi d'emails

### Mode développement (par défaut)

Les emails sont enregistrés dans les logs Laravel au lieu d'être envoyés réellement.

```env
MAIL_MAILER=log
```

Les emails seront visibles dans : `storage/logs/laravel.log`

### Configuration SMTP (Production)

Pour envoyer de vrais emails, modifiez le fichier `.env` :

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=votre-email@gmail.com
MAIL_PASSWORD=votre-mot-de-passe-app
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@smartstock.com"
MAIL_FROM_NAME="SmartStock"
```

### Autres options disponibles

#### SendGrid
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=votre-api-key-sendgrid
MAIL_ENCRYPTION=tls
```

#### Mailgun
```env
MAIL_MAILER=mailgun
MAILGUN_DOMAIN=votre-domaine.mailgun.org
MAILGUN_SECRET=votre-api-key
```

---

## Système de Queue

Les notifications sont envoyées via le système de queue de Laravel pour améliorer les performances.

### Démarrer le worker de queue

Pour que les emails soient envoyés, démarrez le worker :

```bash
php artisan queue:work
```

En production, utilisez un superviseur comme Supervisor pour maintenir le worker actif :

```bash
php artisan queue:work --tries=3 --timeout=90
```

### Vérifier la queue

Pour voir les jobs en attente :

```bash
php artisan queue:monitor
```

Pour lister les jobs échoués :

```bash
php artisan queue:failed
```

Pour relancer les jobs échoués :

```bash
php artisan queue:retry all
```

---

## Tests

### 1. Tester en mode LOG (recommandé pour développement)

1. Assurez-vous que `MAIL_MAILER=log` dans `.env`
2. Démarrez le worker : `php artisan queue:work`
3. Créez un nouvel utilisateur depuis l'interface
4. Consultez les logs : `tail -f storage/logs/laravel.log`
5. Vous verrez l'email complet dans les logs

### 2. Tester avec Mailtrap (service de test d'emails)

1. Créez un compte sur [Mailtrap.io](https://mailtrap.io) (gratuit)
2. Récupérez vos identifiants SMTP
3. Modifiez `.env` :

```env
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=votre-username-mailtrap
MAIL_PASSWORD=votre-password-mailtrap
MAIL_ENCRYPTION=tls
```

4. Démarrez le worker : `php artisan queue:work`
5. Créez un utilisateur
6. Consultez votre boîte Mailtrap pour voir l'email

### 3. Tester en production

1. Configurez votre service SMTP (Gmail, SendGrid, etc.)
2. Démarrez le worker
3. Créez un utilisateur de test
4. Vérifiez la réception de l'email

---

## Actions déclenchant des emails

### Création d'utilisateurs

**Pages concernées :**
- `/superadmin/users/create` → Tous les rôles
- `/superadmin/managers/create` → Managers uniquement
- `/superadmin/sellers/create` → Sellers uniquement

**Emails envoyés :**
- UserCreatedNotification (toujours)
- Enable2FANotification (seulement pour super_admin et manager)

### Réinitialisation de mot de passe

**Page concernée :**
- `/superadmin/users` → Bouton "Réinitialiser" sur chaque utilisateur

**Email envoyé :**
- PasswordResetNotification

### Changement de statut

**Page concernée :**
- `/superadmin/users` → Bouton "Activer/Désactiver"
- `/superadmin/managers` → Bouton "Activer/Désactiver"
- `/superadmin/sellers` → Bouton "Activer/Désactiver"

**Email envoyé :**
- AccountStatusChangedNotification

---

## Personnalisation des emails

### Modifier le contenu d'un email

Les fichiers de notification se trouvent dans `app/Notifications/`.

Exemple pour modifier UserCreatedNotification :

```php
// app/Notifications/UserCreatedNotification.php

public function toMail($notifiable): MailMessage
{
    return (new MailMessage)
        ->subject('Votre nouveau sujet')
        ->line('Votre nouveau message')
        ->action('Votre bouton', url('/votre-lien'));
}
```

### Personnaliser le design des emails

Laravel utilise des templates par défaut. Pour les personnaliser :

```bash
php artisan vendor:publish --tag=laravel-mail
```

Les templates seront copiés dans `resources/views/vendor/mail/`.

---

## Dépannage

### Les emails ne sont pas envoyés

1. **Vérifiez que le worker tourne :**
   ```bash
   php artisan queue:work
   ```

2. **Consultez la table `jobs` :**
   ```sql
   SELECT * FROM jobs;
   ```

3. **Consultez les jobs échoués :**
   ```bash
   php artisan queue:failed
   ```

### Les emails arrivent dans les spams

1. Utilisez un service SMTP professionnel (SendGrid, Mailgun)
2. Configurez SPF, DKIM et DMARC pour votre domaine
3. Utilisez une adresse email vérifiée

### Erreurs d'authentification SMTP

1. Vérifiez vos identifiants dans `.env`
2. Pour Gmail, activez l'accès aux applications moins sécurisées ou utilisez un mot de passe d'application
3. Vérifiez que le port et l'encryption sont corrects

---

## Bonnes pratiques

1. **En développement :** Utilisez `MAIL_MAILER=log` ou Mailtrap
2. **En production :** Utilisez un service SMTP professionnel
3. **Toujours** : Démarrez le queue worker avec un superviseur
4. **Monitoring :** Surveillez la queue pour détecter les jobs échoués
5. **Tests :** Testez tous les scénarios d'emails avant la mise en production

---

## Support

Pour toute question ou problème, consultez :
- [Documentation Laravel Mail](https://laravel.com/docs/mail)
- [Documentation Laravel Notifications](https://laravel.com/docs/notifications)
- [Documentation Laravel Queues](https://laravel.com/docs/queues)
