<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Configuration de session sécurisée
if (session_status() === PHP_SESSION_NONE) {
    $cookieParams = session_get_cookie_params();
    session_set_cookie_params([
        'lifetime' => $cookieParams['lifetime'],
        'path' => '/',
        'domain' => $cookieParams['domain'],
        'secure' => $cookieParams['secure'],
        'httponly' => $cookieParams['httponly'],
        'samesite' => $cookieParams['samesite'] ?? 'Lax',
    ]);
}
session_start();

require_once dirname(__DIR__) . '/config/database.php';

// Utiliser user_type pour la session
$role = strtolower($_SESSION['user_type'] ?? '');
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé - Session invalide']);
    exit();
}

$start_date = $_GET['start_date'] ?? null;
$end_date = $_GET['end_date'] ?? null;

$database = new Database();
$conn = $database->getConnection();

// Log pour debug
error_log("Dashboard API - User ID: $user_id, Role: $role, Start: $start_date, End: $end_date");

// Préparer les filtres selon le rôle
if ($role === 'admin') {
    $where = "1=1";
    $params = [];
    $prodWhere = "1=1";
    $prodParams = [];
    $where30 = "created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    $params30 = [];
} else {
    $where = "affiliate_id = ?";
    $params = [$user_id];
    $prodWhere = "o.affiliate_id = ?";
    $prodParams = [$user_id];
    $where30 = "affiliate_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    $params30 = [$user_id];
}

