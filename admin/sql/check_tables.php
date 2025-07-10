<?php
require_once '../../config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Vérifier la structure de la table products
    echo "Structure de la table products :\n";
    $stmt = $conn->query("DESCRIBE products");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
        echo "\n";
    }

    // Vérifier les index de la table products
    echo "\nIndex de la table products :\n";
    $stmt = $conn->query("SHOW INDEX FROM products");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
        echo "\n";
    }

} catch(PDOException $e) {
    echo "Erreur : " . $e->getMessage();
}
?> 