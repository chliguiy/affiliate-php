<?php
require_once '../config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Démarrer la transaction
    $conn->beginTransaction();

    // Vérifier si la table orders existe
    $tableExists = $conn->query("SHOW TABLES LIKE 'orders'")->rowCount() > 0;

    if (!$tableExists) {
        // Créer la table orders si elle n'existe pas
        $conn->exec("
            CREATE TABLE orders (
                id INT AUTO_INCREMENT PRIMARY KEY,
                product_id INT NOT NULL,
                customer_name VARCHAR(255) NOT NULL,
                phone VARCHAR(20) NOT NULL,
                quantity INT NOT NULL,
                city VARCHAR(50) NOT NULL,
                shipping_cost DECIMAL(10,2) NOT NULL,
                total_price DECIMAL(10,2) NOT NULL,
                status ENUM('pending', 'processing', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (product_id) REFERENCES products(id)
            )
        ");
    } else {
        // Mettre à jour la table orders existante
        $conn->exec("
            ALTER TABLE orders
            ADD COLUMN IF NOT EXISTS city VARCHAR(50) NOT NULL AFTER quantity,
            ADD COLUMN IF NOT EXISTS shipping_cost DECIMAL(10,2) NOT NULL AFTER city,
            ADD COLUMN IF NOT EXISTS total_price DECIMAL(10,2) NOT NULL AFTER shipping_cost,
            MODIFY COLUMN status ENUM('pending', 'processing', 'completed', 'cancelled') NOT NULL DEFAULT 'pending'
        ");
    }

    // Valider la transaction
    $conn->commit();
    
    echo "Mise à jour de la table orders réussie !";
} catch (PDOException $e) {
    // Annuler la transaction en cas d'erreur
    $conn->rollBack();
    echo "Erreur lors de la mise à jour : " . $e->getMessage();
} 