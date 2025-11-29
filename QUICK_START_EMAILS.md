# Guide de Démarrage Rapide - Système d'Emails

## 🚀 Démarrage en 3 étapes

### 1. Démarrer le Queue Worker

Le queue worker traite les emails en arrière-plan :

```bash
php artisan queue:work
```

💡 **Astuce :** Laissez cette commande tourner dans un terminal séparé pendant que vous utilisez l'application.

---

### 2. Tester avec la commande de test

Testez rapidement toutes les notifications :

```bash
php artisan email:test all
```

Ou testez une notification spécifique :

```bash
# Test email de bienvenue
php artisan email:test user-created

# Test invitation 2FA
php artisan email:test enable-2fa

# Test réinitialisation mot de passe
php artisan email:test password-reset

# Test activation de compte
php artisan email:test status-activated

# Test désactivation de compte
php artisan email:test status-deactivated
```

---

### 3. Consulter les emails générés

En mode développement (LOG), les emails sont dans les logs :

```bash
tail -f storage/logs/laravel.log
```

Vous verrez le contenu complet des emails en HTML.

---

## 📧 Actions qui déclenchent automatiquement des emails

### Création d'utilisateurs

Connectez-vous en tant que Super Admin et :

1. Allez sur `Utilisateurs` > `Nouveau utilisateur`
2. Remplissez le formulaire
3. Cliquez sur `Créer`

**Résultat :**
- ✉️ Email de bienvenue avec les identifiants
- ✉️ Email d'invitation 2FA (si super_admin ou manager)

### Réinitialisation de mot de passe

1. Allez sur `Utilisateurs`
2. Cliquez sur un utilisateur
3. Cliquez sur `Réinitialiser le mot de passe`

**Résultat :**
- ✉️ Email avec le nouveau mot de passe temporaire

### Activation/Désactivation de compte

1. Allez sur `Utilisateurs`
2. Cliquez sur `Activer` ou `Désactiver`

**Résultat :**
- ✉️ Email de notification du changement de statut

---

## 🔧 Configuration SMTP (Optionnel)

### Pour Gmail

1. Ouvrez `.env`
2. Modifiez :

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=votre-email@gmail.com
MAIL_PASSWORD=votre-mot-de-passe-app
MAIL_ENCRYPTION=tls
```

3. Redémarrez le queue worker

### Pour Mailtrap (Service de test)

1. Créez un compte sur [mailtrap.io](https://mailtrap.io)
2. Modifiez `.env` :

```env
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=votre-username
MAIL_PASSWORD=votre-password
MAIL_ENCRYPTION=tls
```

3. Consultez vos emails sur Mailtrap

---

## ✅ Checklist de vérification

- [ ] Le queue worker est démarré (`php artisan queue:work`)
- [ ] La configuration MAIL est correcte dans `.env`
- [ ] Les notifications sont créées dans `app/Notifications/`
- [ ] Les contrôleurs envoient les notifications
- [ ] Les emails apparaissent dans les logs (mode LOG)

---

## 🆘 Dépannage rapide

### Les emails ne sont pas envoyés

```bash
# Vérifiez les jobs en attente
php artisan queue:monitor

# Vérifiez les jobs échoués
php artisan queue:failed

# Relancez les jobs échoués
php artisan queue:retry all
```

### Le worker plante

Redémarrez-le :

```bash
php artisan queue:restart
php artisan queue:work
```

---

## 📚 Documentation complète

Pour plus de détails, consultez `EMAILS_DOCUMENTATION.md`

---

## 🎉 C'est prêt !

Votre système d'emails est opérationnel. Créez un utilisateur depuis l'interface et observez les emails dans les logs !

**Commande pour observer les logs en temps réel :**

```bash
tail -f storage/logs/laravel.log | grep -A 50 "Subject:"
```
