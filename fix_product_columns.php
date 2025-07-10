<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Vérifier et ajouter la colonne has_discount
    $columnExists = $conn->query("SHOW COLUMNS FROM products LIKE 'has_discount'")->rowCount() > 0;
    if (!$columnExists) {
        $conn->exec("ALTER TABLE products ADD COLUMN has_discount BOOLEAN NOT NULL DEFAULT FALSE");
        echo "Colonne 'has_discount' ajoutée.\n";
    }

    // Vérifier et ajouter la colonne sale_price
    $columnExists = $conn->query("SHOW COLUMNS FROM products LIKE 'sale_price'")->rowCount() > 0;
    if (!$columnExists) {
        $conn->exec("ALTER TABLE products ADD COLUMN sale_price DECIMAL(10,2) NULL DEFAULT NULL AFTER reseller_price");
        echo "Colonne 'sale_price' ajoutée.\n";
    }

    // Afficher la structure de la table products
    $stmt = $conn->query("DESCRIBE products");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "\nStructure actuelle de la table products :\n";
    foreach ($columns as $col) {
        echo "- " . $col['Field'] . " (" . $col['Type'] . ")\n";
    }
} catch (PDOException $e) {
    echo "Erreur lors de la mise à jour de la table products : " . $e->getMessage();
}
?> 