# 🚀 CHIC AFFILIATE - Plateforme de Marketing d'Affiliation

## 📋 Description

CHIC AFFILIATE est une plateforme complète de marketing d'affiliation développée en PHP avec MySQL. Elle permet aux affiliés de promouvoir des produits et de gagner des commissions sur les ventes.

## ✨ Fonctionnalités

### 👥 Gestion des Utilisateurs
- Inscription et connexion des affiliés
- Gestion des profils utilisateurs
- Informations bancaires sécurisées
- Système de rôles (affilié, administrateur, vendeur)

### 📦 Gestion des Produits
- Catalogue de produits avec images
- Gestion des catégories et sous-catégories
- Système de stock en temps réel
- Prix et commissions configurables
- Couleurs et tailles pour les produits

### 🛒 Système de Commandes
- Processus de commande complet
- Calcul automatique des commissions
- Suivi des statuts de commande
- Gestion des frais de livraison

### 💰 Gestion Financière
- Calcul automatique des commissions
- Système de paiements
- Historique des transactions
- Rapports financiers

### 📞 Support Client
- Système de réclamations
- Suivi des tickets
- Réponses aux demandes
- Pièces jointes

### 📊 Tableau de Bord
- Statistiques en temps réel
- Graphiques de performance
- Rapports détaillés
- Analyses des ventes

## 🛠️ Installation

### Prérequis
- PHP 7.4 ou supérieur
- MySQL 5.7 ou supérieur
- Serveur web (Apache/Nginx)
- Extensions PHP : PDO, PDO_MySQL, GD, mbstring

### Installation Automatique (Recommandée)

1. **Téléchargez les fichiers** dans votre dossier web
2. **Accédez à l'installateur** : `http://votre-domaine/install.php`
3. **Suivez les instructions** à l'écran
4. **L'installation se fait automatiquement**

### Installation Manuelle

1. **Créez la base de données** :
```sql
CREATE DATABASE chic_affiliate CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. **Importez le fichier SQL** :
```bash
mysql -u root -p chic_affiliate < complete_database.sql
```

3. **Configurez la connexion** dans `config/database.php`

4. **Créez les dossiers** :
```bash
mkdir uploads uploads/products uploads/categories uploads/claims logs
chmod 755 uploads logs
```

## 🔧 Configuration

### Informations de Connexion par Défaut

**Administrateur :**
- Email : `admin@chic-affiliate.com`
- Mot de passe : `password`

**Utilisateurs de Test :**
- Affilié 1 : `affiliate1@example.com` / `password`
- Affilié 2 : `affiliate2@example.com` / `password`
- Affilié 3 : `affiliate3@example.com` / `password`

### Configuration de la Base de Données

Modifiez le fichier `config/database.php` :

```php
private $host = "localhost";
private $db_name = "chic_affiliate";
private $username = "root";
private $password = "";
```

### Configuration des Emails

Pour activer l'envoi d'emails, configurez votre serveur SMTP dans les fichiers concernés.

## 📁 Structure des Fichiers

```
chic-affiliate/
├── admin/                 # Interface d'administration
├── affiliate/            # Interface des affiliés
├── api/                  # API REST
├── assets/               # Ressources statiques (CSS, JS, images)
├── auth/                 # Authentification
├── config/               # Configuration
│   ├── app.php          # Configuration générale
│   └── database.php     # Configuration base de données
├── includes/             # Fichiers inclus
│   ├── functions.php    # Fonctions utilitaires
│   ├── header.php       # En-tête commun
│   ├── footer.php       # Pied de page commun
│   ├── navbar.php       # Navigation
│   └── sidebar.php      # Barre latérale
├── logs/                 # Fichiers de logs
├── orders/               # Gestion des commandes
├── products/             # Gestion des produits
├── uploads/              # Fichiers uploadés
│   ├── products/        # Images des produits
│   ├── categories/      # Images des catégories
│   └── claims/          # Pièces jointes des réclamations
├── vendor/               # Interface des vendeurs
├── complete_database.sql # Base de données complète
├── install.php          # Script d'installation
├── test_database.php    # Script de test
└── index.php            # Page d'accueil
```

## 🚀 Utilisation

### Pour les Administrateurs

1. **Connexion** : `http://votre-domaine/admin/`
2. **Gestion des produits** : Ajoutez vos produits dans le catalogue
3. **Gestion des affiliés** : Approuvez et gérez les comptes affiliés
4. **Suivi des commandes** : Surveillez les ventes et commissions
5. **Rapports** : Consultez les statistiques et analyses

