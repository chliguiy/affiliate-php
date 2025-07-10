# 🚀 Installation Rapide - CHIC AFFILIATE

## 📋 Options d'Installation

Vous avez plusieurs options pour installer la base de données CHIC AFFILIATE :

### 🎯 **Option 1 : Installation Simple (Recommandée)**

1. **Ouvrez votre navigateur**
2. **Allez sur** : `http://localhost/adnane1/install_simple.php`
3. **Suivez les instructions** à l'écran
4. **C'est tout !** ✅

### 🎯 **Option 2 : Installation via Script Batch (Windows)**

1. **Double-cliquez** sur `install.bat`
2. **Entrez votre mot de passe MySQL** si demandé
3. **Attendez la fin** de l'installation
4. **C'est tout !** ✅

### 🎯 **Option 3 : Installation via phpMyAdmin**

1. **Ouvrez phpMyAdmin** : `http://localhost/phpmyadmin`
2. **Créez une base de données** : `chic_affiliate`
3. **Cliquez sur "Importer"**
4. **Sélectionnez le fichier** : `complete_database.sql`
5. **Cliquez sur "Exécuter"**
6. **C'est tout !** ✅

### 🎯 **Option 4 : Installation via Ligne de Commande**

```bash
# 1. Créer la base de données
mysql -u root -p -e "CREATE DATABASE chic_affiliate CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 2. Importer le fichier SQL
mysql -u root -p chic_affiliate < complete_database.sql
```

## 🔧 **Résolution des Problèmes**

### ❌ **Erreur : "Cannot execute queries while other unbuffered queries are active"**

**Solution :** Utilisez l'**Option 1** (`install_simple.php`) ou l'**Option 2** (`install.bat`)

### ❌ **Erreur : "MySQL n'est pas accessible"**

**Solutions :**
1. Vérifiez que XAMPP est démarré
2. Vérifiez que MySQL est démarré dans XAMPP
3. Redémarrez XAMPP si nécessaire

### ❌ **Erreur : "Fichier SQL non trouvé"**

**Solution :** Vérifiez que le fichier `complete_database.sql` est dans le même dossier

### ❌ **Erreur : "Accès refusé"**

**Solutions :**
1. Vérifiez que l'utilisateur MySQL a les droits suffisants
2. Essayez avec l'utilisateur `root` sans mot de passe
3. Vérifiez la configuration MySQL

## ✅ **Vérification de l'Installation**

Après l'installation, testez avec :
- **Test automatique** : `http://localhost/adnane1/test_database.php`
- **Page d'accueil** : `http://localhost/adnane1/`

## 🔑 **Informations de Connexion**

### **Administrateur**
- **Email** : `admin@chic-affiliate.com`
- **Mot de passe** : `password`

### **Affiliés de Test**
- **Affilié 1** : `affiliate1@example.com` / `password`
- **Affilié 2** : `affiliate2@example.com` / `password`
- **Affilié 3** : `affiliate3@example.com` / `password`

## 📊 **Ce qui est Installé**

- ✅ **18 tables** avec relations complètes
- ✅ **Données d'exemple** (1 admin, 3 affiliés, 10 produits)
- ✅ **Système de commissions** automatique
- ✅ **Gestion des commandes** complète
- ✅ **Système de paiements** intégré
- ✅ **Support client** avec réclamations
- ✅ **Statistiques** en temps réel
- ✅ **Sécurité** renforcée

## 🚀 **Prochaines Étapes**

1. **Connectez-vous** en tant qu'administrateur
2. **Configurez** vos paramètres
3. **Ajoutez** vos produits
4. **Invitez** vos affiliés
5. **Commencez** à vendre !

## 📞 **Support**

Si vous rencontrez des problèmes :

1. **Consultez** ce guide
2. **Testez** avec `test_database.php`
3. **Vérifiez** les logs d'erreur
4. **Redémarrez** XAMPP si nécessaire

---

**🎉 Votre plateforme CHIC AFFILIATE est prête !** 