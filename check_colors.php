<?php
require_once 'config/database.php';

try {
    // Vérifier si la table colors existe
    $result = $conn->query("SHOW TABLES LIKE 'colors'");
    if ($result->rowCount() == 0) {
        echo "La table colors n'existe pas. Création de la table...\n";
        
        // Créer la table colors
        $sql = "CREATE TABLE colors (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL,
            color_code VARCHAR(7) NOT NULL,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        $conn->exec($sql);
        echo "Table colors créée avec succès.\n";
    } else {
        echo "La table colors existe.\n";
        
        // Afficher la structure de la table
        $result = $conn->query("DESCRIBE colors");
        echo "\nStructure de la table colors :\n";
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            echo $row['Field'] . " - " . $row['Type'] . "\n";
        }
    }
} catch(PDOException $e) {
    echo "Erreur : " . $e->getMessage() . "\n";
}
?> 