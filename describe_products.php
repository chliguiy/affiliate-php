<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();

    $stmt = $conn->query("DESCRIBE products");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Structure de la table products:\n";
    foreach ($columns as $col) {
        echo "- " . $col['Field'] . " (" . $col['Type'] . ")\n";
    }
} catch (PDOException $e) {
    echo "Erreur: " . $e->getMessage();
}
?> 