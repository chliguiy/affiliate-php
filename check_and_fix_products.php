<?php
// Script de diagnostic et correction immédiate
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔧 Diagnostic et Correction Immédiate - Table Products</h1>";

try {
    require_once 'config/database.php';
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "<h2>1. Vérification de la structure actuelle</h2>";
    
    // Vérifier si la table products existe
    $stmt = $conn->query("SHOW TABLES LIKE 'products'");
    if ($stmt->rowCount() == 0) {
        echo "❌ La table 'products' n'existe pas !<br>";
        echo "Création de la table products...<br>";
        
        $create_table_sql = "
        CREATE TABLE products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            price DECIMAL(10,2) DEFAULT 100.00,
            commission_rate DECIMAL(5,2) DEFAULT 10.00,
            status ENUM('active', 'inactive') DEFAULT 'active',
            image_url VARCHAR(500),
            category_id INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        $conn->exec($create_table_sql);
        echo "✅ Table products créée avec succès<br>";
    } else {
        echo "✅ Table products existe<br>";
    }
    
    // Vérifier la structure actuelle
    $stmt = $conn->query("DESCRIBE products");
    $columns = $stmt->fetchAll();
    
    echo "<h3>Colonnes actuelles :</h3>";
    $existing_columns = [];
    foreach ($columns as $column) {
        echo "- " . $column['Field'] . " (" . $column['Type'] . ")<br>";
        $existing_columns[] = $column['Field'];
    }
    
    echo "<h2>2. Correction des colonnes manquantes</h2>";
    
    // Ajouter commission_rate si manquant
    if (!in_array('commission_rate', $existing_columns)) {
        echo "<h3>Ajout de commission_rate...</h3>";
        try {
            $conn->exec("ALTER TABLE products ADD COLUMN commission_rate DECIMAL(5,2) DEFAULT 10.00");
            echo "✅ Colonne commission_rate ajoutée<br>";
        } catch (Exception $e) {
            echo "❌ Erreur lors de l'ajout de commission_rate : " . $e->getMessage() . "<br>";
        }
    } else {
        echo "✅ Colonne commission_rate existe déjà<br>";
    }
    
    // Ajouter status si manquant
    if (!in_array('status', $existing_columns)) {
        echo "<h3>Ajout de status...</h3>";
        try {
            $conn->exec("ALTER TABLE products ADD COLUMN status ENUM('active', 'inactive') DEFAULT 'active'");
            echo "✅ Colonne status ajoutée<br>";
        } catch (Exception $e) {
            echo "❌ Erreur lors de l'ajout de status : " . $e->getMessage() . "<br>";
        }
    } else {
        echo "✅ Colonne status existe déjà<br>";
    }
    
    // Ajouter created_at si manquant
    if (!in_array('created_at', $existing_columns)) {
        echo "<h3>Ajout de created_at...</h3>";
        try {
            $conn->exec("ALTER TABLE products ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
            echo "✅ Colonne created_at ajoutée<br>";
        } catch (Exception $e) {
            echo "❌ Erreur lors de l'ajout de created_at : " . $e->getMessage() . "<br>";
        }
    } else {
        echo "✅ Colonne created_at existe déjà<br>";
    }
    
    echo "<h2>3. Mise à jour des données</h2>";
    
    // Vérifier s'il y a des produits
    $stmt = $conn->query("SELECT COUNT(*) as count FROM products");
    $result = $stmt->fetch();
    
    if ($result['count'] == 0) {
        echo "<h3>Création d'un produit de test...</h3>";
        $insert_sql = "
        INSERT INTO products (name, description, price, commission_rate, status) 
        VALUES ('Produit Test', 'Description du produit test', 100.00, 10.00, 'active')
        ";
        $conn->exec($insert_sql);
        echo "✅ Produit de test créé<br>";
    } else {
        echo "✅ " . $result['count'] . " produits existent<br>";
        
        // Mettre à jour les prix à 0
        $stmt = $conn->query("SELECT COUNT(*) as count FROM products WHERE price = 0 OR price IS NULL");
        $result = $stmt->fetch();
        
        if ($result['count'] > 0) {
            echo "<h3>Mise à jour des prix à 0...</h3>";
            $conn->exec("UPDATE products SET price = 100.00 WHERE price = 0 OR price IS NULL");
            echo "✅ " . $result['count'] . " produits mis à jour avec prix 100 MAD<br>";
        }
        
        // Mettre à jour les commissions à 0
        $stmt = $conn->query("SELECT COUNT(*) as count FROM products WHERE commission_rate = 0 OR commission_rate IS NULL");
        $result = $stmt->fetch();
        
        if ($result['count'] > 0) {
            echo "<h3>Mise à jour des commissions à 0...</h3>";
            $conn->exec("UPDATE products SET commission_rate = 10.00 WHERE commission_rate = 0 OR commission_rate IS NULL");
            echo "✅ " . $result['count'] . " produits mis à jour avec commission 10%<br>";
        }
        
        // Activer les produits inactifs
        $stmt = $conn->query("SELECT COUNT(*) as count FROM products WHERE status = 'inactive' OR status IS NULL");
        $result = $stmt->fetch();
        
        if ($result['count'] > 0) {
            echo "<h3>Activation des produits inactifs...</h3>";
            $conn->exec("UPDATE products SET status = 'active' WHERE status = 'inactive' OR status IS NULL");
            echo "✅ " . $result['count'] . " produits activés<br>";
        }
    }
    
    echo "<h2>4. Test de la requête problématique</h2>";
    
    // Tester la requête qui causait l'erreur
    try {
        $stmt = $conn->prepare("SELECT id, name, price, commission_rate FROM products WHERE status = 'active' LIMIT 1");
        $stmt->execute();
        $test_product = $stmt->fetch();
        
        if ($test_product) {
            echo "✅ Requête test réussie !<br>";
            echo "Produit trouvé : " . $test_product['name'] . " - " . $test_product['price'] . " MAD - " . $test_product['commission_rate'] . "%<br>";
        } else {
            echo "❌ Aucun produit actif trouvé<br>";
        }
    } catch (Exception $e) {
        echo "❌ Erreur lors du test : " . $e->getMessage() . "<br>";
    }
    
    echo "<h2>5. Vérification finale</h2>";
    
    // Afficher quelques produits
    $stmt = $conn->query("SELECT id, name, price, commission_rate, status FROM products LIMIT 5");
    $products = $stmt->fetchAll();
    
    echo "<h3>Produits après correction :</h3>";
    if (count($products) > 0) {
        foreach ($products as $product) {
            echo "- ID: " . $product['id'] . ", Nom: " . $product['name'] . 
                 ", Prix: " . $product['price'] . " MAD, Commission: " . $product['commission_rate'] . 
                 "%, Statut: " . $product['status'] . "<br>";
        }
    } else {
        echo "Aucun produit trouvé<br>";
    }
    
    echo "<h2>6. Test de création de commande</h2>";
    
    // Test de création de commande
    try {
        require_once 'includes/system_integration.php';
        
        $test_client_data = [
            'name' => 'Test Client',
            'email' => 'test' . time() . '@example.com',
            'phone' => '0612345678',
            'address' => '123 Rue Test',
            'city' => 'Casablanca'
        ];
        
        $test_products = [
            [
                'id' => $products[0]['id'],
                'name' => $products[0]['name'],
                'price' => (float)$products[0]['price'],
                'quantity' => 1,
                'commission_rate' => (float)$products[0]['commission_rate']
            ]
        ];
        
        echo "Test avec : " . $test_products[0]['name'] . " - " . $test_products[0]['price'] . " MAD<br>";
        
        $result = createOrderViaAffiliate($test_client_data, 2, $test_products); // Utiliser l'affilié ID 2
        
        if ($result['success']) {
            echo "✅ Test de création de commande réussi !<br>";
            echo "- ID de commande: " . $result['order_id'] . "<br>";
            echo "- Numéro de commande: " . $result['order_number'] . "<br>";
        } else {
            echo "❌ Échec du test : " . $result['error'] . "<br>";
        }
        
    } catch (Exception $e) {
        echo "❌ Erreur lors du test : " . $e->getMessage() . "<br>";
    }
    
    echo "<h2>7. Actions recommandées</h2>";
    echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px;'>";
    echo "<p><strong>✅ Correction terminée !</strong></p>";
    echo "<p>Vous pouvez maintenant :</p>";
    echo "<ul>";
    echo "<li><a href='test_simple_order.php'>Retester la création de commande</a></li>";
    echo "<li><a href='products.php'>Voir les produits</a></li>";
    echo "<li><a href='admin/products.php'>Gérer les produits dans l'admin</a></li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<h2>❌ Erreur Critique</h2>";
    echo "<p>Erreur : " . $e->getMessage() . "</p>";
    echo "<p>Vérifiez que :</p>";
    echo "<ul>";
    echo "<li>XAMPP est démarré</li>";
    echo "<li>MySQL/MariaDB fonctionne</li>";
    echo "<li>La base de données existe</li>";
    echo "<li>Les permissions sont correctes</li>";
    echo "</ul>";
}
?> 