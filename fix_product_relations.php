<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "<h2>🔧 Correction des tables product_colors et product_sizes</h2>";
    
    // Supprimer les anciennes tables si elles existent
    $conn->exec("DROP TABLE IF EXISTS product_colors");
    $conn->exec("DROP TABLE IF EXISTS product_sizes");
    echo "✅ Anciennes tables supprimées<br>";
    
    // Recréer la table product_colors avec la bonne structure
    $sql = "CREATE TABLE product_colors (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        color_id INT NOT NULL,
        stock INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        FOREIGN KEY (color_id) REFERENCES colors(id) ON DELETE CASCADE,
        UNIQUE KEY unique_product_color (product_id, color_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->exec($sql);
    echo "✅ Table product_colors recréée avec succès<br>";
    
    // Recréer la table product_sizes avec la bonne structure
    $sql = "CREATE TABLE product_sizes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        size_id INT NOT NULL,
        stock_quantity INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        FOREIGN KEY (size_id) REFERENCES sizes(id) ON DELETE CASCADE,
        UNIQUE KEY unique_product_size (product_id, size_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->exec($sql);
    echo "✅ Table product_sizes recréée avec succès<br>";
    
    // Vérifier que les tables colors et sizes existent
    $tables = ['colors', 'sizes'];
    foreach ($tables as $table) {
        $stmt = $conn->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() == 0) {
            echo "⚠️ Table '$table' n'existe pas, création...<br>";
            
            if ($table == 'colors') {
                $sql = "CREATE TABLE colors (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(100) NOT NULL UNIQUE,
                    color_code VARCHAR(7) NOT NULL,
                    status ENUM('active', 'inactive') DEFAULT 'active',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                
                $conn->exec($sql);
                
                // Insérer des couleurs par défaut
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
                
                $stmt = $conn->prepare("INSERT INTO colors (name, color_code) VALUES (?, ?)");
                foreach ($defaultColors as $color) {
                    $stmt->execute([$color['name'], $color['color_code']]);
                }
                echo "✅ Table colors créée avec couleurs par défaut<br>";
                
            } elseif ($table == 'sizes') {
                $sql = "CREATE TABLE sizes (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(50) NOT NULL UNIQUE,
                    status ENUM('active', 'inactive') DEFAULT 'active',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                
                $conn->exec($sql);
                
                // Insérer des tailles par défaut
                $defaultSizes = [
                    'XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL',
                    '36', '37', '38', '39', '40', '41', '42', '43', '44', '45', '46',
                    'One Size', 'Taille Unique'
                ];
                
                $stmt = $conn->prepare("INSERT INTO sizes (name) VALUES (?)");
                foreach ($defaultSizes as $size) {
                    $stmt->execute([$size]);
                }
                echo "✅ Table sizes créée avec tailles par défaut<br>";
            }
        }
    }
    
    echo "<br><h3>🎉 Tables corrigées avec succès !</h3>";
    echo "<p>Structure finale :</p>";
    echo "<ul>";
    echo "<li>✅ <strong>product_colors</strong> : product_id + color_id + stock</li>";
    echo "<li>✅ <strong>product_sizes</strong> : product_id + size_id + stock_quantity</li>";
    echo "<li>✅ <strong>colors</strong> : id + name + color_code + status</li>";
    echo "<li>✅ <strong>sizes</strong> : id + name + status</li>";
    echo "</ul>";
    
    echo "<p>Vous pouvez maintenant :</p>";
    echo "<ul>";
    echo "<li><a href='admin/products.php'>📦 Gérer les produits</a></li>";
    echo "<li><a href='admin/colors.php'>🎨 Gérer les couleurs</a></li>";
    echo "<li><a href='admin/sizes.php'>📏 Gérer les tailles</a></li>";
    echo "</ul>";
    
} catch(PDOException $e) {
    echo "❌ Erreur : " . $e->getMessage();
}
?> 