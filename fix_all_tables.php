<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "<h2>🔧 Création des tables manquantes</h2>";
    
    // 1. Table colors
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
    
    // 2. Table sizes
    $sql = "CREATE TABLE IF NOT EXISTS sizes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL UNIQUE,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->exec($sql);
    echo "✅ Table 'sizes' créée avec succès !<br>";
    
    // 3. Table product_colors (relation many-to-many)
    $sql = "CREATE TABLE IF NOT EXISTS product_colors (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        color_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        FOREIGN KEY (color_id) REFERENCES colors(id) ON DELETE CASCADE,
        UNIQUE KEY unique_product_color (product_id, color_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->exec($sql);
    echo "✅ Table 'product_colors' créée avec succès !<br>";
    
    // 4. Table product_sizes (relation many-to-many)
    $sql = "CREATE TABLE IF NOT EXISTS product_sizes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        size_id INT NOT NULL,
        stock_quantity INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        FOREIGN KEY (size_id) REFERENCES sizes(id) ON DELETE CASCADE,
        UNIQUE KEY unique_product_size (product_id, size_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->exec($sql);
    echo "✅ Table 'product_sizes' créée avec succès !<br>";
    
    // Insérer les couleurs par défaut
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
        ['name' => 'Rose', 'color_code' => '#FFC0CB'],
        ['name' => 'Marron', 'color_code' => '#A52A2A'],
        ['name' => 'Cyan', 'color_code' => '#00FFFF']
    ];
    
    $stmt = $conn->prepare("INSERT IGNORE INTO colors (name, color_code) VALUES (?, ?)");
    foreach ($defaultColors as $color) {
        $stmt->execute([$color['name'], $color['color_code']]);
    }
    echo "✅ Couleurs par défaut ajoutées !<br>";
    
    // Insérer les tailles par défaut
    $defaultSizes = [
        'XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL',
        '36', '37', '38', '39', '40', '41', '42', '43', '44', '45', '46',
        'One Size', 'Taille Unique', 'Taille L', 'Taille M', 'Taille S'
    ];
    
    $stmt = $conn->prepare("INSERT IGNORE INTO sizes (name) VALUES (?)");
    foreach ($defaultSizes as $size) {
        $stmt->execute([$size]);
    }
    echo "✅ Tailles par défaut ajoutées !<br>";
    
    echo "<br><h3>🎉 Toutes les tables ont été créées avec succès !</h3>";
    echo "<p>Vous pouvez maintenant accéder aux pages suivantes :</p>";
    echo "<ul>";
    echo "<li><a href='admin/colors.php'>🎨 Gestion des Couleurs</a></li>";
    echo "<li><a href='admin/sizes.php'>📏 Gestion des Tailles</a></li>";
    echo "<li><a href='admin/products.php'>📦 Gestion des Produits</a></li>";
    echo "</ul>";
    
} catch(PDOException $e) {
    echo "❌ Erreur : " . $e->getMessage();
}
?> 