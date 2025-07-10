<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Créer la table colors
    $sql = "CREATE TABLE IF NOT EXISTS colors (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        color_code VARCHAR(7) NOT NULL,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->exec($sql);
    echo "✅ Table 'colors' créée avec succès !<br>";
    
    // Insérer quelques couleurs par défaut
    $defaultColors = [
        ['name' => 'Rouge', 'color_code' => '#FF0000'],
        ['name' => 'Bleu', 'color_code' => '#0000FF'],
        ['name' => 'Vert', 'color_code' => '#00FF00'],
        ['name' => 'Jaune', 'color_code' => '#FFFF00'],
        ['name' => 'Noir', 'color_code' => '#000000'],
        ['name' => 'Blanc', 'color_code' => '#FFFFFF'],
        ['name' => 'Gris', 'color_code' => '#808080'],
        ['name' => 'Orange', 'color_code' => '#FFA500'],
        ['name' => 'Violet', 'color_code' => '#800080'],
        ['name' => 'Rose', 'color_code' => '#FFC0CB']
    ];
    
    $stmt = $conn->prepare("INSERT IGNORE INTO colors (name, color_code) VALUES (?, ?)");
    
    foreach ($defaultColors as $color) {
        $stmt->execute([$color['name'], $color['color_code']]);
    }
    
    echo "✅ Couleurs par défaut ajoutées !<br>";
    echo "<br>🎉 Vous pouvez maintenant accéder à la page des couleurs : <a href='admin/colors.php'>Gestion des Couleurs</a>";
    
} catch(PDOException $e) {
    echo "❌ Erreur : " . $e->getMessage();
}
?> 