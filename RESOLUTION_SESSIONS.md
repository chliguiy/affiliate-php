# 🔧 Résolution des Warnings de Session

## ❌ **Problème**
```
Warning: ini_set(): Session ini settings cannot be changed when a session is active in C:\xampp\htdocs\adnane1\config\app.php on line 27
Warning: ini_set(): Session ini settings cannot be changed when a session is active in C:\xampp\htdocs\adnane1\config\app.php on line 28
```

## ✅ **Solution Rapide**

### **Option 1 : Mise à jour automatique (Recommandée)**

1. **Ouvrez votre navigateur**
2. **Allez sur** : `http://localhost/adnane1/update_sessions.php`
3. **Attendez** que le script termine
4. **C'est tout !** ✅

### **Option 2 : Correction manuelle**

Si l'option automatique ne fonctionne pas, corrigez manuellement :

1. **Ouvrez** `config/app.php`
2. **Remplacez** la fonction `configureSession()` par :

```php
public static function configureSession() {
    // Configuration de sécurité des sessions AVANT de démarrer la session
    if (session_status() === PHP_SESSION_NONE) {
        // Définir les paramètres de session avant session_start()
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
        
        // Démarrer la session
        session_start();
    }
}
```

3. **Commentez** la ligne `AppConfig::configureSession();` à la fin du fichier

### **Option 3 : Utilisation du nouveau système**

1. **Remplacez** tous les `session_start();` par :
   ```php
   require_once 'config/session.php';
   ```

2. **Utilisez** les nouvelles fonctions :
   ```php
   // Vérifier si connecté
   if (isLoggedIn()) {
       // Utilisateur connecté
   }
   
   // Vérifier si admin
   if (isAdmin()) {
       // Administrateur
   }
   
   // Rediriger si non connecté
   requireLogin();
   ```

## 🔍 **Pourquoi ce problème ?**

Le problème survient quand :
- `session_start()` est appelé **avant** les paramètres `ini_set()`
- Plusieurs fichiers démarrent des sessions **en même temps**
- Les paramètres de session sont définis **après** le démarrage

## 🛡️ **Nouveau système sécurisé**

Le nouveau système `config/session.php` :
- ✅ Configure les sessions **avant** de les démarrer
- ✅ Évite les **conflits** entre fichiers
- ✅ Ajoute des **fonctions utiles** (isLoggedIn, isAdmin, etc.)
- ✅ Renforce la **sécurité** (httponly, secure cookies)

## 🧪 **Test de la solution**

Après la correction, testez :

1. **Page d'accueil** : `http://localhost/adnane1/`
2. **Connexion** : `http://localhost/adnane1/login.php`
3. **Dashboard** : `http://localhost/adnane1/dashboard.php`

**Les warnings devraient avoir disparu !** 🎉

## 📞 **Si le problème persiste**

1. **Vérifiez** que `config/session.php` existe
2. **Redémarrez** XAMPP
3. **Videz** le cache du navigateur
4. **Vérifiez** les logs d'erreur PHP

---

**🎯 Résultat : Sessions sécurisées sans warnings !** 

# Guide de Résolution des Problèmes - Système de Commandes

## Problème : Les commandes ne s'exécutent pas

### 1. Diagnostic Rapide

#### Étape 1 : Tester la connexion
Accédez à `test_simple_order.php` dans votre navigateur pour un diagnostic automatique.

#### Étape 2 : Vérifier les logs
Consultez les logs d'erreur PHP pour identifier les problèmes spécifiques.

### 2. Problèmes Courants et Solutions

#### A. Erreur de connexion à la base de données
**Symptômes :** Message "Erreur de connexion"
**Solution :**
1. Vérifier `config/database.php`
2. S'assurer que MySQL/MariaDB est démarré
3. Vérifier les identifiants de connexion

#### B. Tables manquantes
**Symptômes :** Erreur "Table doesn't exist"
**Solution :**
```sql
-- Vérifier les tables nécessaires
SHOW TABLES;

-- Créer les tables manquantes si nécessaire
-- Voir les fichiers SQL dans admin/sql/
```

#### C. Produits non trouvés
**Symptômes :** "Aucun produit valide sélectionné"
**Solution :**
```sql
-- Vérifier les produits actifs
SELECT id, name, price, commission_rate FROM products WHERE status = 'active';

-- Activer des produits si nécessaire
UPDATE products SET status = 'active' WHERE id = 1;
```

