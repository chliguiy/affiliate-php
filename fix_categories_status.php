<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    $conn->exec("ALTER TABLE categories ADD COLUMN status ENUM('active', 'inactive') DEFAULT 'active'");
    echo "Colonne 'status' ajoutée à la table categories.";
} catch(PDOException $e) {
    echo "Erreur : " . $e->getMessage();
}
?> 