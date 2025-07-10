<?php
// Fichier de test pour diagnostiquer les problèmes de commande
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

echo "<h1>Test de Diagnostic - Processus de Commande</h1>";

// Test 1: Vérifier la connexion à la base de données
echo "<h2>1. Test de connexion à la base de données</h2>";
try {
    require_once 'config/database.php';
    $database = new Database();
    $conn = $database->getConnection();
    
    if ($conn) {
        echo "✅ Connexion à la base de données réussie<br>";
    } else {
        echo "❌ Échec de la connexion à la base de données<br>";
        exit;
    }
} catch (Exception $e) {
    echo "❌ Erreur de connexion : " . $e->getMessage() . "<br>";
    exit;
}

// Test 2: Vérifier les tables nécessaires
echo "<h2>2. Vérification des tables</h2>";
$required_tables = ['users', 'orders', 'products', 'order_items', 'commissions'];
foreach ($required_tables as $table) {
    try {
        $stmt = $conn->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Table '$table' existe<br>";
        } else {
            echo "❌ Table '$table' manquante<br>";
        }
    } catch (Exception $e) {
        echo "❌ Erreur lors de la vérification de '$table' : " . $e->getMessage() . "<br>";
    }
}

// Test 3: Vérifier la structure de la table orders
echo "<h2>3. Structure de la table orders</h2>";
try {
    $stmt = $conn->query("DESCRIBE orders");
    $columns = $stmt->fetchAll();
    echo "Colonnes de la table orders :<br>";
    foreach ($columns as $column) {
        echo "- " . $column['Field'] . " (" . $column['Type'] . ")<br>";
    }
} catch (Exception $e) {
    echo "❌ Erreur lors de la vérification de la structure : " . $e->getMessage() . "<br>";
}

// Test 4: Vérifier les produits disponibles
echo "<h2>4. Produits disponibles</h2>";
try {
    $stmt = $conn->query("SELECT id, name, price, commission_rate FROM products WHERE status = 'active' LIMIT 5");
    $products = $stmt->fetchAll();
    
    if (count($products) > 0) {
        echo "Produits actifs trouvés :<br>";
        foreach ($products as $product) {
            echo "- ID: " . $product['id'] . ", Nom: " . $product['name'] . ", Prix: " . $product['price'] . " MAD, Commission: " . $product['commission_rate'] . "%<br>";
        }
    } else {
        echo "❌ Aucun produit actif trouvé<br>";
    }
} catch (Exception $e) {
    echo "❌ Erreur lors de la récupération des produits : " . $e->getMessage() . "<br>";
}

// Test 5: Vérifier les utilisateurs affiliés
echo "<h2>5. Utilisateurs affiliés</h2>";
try {
    $stmt = $conn->query("SELECT id, username, email FROM users WHERE type = 'affiliate' AND status = 'active' LIMIT 5");
    $affiliates = $stmt->fetchAll();
    
    if (count($affiliates) > 0) {
        echo "Affiliés actifs trouvés :<br>";
        foreach ($affiliates as $affiliate) {
            echo "- ID: " . $affiliate['id'] . ", Nom: " . $affiliate['username'] . ", Email: " . $affiliate['email'] . "<br>";
        }
    } else {
        echo "❌ Aucun affilié actif trouvé<br>";
    }
} catch (Exception $e) {
    echo "❌ Erreur lors de la récupération des affiliés : " . $e->getMessage() . "<br>";
}

// Test 6: Test du système d'intégration
echo "<h2>6. Test du système d'intégration</h2>";
try {
    require_once 'includes/system_integration.php';
    echo "✅ Système d'intégration chargé avec succès<br>";
    
    // Test avec des données fictives
    $test_client_data = [
        'name' => 'Test Client',
        'email' => 'test@example.com',
        'phone' => '0612345678',
        'address' => '123 Rue Test',
        'city' => 'Casablanca'
    ];
    
    $test_products = [
        [
            'id' => 1,
            'name' => 'Produit Test',
            'price' => 100.00,
            'quantity' => 1,
            'commission_rate' => 10
        ]
    ];
    
    $test_affiliate_id = 1; // Utiliser le premier affilié trouvé
    
    echo "Données de test préparées<br>";
    echo "Client: " . $test_client_data['name'] . "<br>";
    echo "Produit: " . $test_products[0]['name'] . " - " . $test_products[0]['price'] . " MAD<br>";
    echo "Affilié ID: " . $test_affiliate_id . "<br>";
    
} catch (Exception $e) {
    echo "❌ Erreur lors du chargement du système d'intégration : " . $e->getMessage() . "<br>";
}

// Test 7: Formulaire de test
echo "<h2>7. Formulaire de test de commande</h2>";
echo "<form method='post' action='process_order.php'>";
echo "<h3>Données client</h3>";
echo "Nom: <input type='text' name='customer_name' value='Test Client' required><br>";
echo "Email: <input type='email' name='customer_email' value='test@example.com' required><br>";
echo "Téléphone: <input type='text' name='customer_phone' value='0612345678' required><br>";
echo "Adresse: <input type='text' name='customer_address' value='123 Rue Test' required><br>";
echo "Ville: <input type='text' name='customer_city' value='Casablanca' required><br>";

echo "<h3>Produits</h3>";
if (isset($products) && count($products) > 0) {
    foreach ($products as $product) {
        echo "<input type='checkbox' name='products[" . $product['id'] . "]' value='1'> ";
        echo $product['name'] . " - " . $product['price'] . " MAD<br>";
    }
} else {
    echo "Aucun produit disponible<br>";
}

echo "<h3>Affilié</h3>";
if (isset($affiliates) && count($affiliates) > 0) {
    echo "<select name='affiliate_id'>";
    foreach ($affiliates as $affiliate) {
        echo "<option value='" . $affiliate['id'] . "'>" . $affiliate['username'] . "</option>";
    }
    echo "</select><br>";
} else {
    echo "Aucun affilié disponible<br>";
}

echo "<br><input type='submit' value='Tester la commande'>";
echo "</form>";

// Test 8: Vérifier les erreurs PHP
echo "<h2>8. Configuration PHP</h2>";
echo "Version PHP: " . phpversion() . "<br>";
echo "Extensions PDO: " . (extension_loaded('pdo') ? '✅' : '❌') . "<br>";
echo "Extension PDO MySQL: " . (extension_loaded('pdo_mysql') ? '✅' : '❌') . "<br>";
echo "Affichage des erreurs: " . (ini_get('display_errors') ? '✅' : '❌') . "<br>";
echo "Log des erreurs: " . ini_get('log_errors') . "<br>";

// Test 9: Vérifier les permissions de fichiers
echo "<h2>9. Permissions des fichiers</h2>";
$files_to_check = [
    'config/database.php',
    'includes/system_integration.php',
    'process_order.php'
];

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        echo "✅ $file existe et est lisible<br>";
    } else {
        echo "❌ $file n'existe pas<br>";
    }
}

echo "<h2>10. Logs d'erreur récents</h2>";
$error_log = ini_get('error_log');
if ($error_log && file_exists($error_log)) {
    $recent_errors = file_get_contents($error_log);
    if (strlen($recent_errors) > 0) {
        echo "<pre>" . htmlspecialchars(substr($recent_errors, -1000)) . "</pre>";
    } else {
        echo "Aucune erreur récente dans le log<br>";
    }
} else {
    echo "Log d'erreur non configuré ou inaccessible<br>";
}
?> 