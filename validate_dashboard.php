<?php
/**
 * Script de validation du dashboard affilié
 * Ce script teste toutes les fonctionnalités du nouveau dashboard
 */

require_once 'config/database.php';

echo "<h1>Validation du Dashboard Affilié</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .warning { color: orange; font-weight: bold; }
    .info { color: blue; font-weight: bold; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
    .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
</style>";

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Test 1: Vérification de la base de données
    echo "<div class='test-section'>";
    echo "<h2>1. Vérification de la Base de Données</h2>";
    
    $tables = ['users', 'orders', 'products', 'order_items'];
    $all_tables_exist = true;
    
    foreach ($tables as $table) {
        $stmt = $conn->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        if ($stmt->fetch()) {
            echo "<p class='success'>✓ Table '$table' existe</p>";
        } else {
            echo "<p class='error'>✗ Table '$table' manquante</p>";
            $all_tables_exist = false;
        }
    }
    
    if ($all_tables_exist) {
        echo "<p class='success'>✓ Toutes les tables requises sont présentes</p>";
    } else {
        echo "<p class='error'>✗ Certaines tables sont manquantes</p>";
    }
    echo "</div>";
    
    // Test 2: Vérification des affiliés
    echo "<div class='test-section'>";
    echo "<h2>2. Vérification des Affiliés</h2>";
    
    $stmt = $conn->prepare("
        SELECT id, username, email, status, created_at 
        FROM users 
        WHERE type = 'affiliate' 
        ORDER BY id
    ");
    $stmt->execute();
    $affiliates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($affiliates)) {
        echo "<p class='warning'>⚠ Aucun affilié trouvé dans la base de données</p>";
    } else {
        echo "<p class='success'>✓ " . count($affiliates) . " affilié(s) trouvé(s)</p>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Nom d'utilisateur</th><th>Email</th><th>Statut</th><th>Date création</th></tr>";
        foreach ($affiliates as $affiliate) {
            $status_class = $affiliate['status'] === 'active' ? 'success' : 'warning';
            echo "<tr>";
            echo "<td>" . $affiliate['id'] . "</td>";
            echo "<td>" . htmlspecialchars($affiliate['username']) . "</td>";
            echo "<td>" . htmlspecialchars($affiliate['email']) . "</td>";
            echo "<td class='$status_class'>" . $affiliate['status'] . "</td>";
            echo "<td>" . $affiliate['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    echo "</div>";
    
    // Test 3: Vérification des commandes
    echo "<div class='test-section'>";
    echo "<h2>3. Vérification des Commandes</h2>";
    
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_orders,
            COUNT(DISTINCT affiliate_id) as affiliates_with_orders,
            COUNT(CASE WHEN affiliate_id IS NOT NULL THEN 1 END) as orders_with_affiliate,
            COUNT(CASE WHEN created_at IS NOT NULL AND created_at != '0000-00-00 00:00:00' THEN 1 END) as orders_with_date,
            MIN(created_at) as first_order,
            MAX(created_at) as last_order
        FROM orders
    ");
    $stmt->execute();
    $order_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<table>";
    echo "<tr><th>Métrique</th><th>Valeur</th><th>Statut</th></tr>";
    echo "<tr><td>Total commandes</td><td>" . $order_stats['total_orders'] . "</td><td class='info'>-</td></tr>";
    echo "<tr><td>Commandes avec affilié</td><td>" . $order_stats['orders_with_affiliate'] . "</td><td class='" . ($order_stats['orders_with_affiliate'] > 0 ? 'success' : 'error') . "'>" . ($order_stats['orders_with_affiliate'] > 0 ? '✓' : '✗') . "</td></tr>";
    echo "<tr><td>Commandes avec date</td><td>" . $order_stats['orders_with_date'] . "</td><td class='" . ($order_stats['orders_with_date'] > 0 ? 'success' : 'error') . "'>" . ($order_stats['orders_with_date'] > 0 ? '✓' : '✗') . "</td></tr>";
    echo "<tr><td>Première commande</td><td>" . $order_stats['first_order'] . "</td><td class='info'>-</td></tr>";
    echo "<tr><td>Dernière commande</td><td>" . $order_stats['last_order'] . "</td><td class='info'>-</td></tr>";
    echo "</table>";
    
    if ($order_stats['total_orders'] > 0) {
        // Afficher quelques commandes d'exemple
        $stmt = $conn->prepare("
            SELECT o.*, u.username as affiliate_name
            FROM orders o
            LEFT JOIN users u ON o.affiliate_id = u.id
            WHERE o.affiliate_id IS NOT NULL
            ORDER BY o.created_at DESC
            LIMIT 5
        ");
        $stmt->execute();
        $sample_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($sample_orders)) {
            echo "<h4>Exemples de commandes récentes :</h4>";
            echo "<table>";
            echo "<tr><th>ID</th><th>N° Commande</th><th>Affilié</th><th>Client</th><th>Montant</th><th>Statut</th><th>Date</th></tr>";
            foreach ($sample_orders as $order) {
                echo "<tr>";
                echo "<td>" . $order['id'] . "</td>";
                echo "<td>" . htmlspecialchars($order['order_number']) . "</td>";
                echo "<td>" . htmlspecialchars($order['affiliate_name']) . "</td>";
                echo "<td>" . htmlspecialchars($order['customer_name']) . "</td>";
                echo "<td>" . number_format($order['total_amount'], 2) . " Dhs</td>";
                echo "<td>" . $order['status'] . "</td>";
                echo "<td>" . $order['created_at'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    }
    echo "</div>";
    
    // Test 4: Test de l'API Dashboard
    echo "<div class='test-section'>";
    echo "<h2>4. Test de l'API Dashboard</h2>";
    
    if (!empty($affiliates)) {
        // Simuler une session pour le premier affilié
        session_start();
        $_SESSION['user_id'] = $affiliates[0]['id'];
        $_SESSION['role'] = 'affiliate';
        
        // Test avec différentes périodes
        $test_periods = [
            ['name' => '30 derniers jours', 'start' => date('Y-m-d', strtotime('-30 days')), 'end' => date('Y-m-d')],
            ['name' => '7 derniers jours', 'start' => date('Y-m-d', strtotime('-7 days')), 'end' => date('Y-m-d')],
            ['name' => 'Aujourd\'hui', 'start' => date('Y-m-d'), 'end' => date('Y-m-d')]
        ];
        
        foreach ($test_periods as $period) {
            echo "<h4>Test période : " . $period['name'] . "</h4>";
            
            // Simuler l'appel API
            ob_start();
            $_GET['start_date'] = $period['start'];
            $_GET['end_date'] = $period['end'];
            
            include 'api/dashboard_data.php';
            $api_response = ob_get_clean();
            
            $api_data = json_decode($api_response, true);
            
            if ($api_data && isset($api_data['success'])) {
                echo "<p class='success'>✓ API fonctionne pour " . $period['name'] . "</p>";
                echo "<p class='info'>Données reçues :</p>";
                echo "<ul>";
                echo "<li>Stats : " . (isset($api_data['stats']) ? '✓' : '✗') . "</li>";
                echo "<li>Graphiques : " . (isset($api_data['charts']) ? '✓' : '✗') . "</li>";
                echo "<li>Top produits : " . (isset($api_data['top_products']) ? '✓' : '✗') . "</li>";
                echo "<li>Commandes récentes : " . (isset($api_data['recent_orders']) ? '✓' : '✗') . "</li>";
                echo "</ul>";
            } else {
                echo "<p class='error'>✗ Erreur API pour " . $period['name'] . "</p>";
                echo "<pre>" . htmlspecialchars($api_response) . "</pre>";
            }
        }
    } else {
        echo "<p class='warning'>⚠ Impossible de tester l'API sans affilié</p>";
    }
    echo "</div>";
    
    // Test 5: Vérification des fichiers
    echo "<div class='test-section'>";
    echo "<h2>5. Vérification des Fichiers</h2>";
    
    $required_files = [
        'dashboard.php',
        'api/dashboard_data.php',
        'config/database.php',
        'includes/sidebar.php'
    ];
    
    $all_files_exist = true;
    foreach ($required_files as $file) {
        if (file_exists($file)) {
            echo "<p class='success'>✓ Fichier '$file' existe</p>";
        } else {
            echo "<p class='error'>✗ Fichier '$file' manquant</p>";
            $all_files_exist = false;
        }
    }
    
    if ($all_files_exist) {
        echo "<p class='success'>✓ Tous les fichiers requis sont présents</p>";
    }
    echo "</div>";
    
    // Test 6: Test de performance
    echo "<div class='test-section'>";
    echo "<h2>6. Test de Performance</h2>";
    
    $start_time = microtime(true);
    
    // Simuler un appel API
    if (!empty($affiliates)) {
        session_start();
        $_SESSION['user_id'] = $affiliates[0]['id'];
        $_SESSION['role'] = 'affiliate';
        
        ob_start();
        $_GET['start_date'] = date('Y-m-d', strtotime('-30 days'));
        $_GET['end_date'] = date('Y-m-d');
        
        include 'api/dashboard_data.php';
        ob_end_clean();
        
        $end_time = microtime(true);
        $execution_time = ($end_time - $start_time) * 1000; // en millisecondes
        
        if ($execution_time < 1000) {
            echo "<p class='success'>✓ Temps d'exécution : " . round($execution_time, 2) . " ms (excellent)</p>";
        } elseif ($execution_time < 3000) {
            echo "<p class='warning'>⚠ Temps d'exécution : " . round($execution_time, 2) . " ms (acceptable)</p>";
        } else {
            echo "<p class='error'>✗ Temps d'exécution : " . round($execution_time, 2) . " ms (trop lent)</p>";
        }
    }
    echo "</div>";
    
    // Résumé final
    echo "<div class='test-section'>";
    echo "<h2>Résumé de la Validation</h2>";
    
    $issues = [];
    $warnings = [];
    $successes = [];
    
    // Collecter les résultats
    if (!$all_tables_exist) $issues[] = "Tables manquantes";
    if (empty($affiliates)) $warnings[] = "Aucun affilié trouvé";
    if ($order_stats['orders_with_affiliate'] == 0) $warnings[] = "Aucune commande avec affilié";
    if ($order_stats['orders_with_date'] == 0) $issues[] = "Aucune commande avec date valide";
    
    if (empty($issues) && empty($warnings)) {
        echo "<p class='success'>🎉 Validation réussie ! Le dashboard affilié est prêt à être utilisé.</p>";
    } else {
        if (!empty($issues)) {
            echo "<p class='error'>❌ Problèmes détectés :</p>";
            echo "<ul>";
            foreach ($issues as $issue) {
                echo "<li class='error'>$issue</li>";
            }
            echo "</ul>";
        }
        
        if (!empty($warnings)) {
            echo "<p class='warning'>⚠ Avertissements :</p>";
            echo "<ul>";
            foreach ($warnings as $warning) {
                echo "<li class='warning'>$warning</li>";
            }
            echo "</ul>";
        }
    }
    
    echo "<p class='info'>💡 Conseil : Exécutez le script fix_dashboard_dates.php si des problèmes sont détectés.</p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='test-section'>";
    echo "<h2 class='error'>Erreur Critique</h2>";
    echo "<p class='error'>Une erreur est survenue : " . $e->getMessage() . "</p>";
    echo "</div>";
}
?> 