<?php
$host = "localhost";
$username = "root";
$password = "";

try {
    // Connexion au serveur MySQL
    $conn = new PDO("mysql:host=$host", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Supprimer la base de données si elle existe
    $conn->exec("DROP DATABASE IF EXISTS chic_affiliate");
    echo "✅ Ancienne base de données supprimée.<br>";
    
    // Créer une nouvelle base de données
    $conn->exec("CREATE DATABASE chic_affiliate CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✅ Nouvelle base de données créée.<br>";
    
    // Sélectionner la base de données
    $conn->exec("USE chic_affiliate");
    
    // Créer la table admins
    $sql = "CREATE TABLE admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $conn->exec($sql);
    echo "✅ Table admins créée.<br>";
    
    // Créer le compte administrateur
    $sql = "INSERT INTO admins (name, email, password) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        'Administrateur',
        'admin@chicaffiliate.com',
        password_hash('admin123', PASSWORD_DEFAULT)
    ]);
    echo "✅ Compte administrateur créé.<br>";
    
    // Créer la table categories
    $sql = "CREATE TABLE categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        slug VARCHAR(100) NOT NULL UNIQUE,
        description TEXT,
        image_url VARCHAR(255),
        parent_id INT DEFAULT NULL,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $conn->exec($sql);
    echo "✅ Table categories créée.<br>";
    
    // Créer la table products
    $sql = "CREATE TABLE products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NOT NULL UNIQUE,
        description TEXT,
        price DECIMAL(10,2) NOT NULL,
        commission_rate DECIMAL(5,2) NOT NULL,
        category_id INT,
        image_url VARCHAR(255),
        status ENUM('active', 'inactive') DEFAULT 'active',
        stock INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $conn->exec($sql);
    echo "✅ Table products créée.<br>";
    
    // Créer la table orders
    $sql = "CREATE TABLE orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_number VARCHAR(50) NOT NULL UNIQUE,
        customer_name VARCHAR(100) NOT NULL,
        customer_email VARCHAR(255) NOT NULL,
        customer_phone VARCHAR(20),
        customer_address TEXT NOT NULL,
        total_amount DECIMAL(10,2) NOT NULL,
        total_commission DECIMAL(10,2) NOT NULL,
        affiliate_id INT,
        status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
        payment_status ENUM('pending', 'paid', 'refunded') DEFAULT 'pending',
        payment_method VARCHAR(50),
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $conn->exec($sql);
    echo "✅ Table orders créée.<br>";
    
    // Créer la table order_items
    $sql = "CREATE TABLE order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        commission_rate DECIMAL(5,2) NOT NULL,
        commission_amount DECIMAL(10,2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $conn->exec($sql);
    echo "✅ Table order_items créée.<br>";
    
    echo "<br>✅ Base de données réinitialisée avec succès !<br><br>";
    echo "Vous pouvez maintenant vous connecter avec :<br>";
    echo "Email : admin@chicaffiliate.com<br>";
    echo "Mot de passe : admin123<br><br>";
    echo "<a href='../index.php'>Aller à la page de connexion</a>";
    
} catch(PDOException $e) {
    echo "❌ Erreur : " . $e->getMessage() . "<br>";
    echo "Ligne : " . $e->getLine() . "<br>";
}
?> 