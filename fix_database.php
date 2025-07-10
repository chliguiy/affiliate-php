<?php
// Configuration de la base de données
$host = "localhost";
$username = "root";
$password = "";
$database = "chic_affiliate";

try {
    // Connexion à MySQL
    $pdo = new PDO("mysql:host=$host", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
    ]);
    
    echo "1. Connexion à MySQL réussie\n";
    
    // Supprimer la base de données si elle existe
    $pdo->exec("DROP DATABASE IF EXISTS $database");
    echo "2. Base de données supprimée\n";
    
    // Créer la base de données
    $pdo->exec("CREATE DATABASE $database CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "3. Base de données créée\n";
    
    // Sélectionner la base de données
    $pdo->exec("USE $database");
    echo "4. Base de données sélectionnée\n";
    
    // Créer la table users en premier
    $pdo->exec("CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) NOT NULL UNIQUE,
        email VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        full_name VARCHAR(255) NOT NULL,
        phone VARCHAR(20) NOT NULL,
        address TEXT NOT NULL,
        city VARCHAR(100) NOT NULL,
        type ENUM('affiliate', 'customer', 'vendor', 'admin') DEFAULT 'affiliate',
        status ENUM('active', 'inactive', 'pending', 'suspended') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "5. Table users créée\n";
    
    // Créer la table bank_info après la table users
    $pdo->exec("CREATE TABLE bank_info (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        bank_name ENUM('Attijariwafa Bank', 'CIH Bank', 'BMCI', 'Banque Populaire', 'Société Générale Maroc', 'CFG Bank', 'Al Barid Bank') NOT NULL,
        rib VARCHAR(24) NOT NULL,
        account_holder VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_rib (rib)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "6. Table bank_info créée\n";
    
    // Créer la table password_resets
    $pdo->exec("CREATE TABLE password_resets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        token VARCHAR(255) NOT NULL UNIQUE,
        expires_at TIMESTAMP NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "7. Table password_resets créée\n";
    
    // Créer la table categories
    $pdo->exec("CREATE TABLE categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NOT NULL UNIQUE,
        description TEXT,
        image VARCHAR(255),
        parent_id INT DEFAULT NULL,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "8. Table categories créée\n";
    
    // Créer la table products
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
        vendor_id INT,
        image_url VARCHAR(255),
        commission_rate DECIMAL(5,2) DEFAULT 10.00,
        affiliate_visibility ENUM('all', 'specific') DEFAULT 'all',
        status ENUM('active', 'inactive', 'draft', 'out_of_stock') DEFAULT 'active',
        last_stock_update TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
        FOREIGN KEY (vendor_id) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "9. Table products créée\n";
    
    echo "✅ Structure de la base de données corrigée avec succès !\n";
    
} catch (PDOException $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
}
?> 