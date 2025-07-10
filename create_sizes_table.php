<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Créer la table sizes
    $sql = "CREATE TABLE IF NOT EXISTS sizes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL UNIQUE,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->exec($sql);
    echo "✅ Table 'sizes' créée avec succès !<br>";
    
    // Insérer quelques tailles par défaut
    $defaultSizes = [
        'XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL',
        '36', '37', '38', '39', '40', '41', '42', '43', '44', '45', '46',
        'One Size', 'Taille Unique'
    ];
    
    $stmt = $conn->prepare("INSERT IGNORE INTO sizes (name) VALUES (?)");
    
    foreach ($defaultSizes as $size) {
        $stmt->execute([$size]);
    }
    
    echo "✅ Tailles par défaut ajoutées !<br>";
    echo "<br>🎉 Vous pouvez maintenant accéder à la page des tailles : <a href='admin/sizes.php'>Gestion des Tailles</a>";
    
} catch(PDOException $e) {
    echo "❌ Erreur : " . $e->getMessage();
}
?> 