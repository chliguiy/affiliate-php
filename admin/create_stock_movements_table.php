<?php
$host = "localhost";
$username = "root";
$password = "";
$dbname = "chic_affiliate";

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $sql = "CREATE TABLE IF NOT EXISTS stock_movements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        admin_id INT,
        quantity INT NOT NULL,
        movement_type ENUM('in', 'out') NOT NULL,
        reason VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id),
        FOREIGN KEY (admin_id) REFERENCES admins(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $conn->exec($sql);
    echo "Table stock_movements créée avec succès.";
} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage();
} 