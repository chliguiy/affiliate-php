<?php
$host = "localhost";
$username = "root";
$password = "";

try {
    // Connexion au serveur MySQL sans sélectionner de base de données
    $conn = new PDO("mysql:host=$host", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Création de la base de données
    $sql = "CREATE DATABASE IF NOT EXISTS chic_affiliate CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    $conn->exec($sql);
    echo "Base de données 'chic_affiliate' créée avec succès.<br>";

    // Sélection de la base de données
    $conn->exec("USE chic_affiliate");

    // Création de la table admins
    $sql = "CREATE TABLE IF NOT EXISTS admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $conn->exec($sql);
    echo "Table 'admins' créée avec succès.<br>";

    // Création de la table categories
    $sql = "CREATE TABLE IF NOT EXISTS categories (
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
    )";
    $conn->exec($sql);
    echo "Table 'categories' créée avec succès.<br>";

    // Création de la table products
    $sql = "CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NOT NULL,
        description TEXT,
        price DECIMAL(10,2) NOT NULL,
        commission_rate DECIMAL(5,2) NOT NULL,
        category_id INT,
        image_url VARCHAR(255),
        status ENUM('active', 'inactive') DEFAULT 'active',
        stock INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
        UNIQUE KEY unique_slug (slug)
    )";
    $conn->exec($sql);
    echo "Table 'products' créée avec succès.<br>";

    // Création de la table product_sizes
    $sql = "CREATE TABLE IF NOT EXISTS product_sizes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        size VARCHAR(50) NOT NULL,
        stock INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    )";
    $conn->exec($sql);
    echo "Table 'product_sizes' créée avec succès.<br>";

    // Création de la table product_colors
    $sql = "CREATE TABLE IF NOT EXISTS product_colors (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        color_name VARCHAR(50) NOT NULL,
        color_code VARCHAR(7) NOT NULL,
        stock INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    )";
    $conn->exec($sql);
    echo "Table 'product_colors' créée avec succès.<br>";

    // Création de la table product_images
    $sql = "CREATE TABLE IF NOT EXISTS product_images (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        image_url VARCHAR(255) NOT NULL,
        is_primary BOOLEAN DEFAULT FALSE,
        sort_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    )";
    $conn->exec($sql);
    echo "Table 'product_images' créée avec succès.<br>";

    // Création de la table orders
    $sql = "CREATE TABLE IF NOT EXISTS orders (
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
    )";
    $conn->exec($sql);
    echo "Table 'orders' créée avec succès.<br>";

    // Création de la table order_items
    $sql = "CREATE TABLE IF NOT EXISTS order_items (
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
    )";
    $conn->exec($sql);
    echo "Table 'order_items' créée avec succès.<br>";

    // Création de l'administrateur par défaut
    $stmt = $conn->prepare("SELECT id FROM admins WHERE email = ?");
    $stmt->execute(['admin@chicaffiliate.com']);
    
    if (!$stmt->fetch()) {
        $stmt = $conn->prepare("INSERT INTO admins (name, email, password) VALUES (?, ?, ?)");
        $stmt->execute([
            'Administrateur',
            'admin@chicaffiliate.com',
            password_hash('admin123', PASSWORD_DEFAULT)
        ]);
        echo "Compte administrateur par défaut créé avec succès.<br>";
    } else {
        echo "Le compte administrateur existe déjà.<br>";
    }

    echo "<br>Configuration terminée. Vous pouvez maintenant vous connecter à l'administration avec :<br>";
    echo "Email : admin@chicaffiliate.com<br>";
    echo "Mot de passe : admin123<br>";
    echo "<br><a href='../index.php'>Aller à la page de connexion</a>";

} catch(PDOException $e) {
    echo "Erreur : " . $e->getMessage();
}
?> 