#### D. Affiliés non trouvés
**Symptômes :** "Affilié invalide ou inactif"
**Solution :**
```sql
-- Vérifier les affiliés actifs
SELECT id, username, email FROM users WHERE type = 'affiliate' AND status = 'active';

-- Créer un affilié de test si nécessaire
INSERT INTO users (username, email, password, type, status, created_at) 
VALUES ('test_affiliate', 'test@affiliate.com', 'password123', 'affiliate', 'active', NOW());
```

### 3. Tests de Diagnostic

#### Test Complet
Accédez à `test_order_process.php` pour un diagnostic complet du système.

#### Test Simple
Accédez à `test_simple_order.php` pour un test rapide de création de commande.

### 4. Structure des Tables Requises

#### Table `orders`
```sql
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    affiliate_id INT,
    customer_name VARCHAR(255),
    customer_email VARCHAR(255),
    customer_phone VARCHAR(50),
    customer_address TEXT,
    customer_city VARCHAR(100),
    order_number VARCHAR(50) UNIQUE,
    total_amount DECIMAL(10,2),
    commission_amount DECIMAL(10,2),
    status ENUM('pending', 'confirmed', 'delivered', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    confirmed_at TIMESTAMP NULL,
    delivered_at TIMESTAMP NULL
);
```

#### Table `order_items`
```sql
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    product_id INT,
    quantity INT,
    price DECIMAL(10,2),
    commission_rate DECIMAL(5,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);
```

#### Table `users`
```sql
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100),
    email VARCHAR(255) UNIQUE,
    password VARCHAR(255),
    phone VARCHAR(50),
    city VARCHAR(100),
    type ENUM('admin', 'affiliate', 'confirmateur', 'customer') DEFAULT 'customer',
    status ENUM('active', 'inactive', 'pending') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### 5. Configuration PHP Requise

#### Extensions nécessaires
- PDO
- PDO_MySQL
- JSON
- Session

#### Vérification
```php
<?php
echo "PDO: " . (extension_loaded('pdo') ? 'OK' : 'MANQUANT') . "\n";
echo "PDO_MySQL: " . (extension_loaded('pdo_mysql') ? 'OK' : 'MANQUANT') . "\n";
echo "JSON: " . (extension_loaded('json') ? 'OK' : 'MANQUANT') . "\n";
echo "Session: " . (extension_loaded('session') ? 'OK' : 'MANQUANT') . "\n";
?>
```

### 6. Processus de Commande

#### Flux normal
1. Client sélectionne des produits
2. Remplit le formulaire de commande
3. `process_order.php` traite la commande
4. `createOrderViaAffiliate()` crée la commande
5. Redirection vers `order_confirmation.php`

#### Points de défaillance
- Validation des données
- Connexion à la base de données
- Création du client
- Insertion de la commande
- Ajout des produits
- Création des commissions

### 7. Debug et Logs

#### Activation des logs
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
```

#### Logs personnalisés
Le système utilise `error_log()` pour tracer les étapes importantes.

### 8. Solutions d'Urgence

#### Si rien ne fonctionne
1. Vérifier que XAMPP est démarré
2. Vérifier les permissions des fichiers
3. Redémarrer le serveur web
4. Vérifier la configuration PHP

#### Créer une commande manuelle
```sql
-- Insérer une commande de test directement
INSERT INTO orders (user_id, affiliate_id, customer_name, customer_email, customer_phone, customer_address, customer_city, order_number, total_amount, commission_amount, status) 
VALUES (1, 1, 'Test Client', 'test@example.com', '0612345678', '123 Rue Test', 'Casablanca', 'ORD-TEST-001', 100.00, 10.00, 'pending');
```

### 9. Contact et Support

En cas de problème persistant :
1. Consultez les logs d'erreur
2. Utilisez les fichiers de test
3. Vérifiez la configuration de la base de données
4. Testez avec des données minimales

### 10. Maintenance Préventive

#### Vérifications régulières
- Intégrité de la base de données
- Espace disque disponible
- Logs d'erreur
- Performance des requêtes

#### Sauvegardes
- Sauvegarder régulièrement la base de données
- Sauvegarder les fichiers de configuration
- Tester les sauvegardes 