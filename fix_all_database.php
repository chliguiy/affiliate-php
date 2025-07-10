<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "<h1>🔧 CORRECTION COMPLÈTE DE LA BASE DE DONNÉES</h1>";
    
    // 1. Corriger la table products
    echo "<h2>📦 Correction de la table products</h2>";
    $alterQueries = [
        "ALTER TABLE products ADD COLUMN IF NOT EXISTS seller_price DECIMAL(10,2) DEFAULT 0.00 AFTER price",
        "ALTER TABLE products ADD COLUMN IF NOT EXISTS reseller_price DECIMAL(10,2) DEFAULT 0.00 AFTER seller_price",
        "ALTER TABLE products ADD COLUMN IF NOT EXISTS has_discount BOOLEAN DEFAULT FALSE AFTER reseller_price",
        "ALTER TABLE products ADD COLUMN IF NOT EXISTS discount_price DECIMAL(10,2) DEFAULT NULL AFTER has_discount"
    ];
    
    foreach ($alterQueries as $query) {
        try {
            $conn->exec($query);
            echo "✅ Colonne products ajoutée<br>";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                echo "ℹ️ Colonne products déjà existante<br>";
            }
        }
    }
    
    // 2. Corriger la table users
    echo "<h2>👥 Correction de la table users</h2>";
    $userQueries = [
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS bank_name VARCHAR(100) DEFAULT NULL AFTER email",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS rib VARCHAR(24) DEFAULT NULL AFTER bank_name",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS phone VARCHAR(20) DEFAULT NULL AFTER rib",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS address TEXT DEFAULT NULL AFTER phone",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS city VARCHAR(100) DEFAULT NULL AFTER address",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS full_name VARCHAR(255) DEFAULT NULL AFTER id",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS type ENUM('affiliate', 'customer', 'vendor', 'admin') DEFAULT 'affiliate' AFTER full_name"
    ];
    
    foreach ($userQueries as $query) {
        try {
            $conn->exec($query);
            echo "✅ Colonne users ajoutée<br>";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                echo "ℹ️ Colonne users déjà existante<br>";
            }
        }
    }
    
    // 3. Créer les tables colors et sizes si elles n'existent pas
    echo "<h2>🎨 Création des tables colors et sizes</h2>";
    
    // Table colors
    $conn->exec("CREATE TABLE IF NOT EXISTS colors (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        color_code VARCHAR(7) NOT NULL,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✅ Table colors créée/vérifiée<br>";
    
    // Table sizes
    $conn->exec("CREATE TABLE IF NOT EXISTS sizes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL UNIQUE,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✅ Table sizes créée/vérifiée<br>";
    
    // 4. Recréer les tables de relations
    echo "<h2>🔗 Correction des tables de relations</h2>";
    
    $conn->exec("DROP TABLE IF EXISTS product_colors");
    $conn->exec("CREATE TABLE product_colors (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        color_id INT NOT NULL,
        stock INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        FOREIGN KEY (color_id) REFERENCES colors(id) ON DELETE CASCADE,
        UNIQUE KEY unique_product_color (product_id, color_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✅ Table product_colors recréée<br>";
    
    $conn->exec("DROP TABLE IF EXISTS product_sizes");
    $conn->exec("CREATE TABLE product_sizes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        size_id INT NOT NULL,
        stock_quantity INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        FOREIGN KEY (size_id) REFERENCES sizes(id) ON DELETE CASCADE,
        UNIQUE KEY unique_product_size (product_id, size_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✅ Table product_sizes recréée<br>";
    
    // 5. Insérer les données par défaut
    echo "<h2>📝 Insertion des données par défaut</h2>";
    
    // Couleurs par défaut
    $defaultColors = [
        ['name' => 'Rouge', 'color_code' => '#FF0000'],
        ['name' => 'Bleu', 'color_code' => '#0000FF'],
        ['name' => 'Vert', 'color_code' => '#00FF00'],
        ['name' => 'Jaune', 'color_code' => '#FFFF00'],
        ['name' => 'Noir', 'color_code' => '#000000'],
        ['name' => 'Blanc', 'color_code' => '#FFFFFF'],
        ['name' => 'Gris', 'color_code' => '#808080'],
        ['name' => 'Orange', 'color_code' => '#FFA500'],
        ['name' => 'Violet', 'color_code' => '#800080'],
        ['name' => 'Rose', 'color_code' => '#FFC0CB']
    ];
    
    $stmt = $conn->prepare("INSERT IGNORE INTO colors (name, color_code) VALUES (?, ?)");
    foreach ($defaultColors as $color) {
        $stmt->execute([$color['name'], $color['color_code']]);
    }
    echo "✅ Couleurs par défaut ajoutées<br>";
    
    // Tailles par défaut
    $defaultSizes = [
        'XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL',
        '36', '37', '38', '39', '40', '41', '42', '43', '44', '45', '46',
        'One Size', 'Taille Unique'
    ];
    
    $stmt = $conn->prepare("INSERT IGNORE INTO sizes (name) VALUES (?)");
    foreach ($defaultSizes as $size) {
        $stmt->execute([$size]);
    }
    echo "✅ Tailles par défaut ajoutées<br>";
    
    // 6. Mettre à jour les données existantes
    echo "<h2>🔄 Mise à jour des données existantes</h2>";
    
    $updateQueries = [
        "UPDATE products SET seller_price = price * 0.7, reseller_price = price WHERE seller_price = 0 AND price > 0",
        "UPDATE users SET full_name = CONCAT(first_name, ' ', last_name) WHERE full_name IS NULL AND first_name IS NOT NULL AND last_name IS NOT NULL",
        "UPDATE users SET type = 'affiliate' WHERE type IS NULL",
        "UPDATE users SET bank_name = 'CIH Bank' WHERE bank_name IS NULL AND type = 'affiliate'"
    ];
    
    foreach ($updateQueries as $query) {
        try {
            $affected = $conn->exec($query);
            echo "✅ $affected enregistrements mis à jour<br>";
        } catch (PDOException $e) {
            echo "ℹ️ " . $e->getMessage() . "<br>";
        }
    }
    
    echo "<br><h1>🎉 CORRECTION TERMINÉE AVEC SUCCÈS !</h1>";
    echo "<p>Toutes les tables et colonnes ont été corrigées. Vous pouvez maintenant :</p>";
    echo "<ul>";
    echo "<li><a href='admin/products.php'>📦 Gérer les produits</a></li>";
    echo "<li><a href='admin/users.php'>👥 Gérer les utilisateurs</a></li>";
    echo "<li><a href='admin/colors.php'>🎨 Gérer les couleurs</a></li>";
    echo "<li><a href='admin/sizes.php'>📏 Gérer les tailles</a></li>";
    echo "<li><a href='register.php'>📝 Inscription</a></li>";
    echo "<li><a href='login.php'>🔐 Connexion</a></li>";
    echo "</ul>";
    
} catch(PDOException $e) {
    echo "❌ Erreur : " . $e->getMessage();
}
?> 