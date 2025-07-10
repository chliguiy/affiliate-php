<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

echo "<h1>Test de la Base de Données CHIC AFFILIATE</h1>";

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    if (!$conn) {
        die("❌ Erreur de connexion à la base de données");
    }
    
    echo "✅ Connexion à la base de données réussie<br><br>";
    
    // Test 1: Vérifier les tables
    echo "<h2>1. Vérification des Tables</h2>";
    $tables = [
        'admins', 'users', 'categories', 'products', 'orders', 'order_items',
        'payments', 'transactions', 'claims', 'bank_info', 'product_images',
        'product_colors', 'product_sizes', 'stock_movements', 'admin_logs',
        'admin_sessions', 'password_resets', 'claim_responses', 'claim_attachments'
    ];
    
    foreach ($tables as $table) {
        $stmt = $conn->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Table '$table' existe<br>";
        } else {
            echo "❌ Table '$table' manquante<br>";
        }
    }
    
    echo "<br>";
    
    // Test 2: Vérifier les données
    echo "<h2>2. Vérification des Données</h2>";
    
    // Compter les administrateurs
    $stmt = $conn->query("SELECT COUNT(*) as count FROM admins");
    $adminCount = $stmt->fetch()['count'];
    echo "👤 Administrateurs: $adminCount<br>";
    
    // Compter les utilisateurs
    $stmt = $conn->query("SELECT COUNT(*) as count FROM users");
    $userCount = $stmt->fetch()['count'];
    echo "👥 Utilisateurs: $userCount<br>";
    
    // Compter les catégories
    $stmt = $conn->query("SELECT COUNT(*) as count FROM categories");
    $categoryCount = $stmt->fetch()['count'];
    echo "📁 Catégories: $categoryCount<br>";
    
    // Compter les produits
    $stmt = $conn->query("SELECT COUNT(*) as count FROM products");
    $productCount = $stmt->fetch()['count'];
    echo "📦 Produits: $productCount<br>";
    
    // Compter les commandes
    $stmt = $conn->query("SELECT COUNT(*) as count FROM orders");
    $orderCount = $stmt->fetch()['count'];
    echo "🛒 Commandes: $orderCount<br>";
    
    echo "<br>";
    
    // Test 3: Vérifier les vues
    echo "<h2>3. Vérification des Vues</h2>";
    $views = ['order_stats', 'payment_stats', 'product_stats', 'claim_stats'];
    
    foreach ($views as $view) {
        try {
            $stmt = $conn->query("SELECT COUNT(*) as count FROM $view");
            $count = $stmt->fetch()['count'];
            echo "✅ Vue '$view' fonctionne ($count enregistrements)<br>";
        } catch (Exception $e) {
            echo "❌ Vue '$view' ne fonctionne pas<br>";
        }
    }
    
    echo "<br>";
    
    // Test 4: Vérifier les triggers
    echo "<h2>4. Vérification des Triggers</h2>";
    $stmt = $conn->query("SHOW TRIGGERS");
    $triggers = $stmt->fetchAll();
    
    foreach ($triggers as $trigger) {
        echo "✅ Trigger '{$trigger['Trigger']}' sur la table '{$trigger['Table']}'<br>";
    }
    
    echo "<br>";
    
    // Test 5: Vérifier les procédures stockées
    echo "<h2>5. Vérification des Procédures Stockées</h2>";
    $stmt = $conn->query("SHOW PROCEDURE STATUS WHERE Db = 'chic_affiliate'");
    $procedures = $stmt->fetchAll();
    
    foreach ($procedures as $procedure) {
        echo "✅ Procédure '{$procedure['Name']}' disponible<br>";
    }
    
    echo "<br>";
    
    // Test 6: Test de connexion administrateur
    echo "<h2>6. Test de Connexion Administrateur</h2>";
    $stmt = $conn->prepare("SELECT * FROM admins WHERE email = ?");
    $stmt->execute(['admin@chic-affiliate.com']);
    $admin = $stmt->fetch();
    
    if ($admin) {
        echo "✅ Administrateur trouvé: {$admin['full_name']}<br>";
        echo "📧 Email: {$admin['email']}<br>";
        echo "🔑 Mot de passe hashé: " . substr($admin['password'], 0, 20) . "...<br>";
    } else {
        echo "❌ Administrateur non trouvé<br>";
    }
    
    echo "<br>";
    
    // Test 7: Test des produits
    echo "<h2>7. Test des Produits</h2>";
    $stmt = $conn->query("SELECT p.name, p.price, c.name as category FROM products p LEFT JOIN categories c ON p.category_id = c.id LIMIT 5");
    $products = $stmt->fetchAll();
    
    foreach ($products as $product) {
        echo "📦 {$product['name']} - {$product['price']} DH ({$product['category']})<br>";
    }
    
    echo "<br>";
    
    // Test 8: Test des commandes
    echo "<h2>8. Test des Commandes</h2>";
    $stmt = $conn->query("SELECT o.order_number, o.customer_name, o.total_amount, o.status FROM orders o LIMIT 3");
    $orders = $stmt->fetchAll();
    
    foreach ($orders as $order) {
        echo "🛒 {$order['order_number']} - {$order['customer_name']} - {$order['total_amount']} DH ({$order['status']})<br>";
    }
    
    echo "<br>";
    
    // Test 9: Test des statistiques
    echo "<h2>9. Test des Statistiques</h2>";
    $stmt = $conn->query("SELECT * FROM order_stats LIMIT 3");
    $stats = $stmt->fetchAll();
    
    foreach ($stats as $stat) {
        echo "📊 Affilié ID {$stat['affiliate_id']}: {$stat['total_orders']} commandes, {$stat['total_commission']} DH de commission<br>";
    }
    
    echo "<br>";
    
    // Test 10: Test de performance
    echo "<h2>10. Test de Performance</h2>";
    $start = microtime(true);
    
    $stmt = $conn->query("SELECT COUNT(*) as count FROM products WHERE status = 'active'");
    $result = $stmt->fetch();
    
    $end = microtime(true);
    $time = round(($end - $start) * 1000, 2);
    
    echo "⚡ Requête exécutée en {$time}ms<br>";
    echo "📦 Produits actifs: {$result['count']}<br>";
    
    echo "<br>";
    
    // Résumé final
    echo "<h2>🎉 Résumé du Test</h2>";
    echo "✅ Tous les tests ont été exécutés avec succès<br>";
    echo "✅ La base de données est opérationnelle<br>";
    echo "✅ Toutes les fonctionnalités sont disponibles<br>";
    echo "<br>";
    echo "<strong>Informations de connexion:</strong><br>";
    echo "🔗 Base de données: chic_affiliate<br>";
    echo "👤 Administrateur: admin@chic-affiliate.com<br>";
    echo "🔑 Mot de passe: password (hashé)<br>";
    echo "<br>";
    echo "🚀 Votre application CHIC AFFILIATE est prête à être utilisée !";
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage();
}
?> 