try {
    // 1. Statistiques principales des commandes
    $stats = [
        'total_orders' => 0,
        'new_orders' => 0,
        'confirmed_orders' => 0,
        'unconfirmed_orders' => 0,
        'shipping_orders' => 0,
        'delivered_orders' => 0,
        'returned_orders' => 0,
        'cancelled_orders' => 0,
        'total_amount' => 0,
        'total_commission' => 0,
        'total_earnings' => 0
    ];

    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_orders,
            SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new_orders,
            SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_orders,
            SUM(CASE WHEN status = 'unconfirmed' THEN 1 ELSE 0 END) as unconfirmed_orders,
            SUM(CASE WHEN status = 'shipped' THEN 1 ELSE 0 END) as shipping_orders,
            SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders,
            SUM(CASE WHEN status = 'returned' THEN 1 ELSE 0 END) as returned_orders,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
            SUM(COALESCE(final_sale_price, 0)) as total_amount,
            SUM(COALESCE(commission_amount, 0)) as total_commission,
            SUM(COALESCE(affiliate_margin, 0)) as total_earnings
        FROM orders 
        WHERE $where
    ");
    $stmt->execute($params);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    // Correction : forcer chaque valeur à 0 si null
    foreach ([
        'total_orders','new_orders','confirmed_orders','unconfirmed_orders','shipping_orders','delivered_orders','returned_orders','cancelled_orders','total_amount','total_commission','total_earnings'
    ] as $key) {
        $stats[$key] = isset($stats[$key]) && $stats[$key] !== null ? (float)$stats[$key] : 0;
    }

    // Correction : afficher la somme des marges affilié (profit) au lieu de la commission
    $stmt = $conn->prepare("
        SELECT SUM(CASE WHEN status = 'delivered' THEN total_amount ELSE 0 END) as total_amount,
               SUM(CASE WHEN status = 'delivered' THEN commission_amount ELSE 0 END) as total_commission
        FROM orders
        WHERE affiliate_id = ?
    ");
    $stmt->execute([$user_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['total_amount'] = $row['total_amount'] ?? 0;
    $stats['total_commission'] = $row['total_commission'] ?? 0;

    // 2. Calcul des changements par rapport au mois précédent
    $previous_month_start = date('Y-m-d', strtotime('first day of last month'));
    $previous_month_end = date('Y-m-d', strtotime('last day of last month'));
    
    if ($role === 'admin') {
        $prevWhere = "created_at BETWEEN ? AND ?";
        $prevParams = [$previous_month_start, $previous_month_end];
    } else {
        $prevWhere = "affiliate_id = ? AND created_at BETWEEN ? AND ?";
        $prevParams = [$user_id, $previous_month_start, $previous_month_end];
    }

    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as prev_total_orders,
            SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as prev_confirmed_orders,
            SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as prev_delivered_orders,
            SUM(COALESCE(affiliate_margin, 0)) as prev_total_earnings
        FROM orders 
        WHERE $prevWhere
    ");
    $stmt->execute($prevParams);
    $prev_stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Calcul des pourcentages de changement
    $stats['total_orders_change'] = calculatePercentageChange($stats['total_orders'], $prev_stats['prev_total_orders']);
    $stats['confirmed_orders_change'] = calculatePercentageChange($stats['confirmed_orders'], $prev_stats['prev_confirmed_orders']);
    $stats['delivered_orders_change'] = calculatePercentageChange($stats['delivered_orders'], $prev_stats['prev_delivered_orders']);
    $stats['total_earnings_change'] = calculatePercentageChange($stats['total_earnings'], $prev_stats['prev_total_earnings']);

    // 3. Données pour le graphique des villes (30 derniers jours)
    $stmt = $conn->prepare("
        SELECT 
            COALESCE(customer_city, 'Ville non spécifiée') as customer_city,
            COUNT(*) as total
        FROM orders 
        WHERE $where30
        GROUP BY customer_city
        ORDER BY total DESC
        LIMIT 6
    ");
    $stmt->execute($params30);
    $cities_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. Données pour le graphique des commandes (30 derniers jours)
    $stmt = $conn->prepare("
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as total
        FROM orders 
        WHERE $where30
        GROUP BY DATE(created_at)
        ORDER BY date
    ");
    $stmt->execute($params30);
    $orders_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Remplir les jours manquants avec 0
    $orders_data = fillMissingDates($orders_data, 30);

    // 5. Top produits les plus vendus
    $stmt = $conn->prepare("
        SELECT 
            p.id,
            p.name,
            (SELECT image_url FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC, sort_order ASC, id ASC LIMIT 1) as image_url,
            COUNT(oi.id) as total_orders,
            SUM(oi.quantity) as total_quantity
        FROM products p
        LEFT JOIN order_items oi ON p.id = oi.product_id
        LEFT JOIN orders o ON oi.order_id = o.id
        WHERE $prodWhere AND p.status = 'active'
        GROUP BY p.id, p.name
        ORDER BY total_orders DESC, total_quantity DESC
        LIMIT 6
    ");
    $stmt->execute($prodParams);
    $top_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Correction chemin image pour chaque produit
    foreach ($top_products as &$prod) {
        if (!empty($prod['image_url'])) {
            $prod['image_url'] = 'uploads/products/' . basename($prod['image_url']);
        } else {
            $prod['image_url'] = 'assets/images/no-image.png';
        }
    }
    unset($prod);

    // 6. Commandes récentes (10 dernières)
    $stmt = $conn->prepare("
        SELECT 
            o.id,
            o.order_number,
            o.customer_name,
            o.customer_city,
            o.final_sale_price AS total_amount,
            o.affiliate_margin,
            o.status,
            o.created_at
        FROM orders o
        WHERE $where
        ORDER BY o.created_at DESC
        LIMIT 10
    ");
    $stmt->execute($params);
    $recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 7. Statistiques supplémentaires
    $additional_stats = [
        'avg_order_value' => $stats['total_orders'] > 0 ? round($stats['total_amount'] / $stats['total_orders'], 2) : 0,
        'conversion_rate' => $stats['total_orders'] > 0 ? round(($stats['delivered_orders'] / $stats['total_orders']) * 100, 1) : 0,
        'total_customers' => 0
    ];

    // Calculer le nombre de clients uniques
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT customer_email) as total_customers
        FROM orders 
        WHERE $where
    ");
    $stmt->execute($params);
    $customers_result = $stmt->fetch(PDO::FETCH_ASSOC);
    $additional_stats['total_customers'] = $customers_result['total_customers'];

    // Préparer la réponse
    $response = [
        'success' => true,
        'stats' => array_merge($stats, $additional_stats),
        'charts' => [
            'cities' => $cities_data,
            'orders' => $orders_data
        ],
        'top_products' => $top_products,
        'recent_orders' => $recent_orders,
        'period' => [
            'start_date' => $start_date,
            'end_date' => $end_date
        ]
    ];

    // Log de succès
    error_log("Dashboard API - Succès pour utilisateur $user_id");

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;

} catch (PDOException $e) {
    error_log("Dashboard API - Erreur PDO: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Erreur de base de données',
        'details' => $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Dashboard API - Erreur générale: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Erreur interne du serveur',
        'details' => $e->getMessage()
    ]);
}

/**
 * Calcule le pourcentage de changement entre deux valeurs
 */
function calculatePercentageChange($current, $previous) {
    if ($previous == 0) {
        return $current > 0 ? '+100%' : '0%';
    }
    
    $change = (($current - $previous) / $previous) * 100;
    $sign = $change >= 0 ? '+' : '';
    return $sign . round($change, 1) . '%';
}

/**
 * Remplit les jours manquants avec des valeurs à 0
 */
function fillMissingDates($data, $days) {
    $result = [];
    $date_map = [];
    
    // Créer un map des dates existantes
    foreach ($data as $item) {
        $date_map[$item['date']] = $item['total'];
    }
    
    // Remplir tous les jours
    for ($i = $days - 1; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $result[] = [
            'date' => $date,
            'total' => isset($date_map[$date]) ? $date_map[$date] : 0
        ];
    }
    
    return $result;
}
?> 