### Pour les Affiliés

1. **Inscription** : Créez votre compte affilié
2. **Profil** : Complétez vos informations bancaires
3. **Catalogue** : Parcourez les produits disponibles
4. **Promotion** : Partagez vos liens d'affiliation
5. **Suivi** : Consultez vos commissions et statistiques

## 🔒 Sécurité

- **Mots de passe hashés** avec `password_hash()`
- **Protection contre les injections SQL** avec PDO
- **Validation des données** côté serveur
- **Sessions sécurisées**
- **Nettoyage des entrées utilisateur**

## 📊 Base de Données

### Tables Principales

- **users** : Utilisateurs (affiliés, clients, vendeurs)
- **admins** : Administrateurs
- **products** : Produits du catalogue
- **categories** : Catégories de produits
- **orders** : Commandes
- **order_items** : Détails des commandes
- **payments** : Paiements
- **transactions** : Transactions financières
- **claims** : Réclamations
- **bank_info** : Informations bancaires

### Vues Statistiques

- **order_stats** : Statistiques des commandes
- **payment_stats** : Statistiques des paiements
- **product_stats** : Statistiques des produits
- **claim_stats** : Statistiques des réclamations

## 🛠️ Maintenance

### Sauvegarde

```bash
# Sauvegarde de la base de données
mysqldump -u root -p chic_affiliate > backup_$(date +%Y%m%d).sql
```

### Logs

Les logs sont stockés dans le dossier `logs/` :
- `app.log` : Logs d'application
- Logs d'erreurs PHP

### Mise à Jour

1. Sauvegardez votre base de données
2. Remplacez les fichiers
3. Exécutez les scripts de migration si nécessaire
4. Testez l'application

## 🐛 Dépannage

### Problèmes Courants

1. **Erreur de connexion à la base de données**
   - Vérifiez les paramètres dans `config/database.php`
   - Assurez-vous que MySQL est démarré

2. **Erreur 500**
   - Vérifiez les logs PHP
   - Vérifiez les permissions des dossiers

3. **Images non affichées**
   - Vérifiez les permissions du dossier `uploads/`
   - Vérifiez les chemins des images

4. **Problèmes d'upload**
   - Vérifiez `upload_max_filesize` dans php.ini
   - Vérifiez `post_max_size` dans php.ini

### Test de l'Installation

Accédez à `http://votre-domaine/test_database.php` pour vérifier que tout fonctionne correctement.

## 📞 Support

Pour toute question ou problème :

1. Consultez ce README
2. Vérifiez les logs d'erreur
3. Testez avec le script `test_database.php`
4. Contactez le support technique

## 📄 Licence

Ce projet est sous licence MIT. Voir le fichier LICENSE pour plus de détails.

## 🤝 Contribution

Les contributions sont les bienvenues ! N'hésitez pas à :

1. Signaler des bugs
2. Proposer des améliorations
3. Soumettre des pull requests

## 📈 Roadmap

- [ ] API REST complète
- [ ] Application mobile
- [ ] Intégration paiements en ligne
- [ ] Système de notifications push
- [ ] Analytics avancées
- [ ] Multi-langues
- [ ] Thèmes personnalisables

---

**CHIC AFFILIATE** - Votre plateforme de marketing d'affiliation professionnelle 🚀 # affiliate-php
