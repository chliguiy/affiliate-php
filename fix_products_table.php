<?php
// Fichier pour corriger la structure de la table products
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Correction de la Table Products</h1>";

try {
    require_once 'config/database.php';
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "<h2>1. Vérification de la structure actuelle</h2>";
    
    // Vérifier la structure actuelle de la table products
    $stmt = $conn->query("DESCRIBE products");
    $columns = $stmt->fetchAll();
    
    echo "<h3>Colonnes actuelles :</h3>";
    $existing_columns = [];
    foreach ($columns as $column) {
        echo "- " . $column['Field'] . " (" . $column['Type'] . ")<br>";
        $existing_columns[] = $column['Field'];
    }
    
    echo "<h2>2. Ajout des colonnes manquantes</h2>";
    
    // Liste des colonnes nécessaires
    $required_columns = [
        'commission_rate' => 'DECIMAL(5,2) DEFAULT 10.00',
        'status' => "ENUM('active', 'inactive') DEFAULT 'active'",
        'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
        'updated_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
    ];
    
    foreach ($required_columns as $column_name => $column_definition) {
        if (!in_array($column_name, $existing_columns)) {
            echo "<h3>Ajout de la colonne : $column_name</h3>";
            
            $sql = "ALTER TABLE products ADD COLUMN $column_name $column_definition";
            echo "SQL: $sql<br>";
            
            try {
                $conn->exec($sql);
                echo "✅ Colonne $column_name ajoutée avec succès<br>";
            } catch (Exception $e) {
                echo "❌ Erreur lors de l'ajout de $column_name : " . $e->getMessage() . "<br>";
            }
        } else {
            echo "✅ Colonne $column_name existe déjà<br>";
        }
    }
    
    echo "<h2>3. Mise à jour des données existantes</h2>";
    
    // Mettre à jour les prix à 0 vers des valeurs par défaut
    $stmt = $conn->query("SELECT COUNT(*) as count FROM products WHERE price = 0 OR price IS NULL");
    $result = $stmt->fetch();
    
    if ($result['count'] > 0) {
        echo "<h3>Mise à jour des prix à 0</h3>";
        
        // Mettre à jour les prix à 0 vers 100 MAD par défaut
        $update_sql = "UPDATE products SET price = 100.00 WHERE price = 0 OR price IS NULL";
        $conn->exec($update_sql);
        
        echo "✅ " . $result['count'] . " produits mis à jour avec un prix de 100 MAD<br>";
    } else {
        echo "✅ Aucun produit avec prix à 0 trouvé<br>";
    }
    
    // Mettre à jour les commissions à 0 vers des valeurs par défaut
    $stmt = $conn->query("SELECT COUNT(*) as count FROM products WHERE commission_rate = 0 OR commission_rate IS NULL");
    $result = $stmt->fetch();
    
    if ($result['count'] > 0) {
        echo "<h3>Mise à jour des commissions à 0</h3>";
        
        // Mettre à jour les commissions à 0 vers 10% par défaut
        $update_sql = "UPDATE products SET commission_rate = 10.00 WHERE commission_rate = 0 OR commission_rate IS NULL";
        $conn->exec($update_sql);
        
        echo "✅ " . $result['count'] . " produits mis à jour avec une commission de 10%<br>";
    } else {
        echo "✅ Aucun produit avec commission à 0 trouvé<br>";
    }
    
    // Activer tous les produits inactifs
    $stmt = $conn->query("SELECT COUNT(*) as count FROM products WHERE status = 'inactive' OR status IS NULL");
    $result = $stmt->fetch();
    
    if ($result['count'] > 0) {
        echo "<h3>Activation des produits inactifs</h3>";
        
        $update_sql = "UPDATE products SET status = 'active' WHERE status = 'inactive' OR status IS NULL";
        $conn->exec($update_sql);
        
        echo "✅ " . $result['count'] . " produits activés<br>";
    } else {
        echo "✅ Tous les produits sont déjà actifs<br>";
    }
    
    echo "<h2>4. Vérification finale</h2>";
    
    // Vérifier les produits après correction
    $stmt = $conn->query("SELECT id, name, price, commission_rate, status FROM products LIMIT 5");
    $products = $stmt->fetchAll();
    
    echo "<h3>Produits après correction :</h3>";
    foreach ($products as $product) {
        echo "- ID: " . $product['id'] . ", Nom: " . $product['name'] . 
             ", Prix: " . $product['price'] . " MAD, Commission: " . $product['commission_rate'] . 
             "%, Statut: " . $product['status'] . "<br>";
    }
    
    echo "<h2>5. Test de la requête corrigée</h2>";
    
    // Tester la requête qui causait l'erreur
    $stmt = $conn->prepare("SELECT id, name, price, commission_rate FROM products WHERE status = 'active' LIMIT 1");
    $stmt->execute();
    $test_product = $stmt->fetch();
    
    if ($test_product) {
        echo "✅ Requête test réussie !<br>";
        echo "Produit trouvé : " . $test_product['name'] . " - " . $test_product['price'] . " MAD - " . $test_product['commission_rate'] . "%<br>";
    } else {
        echo "❌ Aucun produit actif trouvé<br>";
    }
    
    echo "<h2>6. Actions recommandées</h2>";
    echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px;'>";
    echo "<p><strong>✅ La table products a été corrigée !</strong></p>";
    echo "<p>Vous pouvez maintenant :</p>";
    echo "<ul>";
    echo "<li><a href='test_simple_order.php'>Retester la création de commande</a></li>";
    echo "<li><a href='products.php'>Voir les produits</a></li>";
    echo "<li><a href='admin/products.php'>Gérer les produits dans l'admin</a></li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<h2>❌ Erreur</h2>";
    echo "<p>Erreur : " . $e->getMessage() . "</p>";
    echo "<p>Vérifiez que :</p>";
    echo "<ul>";
    echo "<li>La base de données est accessible</li>";
    echo "<li>Les permissions sont correctes</li>";
    echo "<li>La table products existe</li>";
    echo "</ul>";
}
?> 