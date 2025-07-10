<?php
require_once '../../config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Vérifier si la colonne slug existe déjà
    $stmt = $conn->query("SHOW COLUMNS FROM products LIKE 'slug'");
    $column_exists = $stmt->fetch();

    if (!$column_exists) {
        // Ajouter la colonne slug
        $conn->exec("ALTER TABLE products ADD COLUMN slug VARCHAR(255) NOT NULL AFTER name");
        
        // Générer les slugs pour les produits existants
        $stmt = $conn->query("SELECT id, name FROM products");
        $products = $stmt->fetchAll();
        
        foreach ($products as $product) {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $product['name'])));
            
            // Vérifier si le slug existe déjà
            $check_stmt = $conn->prepare("SELECT COUNT(*) FROM products WHERE slug = ? AND id != ?");
            $check_stmt->execute([$slug, $product['id']]);
            if ($check_stmt->fetchColumn() > 0) {
                $slug = $slug . '-' . uniqid();
            }
            
            // Mettre à jour le slug
            $update_stmt = $conn->prepare("UPDATE products SET slug = ? WHERE id = ?");
            $update_stmt->execute([$slug, $product['id']]);
        }
        
        // Ajouter l'index unique
        $conn->exec("ALTER TABLE products ADD UNIQUE KEY unique_slug (slug)");
        
        echo "La colonne slug a été ajoutée avec succès et les slugs ont été générés pour tous les produits existants.";
    } else {
        echo "La colonne slug existe déjà.";
    }

} catch(PDOException $e) {
    echo "Erreur : " . $e->getMessage();
}
?> 