<?php
// Test simple du processus de commande
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Test Simple - Processus de Commande</h1>";

// 1. Test de connexion
echo "<h2>1. Test de connexion</h2>";
try {
    require_once 'config/database.php';
    $database = new Database();
    $conn = $database->getConnection();
    echo "✅ Connexion réussie<br>";
} catch (Exception $e) {
    echo "❌ Erreur de connexion: " . $e->getMessage() . "<br>";
    exit;
}

// 2. Test du système d'intégration
echo "<h2>2. Test du système d'intégration</h2>";
try {
    require_once 'includes/system_integration.php';
    echo "✅ Système d'intégration chargé<br>";
} catch (Exception $e) {
    echo "❌ Erreur système d'intégration: " . $e->getMessage() . "<br>";
    exit;
}

// 3. Vérifier les données de test
echo "<h2>3. Vérification des données</h2>";

// Vérifier les produits
$stmt = $conn->query("SELECT id, name, price, commission_rate FROM products WHERE status = 'active' LIMIT 1");
$product = $stmt->fetch();

if ($product) {
    echo "✅ Produit trouvé: " . $product['name'] . " - " . $product['price'] . " MAD<br>";
} else {
    echo "❌ Aucun produit actif trouvé<br>";
    exit;
}

// Vérifier les affiliés
$stmt = $conn->query("SELECT id, username FROM users WHERE type = 'affiliate' AND status = 'active' LIMIT 1");
$affiliate = $stmt->fetch();

if ($affiliate) {
    echo "✅ Affilié trouvé: " . $affiliate['username'] . "<br>";
} else {
    echo "❌ Aucun affilié actif trouvé<br>";
    exit;
}

// 4. Test de création de commande
echo "<h2>4. Test de création de commande</h2>";

$test_client_data = [
    'name' => 'Test Client',
    'email' => 'test' . time() . '@example.com', // Email unique
    'phone' => '0612345678',
    'address' => '123 Rue Test',
    'city' => 'Casablanca'
];

$test_products = [
    [
        'id' => $product['id'],
        'name' => $product['name'],
        'price' => (float)$product['price'],
        'quantity' => 1,
        'commission_rate' => (float)$product['commission_rate']
    ]
];

echo "Données de test préparées:<br>";
echo "- Client: " . $test_client_data['name'] . " (" . $test_client_data['email'] . ")<br>";
echo "- Produit: " . $test_products[0]['name'] . " - " . $test_products[0]['price'] . " MAD<br>";
echo "- Affilié: " . $affiliate['username'] . " (ID: " . $affiliate['id'] . ")<br>";

// 5. Exécuter le test
echo "<h2>5. Exécution du test</h2>";

try {
    $result = createOrderViaAffiliate($test_client_data, $affiliate['id'], $test_products);
    
    if ($result['success']) {
        echo "✅ Commande créée avec succès!<br>";
        echo "- ID de commande: " . $result['order_id'] . "<br>";
        echo "- Numéro de commande: " . $result['order_number'] . "<br>";
        
        // Vérifier que la commande existe dans la base
        $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$result['order_id']]);
        $created_order = $stmt->fetch();
        
        if ($created_order) {
            echo "✅ Commande vérifiée dans la base de données<br>";
            echo "- Statut: " . $created_order['status'] . "<br>";
            echo "- Montant total: " . $created_order['total_amount'] . " MAD<br>";
            echo "- Commission: " . $created_order['commission_amount'] . " MAD<br>";
        } else {
            echo "❌ Commande non trouvée dans la base de données<br>";
        }
        
        // Vérifier les produits de la commande
        $stmt = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $stmt->execute([$result['order_id']]);
        $order_items = $stmt->fetchAll();
        
        if (count($order_items) > 0) {
            echo "✅ Produits de la commande vérifiés: " . count($order_items) . " produit(s)<br>";
        } else {
            echo "❌ Aucun produit trouvé dans la commande<br>";
        }
        
    } else {
        echo "❌ Échec de la création de commande: " . $result['error'] . "<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Exception lors du test: " . $e->getMessage() . "<br>";
}

// 6. Afficher les logs d'erreur récents
echo "<h2>6. Logs d'erreur récents</h2>";
$error_log = ini_get('error_log');
if ($error_log && file_exists($error_log)) {
    $recent_errors = file_get_contents($error_log);
    if (strlen($recent_errors) > 0) {
        $lines = explode("\n", $recent_errors);
        $recent_lines = array_slice($lines, -10); // 10 dernières lignes
        echo "<pre>" . htmlspecialchars(implode("\n", $recent_lines)) . "</pre>";
    } else {
        echo "Aucune erreur récente dans le log<br>";
    }
} else {
    echo "Log d'erreur non configuré ou inaccessible<br>";
}

echo "<br><a href='test_order_process.php'>Retour au diagnostic complet</a>";
?> 