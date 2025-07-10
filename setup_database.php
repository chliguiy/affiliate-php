<?php
$host = "localhost";
$username = "root";
$password = "";

try {
    echo "Tentative de connexion à MySQL...\n";
    
    // Connexion à MySQL sans sélectionner de base de données
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connexion à MySQL réussie.\n";
    
    // Vérification si la base de données existe
    $stmt = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = 'chic_affiliate'");
    $dbExists = $stmt->fetch();
    
    if (!$dbExists) {
        echo "Création de la base de données chic_affiliate...\n";
        $pdo->exec("CREATE DATABASE chic_affiliate CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "Base de données créée avec succès.\n";
    } else {
        echo "La base de données chic_affiliate existe déjà.\n";
    }
    
    // Sélection de la base de données
    echo "Sélection de la base de données chic_affiliate...\n";
    $pdo->exec("USE chic_affiliate");
    echo "Base de données sélectionnée avec succès.\n";

    // Désactiver temporairement les contraintes de clé étrangère
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // Suppression des tables dans l'ordre inverse des dépendances
    echo "Suppression des tables existantes...\n";
    $pdo->exec("DROP TABLE IF EXISTS stock_movements");
    $pdo->exec("DROP TABLE IF EXISTS order_items");
    $pdo->exec("DROP TABLE IF EXISTS orders");
    $pdo->exec("DROP TABLE IF EXISTS transactions");
    $pdo->exec("DROP TABLE IF EXISTS products");
    $pdo->exec("DROP TABLE IF EXISTS categories");
    $pdo->exec("DROP TABLE IF EXISTS users");
    $pdo->exec("DROP TABLE IF EXISTS admins");
    
    // Réactiver les contraintes de clé étrangère
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    // Création de la table admins avec rôles améliorés
    echo "Création de la table admins...\n";
    $pdo->exec("CREATE TABLE admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        full_name VARCHAR(255) NOT NULL,
        role ENUM('super_admin', 'admin', 'stock_manager', 'order_manager') DEFAULT 'admin',
        permissions JSON,
        status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
        last_login TIMESTAMP NULL,
        last_password_change TIMESTAMP NULL,
        password_reset_token VARCHAR(100) NULL,
        password_reset_expires TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Table admins créée avec succès.\n";

    // Création de la table admin_logs pour tracer toutes les actions
    echo "Création de la table admin_logs...\n";
    $pdo->exec("CREATE TABLE admin_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admin_id INT NOT NULL,
        action VARCHAR(255) NOT NULL,
        entity_type VARCHAR(50) NOT NULL,
        entity_id INT,
        details JSON,
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Table admin_logs créée avec succès.\n";

    // Création de la table admin_sessions pour gérer les sessions
    echo "Création de la table admin_sessions...\n";
    $pdo->exec("CREATE TABLE admin_sessions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admin_id INT NOT NULL,
        session_id VARCHAR(255) NOT NULL UNIQUE,
        ip_address VARCHAR(45),
        user_agent TEXT,
        last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Table admin_sessions créée avec succès.\n";

    // Création de la table categories
    echo "Création de la table categories...\n";
    $pdo->exec("CREATE TABLE categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NOT NULL UNIQUE,
        description TEXT,
        parent_id INT DEFAULT NULL,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Table categories créée avec succès.\n";

    // Création de la table products avec gestion de stock améliorée
    echo "Création de la table products...\n";
    $pdo->exec("CREATE TABLE products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NOT NULL UNIQUE,
        description TEXT,
        price DECIMAL(10,2) NOT NULL,
        sale_price DECIMAL(10,2) DEFAULT NULL,
        stock INT NOT NULL DEFAULT 0,
        min_stock_level INT NOT NULL DEFAULT 5,
        max_stock_level INT NOT NULL DEFAULT 100,
        reorder_point INT NOT NULL DEFAULT 10,
        sku VARCHAR(50) UNIQUE,
        barcode VARCHAR(50) UNIQUE,
        weight DECIMAL(10,2) DEFAULT 0.00,
        dimensions VARCHAR(50),
        category_id INT,
        image_url VARCHAR(255),
        commission_rate DECIMAL(5,2) DEFAULT 10.00,
        status ENUM('active', 'inactive', 'draft', 'out_of_stock') DEFAULT 'active',
        last_stock_update TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Table products créée avec succès.\n";

    // Création de la table stock_movements pour suivre l'historique des mouvements de stock
    echo "Création de la table stock_movements...\n";
    $pdo->exec("CREATE TABLE stock_movements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        quantity INT NOT NULL,
        type ENUM('in', 'out', 'adjustment', 'return') NOT NULL,
        reference_type ENUM('order', 'manual', 'return', 'adjustment') NOT NULL,
        reference_id INT,
        notes TEXT,
        created_by INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        FOREIGN KEY (created_by) REFERENCES admins(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Table stock_movements créée avec succès.\n";
    
    // Création de la table users
    echo "Création de la table users...\n";
    $pdo->exec("CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) NOT NULL UNIQUE,
        full_name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        address TEXT NOT NULL,
        bank_name VARCHAR(255) NOT NULL,
        rib VARCHAR(24) NOT NULL,
        password VARCHAR(255) NOT NULL,
        type ENUM('affiliate', 'customer', 'other') DEFAULT 'affiliate',
        status ENUM('active', 'inactive', 'pending') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Table users créée avec succès.\n";
    
    // Création de la table transactions
    echo "Création de la table transactions...\n";
    $pdo->exec("CREATE TABLE transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        type ENUM('commission', 'withdrawal') NOT NULL,
        status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Table transactions créée avec succès.\n";

    // Création de la table orders
    echo "Création de la table orders...\n";
    $pdo->exec("CREATE TABLE orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        affiliate_id INT NOT NULL,
        customer_name VARCHAR(255) NOT NULL,
        customer_email VARCHAR(255) NOT NULL,
        order_number VARCHAR(50) NOT NULL UNIQUE,
        total_amount DECIMAL(10,2) NOT NULL,
        commission_amount DECIMAL(10,2) NOT NULL,
        status ENUM('pending', 'processing', 'completed', 'cancelled') DEFAULT 'pending',
        payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
        shipping_address TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (affiliate_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Table orders créée avec succès.\n";
    
    // Création de la table order_items
    echo "Création de la table order_items...\n";
    $pdo->exec("CREATE TABLE order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        product_id INT NOT NULL,
        product_name VARCHAR(255) NOT NULL,
        quantity INT NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Table order_items créée avec succès.\n";

    // Vérification si l'admin par défaut existe déjà
    $stmt = $pdo->query("SELECT id FROM admins WHERE email = 'admin@chic-affiliate.com'");
    $adminExists = $stmt->fetch();

    if (!$adminExists) {
        echo "Création du compte administrateur par défaut...\n";
        $defaultPassword = password_hash('Admin@123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO admins (username, email, password, full_name, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['admin', 'admin@chic-affiliate.com', $defaultPassword, 'Administrateur Principal', 'super_admin']);
        echo "Compte administrateur créé avec succès.\n";
    } else {
        echo "Le compte administrateur existe déjà.\n";
    }
    
    echo "Configuration de la base de données terminée avec succès !\n";
    
} catch(PDOException $e) {
    die("ERREUR : " . $e->getMessage() . "\n");
}
?> 