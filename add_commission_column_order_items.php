<?php
require_once __DIR__ . '/config/database.php';

$database = new Database();
$conn = $database->getConnection();

try {
    // Vérifier si la colonne existe déjà
    $result = $conn->query("SHOW COLUMNS FROM order_items LIKE 'commission'");
    if ($result->rowCount() === 0) {
        // Ajouter la colonne commission
        $sql = "ALTER TABLE order_items ADD COLUMN commission DECIMAL(10,2) DEFAULT 0";
        $conn->exec($sql);
        echo "Colonne 'commission' ajoutée avec succès à order_items.";
    } else {
        echo "La colonne 'commission' existe déjà dans order_items.";
    }
} catch (PDOException $e) {
    echo "Erreur lors de l'ajout de la colonne : " . $e->getMessage();
}