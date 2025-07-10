<?php
// Script de correction forcée de la table products
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔧 Correction Forcée - Table Products</h1>";

try {
    require_once 'config/database.php';
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "<h2>1. Vérification de la connexion</h2>";
    echo "✅ Connexion à la base de données réussie<br>";
    
    echo "<h2>2. Vérification de la table products</h2>";
    
    // Vérifier si la table existe
    $stmt = $conn->query("SHOW TABLES LIKE 'products'");
    if ($stmt->rowCount() == 0) {
        echo "❌ Table products n'existe pas !<br>";
        echo "Création de la table...<br>";
        
        $create_sql = "
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
        
        $conn->exec($create_sql);
        echo "✅ Table products créée avec succès<br>";
    } else {
        echo "✅ Table products existe<br>";
    }
    
    echo "<h2>3. Structure actuelle de la table</h2>";
    
    // Afficher la structure actuelle
    $stmt = $conn->query("DESCRIBE products");
    $columns = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Colonne</th><th>Type</th><th>Null</th><th>Clé</th><th>Défaut</th><th>Extra</th></tr>";
    
    $existing_columns = [];
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "<td>" . $column['Default'] . "</td>";
        echo "<td>" . $column['Extra'] . "</td>";
        echo "</tr>";
        $existing_columns[] = $column['Field'];
    }
    echo "</table>";
    
    echo "<h2>4. Correction forcée des colonnes</h2>";
    
    // Forcer l'ajout de commission_rate
    if (!in_array('commission_rate', $existing_columns)) {
        echo "<h3>Ajout forcé de commission_rate...</h3>";
        try {
            $conn->exec("ALTER TABLE products ADD COLUMN commission_rate DECIMAL(5,2) DEFAULT 10.00");
            echo "✅ Colonne commission_rate ajoutée<br>";
        } catch (Exception $e) {
            echo "❌ Erreur lors de l'ajout de commission_rate : " . $e->getMessage() . "<br>";
            
            // Essayer une approche différente
            echo "Tentative de suppression et recréation de la colonne...<br>";
            try {
                $conn->exec("ALTER TABLE products DROP COLUMN commission_rate");
                echo "✅ Ancienne colonne supprimée<br>";
            } catch (Exception $e2) {
                echo "ℹ️ Colonne n'existait pas<br>";
            }
            
            try {
                $conn->exec("ALTER TABLE products ADD COLUMN commission_rate DECIMAL(5,2) DEFAULT 10.00");
                echo "✅ Nouvelle colonne commission_rate ajoutée<br>";
            } catch (Exception $e3) {
                echo "❌ Impossible d'ajouter commission_rate : " . $e3->getMessage() . "<br>";
            }
        }
    } else {
        echo "✅ Colonne commission_rate existe déjà<br>";
    }
    
    // Forcer l'ajout de status
    if (!in_array('status', $existing_columns)) {
        echo "<h3>Ajout forcé de status...</h3>";
        try {
            $conn->exec("ALTER TABLE products ADD COLUMN status ENUM('active', 'inactive') DEFAULT 'active'");
            echo "✅ Colonne status ajoutée<br>";
        } catch (Exception $e) {
            echo "❌ Erreur lors de l'ajout de status : " . $e->getMessage() . "<br>";
        }
    } else {
        echo "✅ Colonne status existe déjà<br>";
    }
    
    echo "<h2>5. Vérification après correction</h2>";
    
    // Vérifier à nouveau la structure
    $stmt = $conn->query("DESCRIBE products");
    $columns_after = $stmt->fetchAll();
    
    $columns_after_names = [];
    foreach ($columns_after as $column) {
        $columns_after_names[] = $column['Field'];
    }
    
    echo "<h3>Colonnes après correction :</h3>";
    foreach ($columns_after_names as $col) {
        echo "- $col<br>";
    }
    
    if (in_array('commission_rate', $columns_after_names)) {
        echo "✅ commission_rate est maintenant présent<br>";
    } else {
        echo "❌ commission_rate est toujours manquant<br>";
    }
    
    echo "<h2>6. Mise à jour des données</h2>";
    
    // Compter les produits
    $stmt = $conn->query("SELECT COUNT(*) as count FROM products");
    $result = $stmt->fetch();
    echo "Nombre de produits : " . $result['count'] . "<br>";
    
    if ($result['count'] > 0) {
        // Mettre à jour les prix
        $stmt = $conn->query("SELECT COUNT(*) as count FROM products WHERE price = 0 OR price IS NULL");
        $result = $stmt->fetch();
        if ($result['count'] > 0) {
            $conn->exec("UPDATE products SET price = 100.00 WHERE price = 0 OR price IS NULL");
            echo "✅ " . $result['count'] . " produits mis à jour avec prix 100 MAD<br>";
        }
        
        // Mettre à jour les commissions
        if (in_array('commission_rate', $columns_after_names)) {
            $stmt = $conn->query("SELECT COUNT(*) as count FROM products WHERE commission_rate = 0 OR commission_rate IS NULL");
            $result = $stmt->fetch();
            if ($result['count'] > 0) {
                $conn->exec("UPDATE products SET commission_rate = 10.00 WHERE commission_rate = 0 OR commission_rate IS NULL");
                echo "✅ " . $result['count'] . " produits mis à jour avec commission 10%<br>";
            }
        }
        
        // Mettre à jour le status
        if (in_array('status', $columns_after_names)) {
            $stmt = $conn->query("SELECT COUNT(*) as count FROM products WHERE status = 'inactive' OR status IS NULL");
            $result = $stmt->fetch();
            if ($result['count'] > 0) {
                $conn->exec("UPDATE products SET status = 'active' WHERE status = 'inactive' OR status IS NULL");
                echo "✅ " . $result['count'] . " produits activés<br>";
            }
        }
    }
    
    echo "<h2>7. Test de la requête problématique</h2>";
    
    // Tester la requête exacte qui cause l'erreur
    try {
        echo "Test de la requête : SELECT id, name, price, commission_rate FROM products WHERE status = 'active' LIMIT 1<br>";
        
        $stmt = $conn->prepare("SELECT id, name, price, commission_rate FROM products WHERE status = 'active' LIMIT 1");
        $stmt->execute();
        $test_product = $stmt->fetch();
        
        if ($test_product) {
            echo "✅ Requête réussie !<br>";
            echo "Produit : " . $test_product['name'] . " - " . $test_product['price'] . " MAD - " . $test_product['commission_rate'] . "%<br>";
        } else {
            echo "❌ Aucun produit trouvé<br>";
        }
    } catch (Exception $e) {
        echo "❌ Erreur lors du test : " . $e->getMessage() . "<br>";
        
        // Essayer une requête alternative
        echo "Tentative avec requête alternative...<br>";
        try {
            $stmt = $conn->prepare("SELECT id, name, price FROM products LIMIT 1");
            $stmt->execute();
            $test_product = $stmt->fetch();
            
            if ($test_product) {
                echo "✅ Requête alternative réussie !<br>";
                echo "Produit : " . $test_product['name'] . " - " . $test_product['price'] . " MAD<br>";
            }
        } catch (Exception $e2) {
            echo "❌ Même la requête alternative échoue : " . $e2->getMessage() . "<br>";
        }
    }
    
    echo "<h2>8. Affichage des produits</h2>";
    
    // Afficher tous les produits
    try {
        $stmt = $conn->query("SELECT * FROM products LIMIT 10");
        $products = $stmt->fetchAll();
        
        if (count($products) > 0) {
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>ID</th><th>Nom</th><th>Prix</th><th>Commission</th><th>Status</th></tr>";
            
            foreach ($products as $product) {
                echo "<tr>";
                echo "<td>" . $product['id'] . "</td>";
                echo "<td>" . $product['name'] . "</td>";
                echo "<td>" . $product['price'] . "</td>";
                echo "<td>" . (isset($product['commission_rate']) ? $product['commission_rate'] : 'N/A') . "</td>";
                echo "<td>" . (isset($product['status']) ? $product['status'] : 'N/A') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "Aucun produit trouvé<br>";
        }
    } catch (Exception $e) {
        echo "❌ Erreur lors de l'affichage des produits : " . $e->getMessage() . "<br>";
    }
    
    echo "<h2>9. Actions recommandées</h2>";
    echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px;'>";
    echo "<p><strong>✅ Diagnostic terminé !</strong></p>";
    echo "<p>Si le problème persiste :</p>";
    echo "<ul>";
    echo "<li>Vérifiez les permissions de la base de données</li>";
    echo "<li>Redémarrez MySQL/MariaDB</li>";
    echo "<li>Vérifiez que vous utilisez la bonne base de données</li>";
    echo "<li>Exécutez manuellement les commandes SQL dans phpMyAdmin</li>";
    echo "</ul>";
    echo "<p><a href='test_simple_order.php'>Retester la création de commande</a></p>";
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