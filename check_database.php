<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Vérifier la base de données actuelle
    $stmt = $conn->query("SELECT DATABASE()");
    $current_db = $stmt->fetchColumn();
    echo "Base de données actuelle : " . $current_db . "<br>";
    
    // Vérifier la structure de la table categories
    $stmt = $conn->query("SHOW COLUMNS FROM categories");
    echo "<br>Structure de la table categories :<br>";
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . " - " . $row['Type'] . "<br>";
    }
    
} catch(PDOException $e) {
    echo "Erreur : " . $e->getMessage();
}
?> 