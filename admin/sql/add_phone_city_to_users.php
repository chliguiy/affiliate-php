<?php
require_once '../../config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Vérifier si la colonne phone existe déjà
    $stmt = $conn->query("SHOW COLUMNS FROM users LIKE 'phone'");
    $phone_exists = $stmt->fetch();

    // Vérifier si la colonne city existe déjà
    $stmt = $conn->query("SHOW COLUMNS FROM users LIKE 'city'");
    $city_exists = $stmt->fetch();

    if (!$phone_exists) {
        $conn->exec("ALTER TABLE users ADD COLUMN phone VARCHAR(30) DEFAULT NULL");
        echo "Colonne 'phone' ajoutée.<br>";
    } else {
        echo "Colonne 'phone' existe déjà.<br>";
    }

    if (!$city_exists) {
        $conn->exec("ALTER TABLE users ADD COLUMN city VARCHAR(100) DEFAULT NULL");
        echo "Colonne 'city' ajoutée.<br>";
    } else {
        echo "Colonne 'city' existe déjà.<br>";
    }

    echo "✅ Structure de la table users à jour.";

} catch(PDOException $e) {
    echo "Erreur : " . $e->getMessage();
} 