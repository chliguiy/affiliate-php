<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Création de la table products sans la contrainte de clé étrangère
    $sql = "CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NOT NULL UNIQUE,
        description TEXT,
        seller_price DECIMAL(10,2) NOT NULL,
        reseller_price DECIMAL(10,2) NOT NULL,
        category_id INT,
        image_url VARCHAR(255),
        status ENUM('active', 'inactive') DEFAULT 'active',
        stock INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    $conn->exec($sql);
    echo "Table products créée avec succès.<br>";

    // Ajout de la contrainte de clé étrangère
    $sql = "ALTER TABLE products ADD FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL";
    $conn->exec($sql);
    echo "Contrainte de clé étrangère ajoutée avec succès.";
} catch(PDOException $e) {
    echo "Erreur : " . $e->getMessage();
}
?> 