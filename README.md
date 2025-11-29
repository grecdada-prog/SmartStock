# 📦 SmartStock - Système de Gestion de Stock Intelligent

SmartStock est une application Laravel moderne de gestion de stock multi-utilisateurs avec des rôles (SuperAdmin, Manager, Vendeur).

## 🚀 Déploiement Rapide

**Pour déployer sur Render en 10 minutes** → Voir [DEPLOYMENT.md](DEPLOYMENT.md)

## ✨ Fonctionnalités

### Module SuperAdmin
- ✅ Gestion complète des utilisateurs (Managers, Vendeurs)
- ✅ Gestion des rôles et permissions (Spatie)
- ✅ Tableau de bord avec statistiques en temps réel
- ✅ Exports Excel/PDF (utilisateurs, ventes, logs)
- ✅ Journaux d'activité (audit trail)
- ✅ Gestion des sessions actives
- ✅ Notifications email automatiques

### Module Manager
- ✅ Gestion des vendeurs (scope par created_by)
- ✅ Gestion des produits (CRUD complet)
- ✅ Gestion des catégories
- ✅ Gestion du stock (réapprovisionnement, mouvements)
- ✅ Vue des ventes de ses vendeurs
- ✅ Auto-refresh toutes les 5 secondes
- ✅ Alertes stock faible/rupture
- ✅ Indicateurs visuels en temps réel

### Sécurité
- ✅ Rate limiting sur routes critiques
- ✅ CSRF Protection
- ✅ Strong Password Policies
- ✅ 2FA Ready (hooks)
- ✅ Activity Logging complet
- ✅ Session Management
- ✅ Authorization scopes

## 🛠️ Stack Technique

- **Backend**: Laravel 12.40.2
- **Frontend**: Blade + Alpine.js + Tailwind CSS
- **Base de données**: MySQL (local) / PostgreSQL (production)
- **Email**: SendGrid
- **Exports**: Maatwebsite/Excel, Barryvdh/DomPDF
- **Permissions**: Spatie Laravel Permission
- **CI/CD**: GitHub Actions
- **Hosting**: Render (ou autre plateforme)

## 📋 Installation Locale

### Prérequis
- PHP 8.2+
- Composer
- MySQL 8.0+
- Node.js 20+

### Étapes

```bash
# 1. Cloner le repository
git clone https://github.com/votre-username/SmartStock.git
cd SmartStock

# 2. Installer les dépendances PHP
composer install

# 3. Installer les dépendances NPM
npm install

# 4. Copier le fichier .env
cp .env.example .env

# 5. Générer la clé d'application
php artisan key:generate

# 6. Configurer la base de données dans .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=smartstock
DB_USERNAME=root
DB_PASSWORD=

# 7. Créer la base de données
mysql -u root -p -e "CREATE DATABASE smartstock;"

# 8. Exécuter les migrations
php artisan migrate

# 9. Seed les rôles et permissions
php artisan db:seed --class=RolesAndPermissionsSeeder

# 10. Créer le lien symbolique pour le stockage
php artisan storage:link

# 11. Compiler les assets
npm run dev

# 12. Lancer le serveur
php artisan serve
```

Accéder à l'application : [http://localhost:8000](http://localhost:8000)

## 👥 Credentials par Défaut

Voir votre seeder `RolesAndPermissionsSeeder.php` pour les credentials initiaux.

## 📚 Documentation

- [Guide de Déploiement](DEPLOYMENT.md) - Déploiement sur Render avec CI/CD
- [Architecture](docs/ARCHITECTURE.md) - (À créer) Structure du projet
- [API](docs/API.md) - (À créer) Documentation API si applicable

## 🧪 Tests

```bash
# Exécuter tous les tests
php artisan test

# Avec coverage
php artisan test --coverage

# Tests spécifiques
php artisan test --filter=ManagerSellerControllerTest
```

## 🔄 Workflow de Développement

```bash
# 1. Créer une branche
git checkout -b feature/ma-fonctionnalite

# 2. Développer et tester
php artisan test

# 3. Commit
git add .
git commit -m "feat: Description de la fonctionnalité"

# 4. Push
git push origin feature/ma-fonctionnalite

# 5. Créer une Pull Request sur GitHub

# 6. Après merge dans main, le CI/CD déploie automatiquement
```

## 📊 Structure du Projet

```
SmartStock/
├── app/
│   ├── Http/Controllers/
│   │   ├── SuperAdmin/      # Contrôleurs SuperAdmin
│   │   ├── Manager/          # Contrôleurs Manager
│   │   └── Seller/           # Contrôleurs Vendeur
│   ├── Models/               # Modèles Eloquent
│   ├── Helpers/              # Helpers (PasswordHelper, etc.)
│   ├── Services/             # Services (SessionManager, etc.)
│   └── Exports/              # Classes d'export Excel/PDF
├── resources/
│   └── views/
│       ├── superadmin/       # Vues SuperAdmin
│       ├── manager/          # Vues Manager
│       ├── seller/           # Vues Vendeur
│       └── components/       # Composants Blade réutilisables
├── database/
│   ├── migrations/           # Migrations
│   └── seeders/              # Seeders
├── routes/
│   └── web.php               # Routes de l'application
├── tests/                    # Tests automatisés
├── .github/
│   └── workflows/
│       └── deploy.yml        # Pipeline CI/CD
├── render.yaml               # Configuration Render
├── render-build.sh           # Script de build Render
└── DEPLOYMENT.md             # Guide de déploiement
```

## 🤝 Contribution

Les contributions sont les bienvenues ! Veuillez :
1. Fork le projet
2. Créer une branche (`git checkout -b feature/AmazingFeature`)
3. Commit (`git commit -m 'Add some AmazingFeature'`)
4. Push (`git push origin feature/AmazingFeature`)
5. Ouvrir une Pull Request

## 📝 License

Ce projet est sous licence privée. Tous droits réservés.

## 📞 Support

Pour toute question ou problème :
- Ouvrir une issue sur GitHub
- Contacter l'équipe de développement

---

**Développé avec ❤️ par votre équipe**
