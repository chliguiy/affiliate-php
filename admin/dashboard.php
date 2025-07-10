<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/database.php';
require_once 'includes/auth.php';
require_once 'includes/AdminLogger.php';
require_once 'includes/AdminPermissions.php';

// Vérification de l'authentification et du rôle
if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

// Connexion à la base de données
$host = 'localhost:8889';
$db   = 'chic_affiliate';
$user = 'root';
$pass = 'root';
$charset = 'utf8mb4';
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die('Erreur de connexion : ' . $e->getMessage());
}

// Vérifier les permissions
$permissions = new AdminPermissions($pdo, $_SESSION['admin_id']);
if (!$permissions->hasPermission('canViewDashboard')) {
    header('Location: index.php');
    exit;
}

$logger = new AdminLogger($pdo, $_SESSION['admin_id']);

// Calculer les statistiques
try {
    // Total des ventes
    $stmt = $pdo->query("SELECT COALESCE(SUM(final_sale_price), 0) as total FROM orders WHERE status = 'delivered'");
    $total_sales = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Total des commissions
    $stmt = $pdo->query("SELECT COALESCE(SUM(commission_amount), 0) as total FROM orders WHERE status = 'delivered'");
    $total_commission = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Statistiques des commandes par statut
    $stmt = $pdo->query("
        SELECT 
            status,
            COUNT(*) as count,
            SUM(final_sale_price) as total_amount
        FROM orders 
        GROUP BY status
    ");
    $order_stats = [];
    while ($row = $stmt->fetch()) {
        $order_stats[$row['status']] = [
            'count' => $row['count'],
            'total_amount' => $row['total_amount']
        ];
    }

    // Nombre total de commandes
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM orders");
    $total_orders = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Nombre total d'affiliés
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE type = 'affiliate'");
    $total_affiliates = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Nombre total de clients
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE type = 'customer'");
    $total_customers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Commandes récentes
    $stmt = $pdo->query("
        SELECT o.*, u.username as customer_name 
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        ORDER BY o.created_at DESC 
        LIMIT 5
    ");
    $recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Produits les plus vendus
    $stmt = $pdo->query("
        SELECT p.name, COUNT(oi.id) as total_sales 
        FROM order_items oi 
        JOIN products p ON oi.product_id = p.id 
        GROUP BY p.id 
        ORDER BY total_sales DESC 
        LIMIT 5
    ");
    $top_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Affiliés les plus performants
    $stmt = $pdo->query("
        SELECT u.username, COUNT(DISTINCT o.id) as total_orders, 
               COALESCE(SUM(t.amount), 0) as total_commission 
        FROM users u 
        LEFT JOIN orders o ON o.affiliate_id = u.id 
        LEFT JOIN transactions t ON t.user_id = u.id AND t.type = 'commission'
        WHERE u.type = 'affiliate' 
        GROUP BY u.id 
        ORDER BY total_commission DESC 
        LIMIT 5
    ");
    $top_affiliates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Meilleurs affiliés du mois
    $stmt = $pdo->query("
        SELECT 
            u.username,
            COUNT(DISTINCT o.id) as total_orders,
            COALESCE(SUM(o.total_amount), 0) as total_sales,
            COALESCE(SUM(oi.commission), 0) as total_commission
        FROM users u
        LEFT JOIN orders o ON o.affiliate_id = u.id
        LEFT JOIN order_items oi ON o.id = oi.order_id
        WHERE u.type = 'affiliate'
        AND o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY u.id, u.username
        ORDER BY total_sales DESC
        LIMIT 5
    ");
    $top_affiliates = $stmt->fetchAll();

    // Produits les plus vendus du mois
    $stmt = $pdo->query("
        SELECT 
            p.name,
            p.image,
            COUNT(oi.id) as total_sold,
            SUM(oi.quantity) as total_quantity,
            SUM(oi.price * oi.quantity) as total_revenue
        FROM products p
        LEFT JOIN order_items oi ON p.id = oi.product_id
        LEFT JOIN orders o ON oi.order_id = o.id
        WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY p.id
        ORDER BY total_sold DESC
        LIMIT 5
    ");
    $top_products = $stmt->fetchAll();

    // Statistiques de performance
    $stmt = $pdo->query("
        SELECT 
            COUNT(DISTINCT CASE WHEN status = 'delivered' THEN id END) as delivered_orders,
            COUNT(DISTINCT CASE WHEN status = 'cancelled' THEN id END) as cancelled_orders,
            AVG(CASE WHEN status = 'delivered' THEN total_amount END) as avg_order_value
        FROM orders
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
    $performance_stats = $stmt->fetch();

} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des statistiques : " . $e->getMessage());
    $error = "Une erreur est survenue lors de la récupération des statistiques.";
}

// Journaliser l'accès au tableau de bord
$logger->log('view', 'dashboard', null, ['ip' => $_SERVER['REMOTE_ADDR']]);

// Statistiques clés
$totalProduits = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalCommandes = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$totalAffilies = $pdo->query("SELECT COUNT(*) FROM users WHERE type = 'affiliate'")->fetchColumn();
$totalClients = $pdo->query("SELECT COUNT(*) FROM users WHERE type = 'customer'")->fetchColumn();
$stockFaible = $pdo->query("SELECT COUNT(*) FROM products WHERE stock <= reorder_point OR status = 'out_of_stock'")->fetchColumn();

// Dernières commandes : inclure tous les statuts
    $dernieresCommandes = $pdo->query("SELECT o.id, o.order_number, o.customer_name, o.final_sale_price, o.status, o.created_at, u.username as affiliate FROM orders o LEFT JOIN users u ON o.affiliate_id = u.id ORDER BY o.created_at DESC LIMIT 20")->fetchAll();

// Produits en stock faible
$produitsStockFaible = $pdo->query("SELECT name, stock, reorder_point FROM products WHERE stock <= reorder_point OR status = 'out_of_stock' ORDER BY stock ASC LIMIT 5")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord Admin - Chic Affiliate</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .sidebar {
            min-height: 100vh;
            background-color: #f8f9fa;
            border-right: 1px solid #dee2e6;
        }
        .stat-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 20px;
        }
        .alert-card {
            border-left: 4px solid #dc3545;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
        }
        .status-new { background-color: #ffc107; color: #000; }
        .status-confirmed { background-color: #28a745; color: #fff; }
        .status-shipping { background-color: #17a2b8; color: #fff; }
        .status-delivered { background-color: #28a745; color: #fff; }
        .status-cancelled { background-color: #dc3545; color: #fff; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'includes/sidebar.php'; ?>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 p-4">
                <h2 class="mb-4">Tableau de bord administrateur</h2>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card stat-card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Commandes</h5>
                                <div class="d-flex justify-content-between align-items-center">
                                    <h2 class="mb-0"><?php echo $totalCommandes; ?></h2>
                                    <i class="fas fa-shopping-cart fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card bg-warning text-white">
                            <div class="card-body">
                                <h5 class="card-title">Nouvelles Commandes</h5>
                                <div class="d-flex justify-content-between align-items-center">
                                    <h2 class="mb-0"><?php echo $order_stats['new']['count'] ?? 0; ?></h2>
                                    <i class="fas fa-bell fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Ventes</h5>
                                <div class="d-flex justify-content-between align-items-center">
                                    <h2 class="mb-0"><?php echo number_format($total_sales, 2); ?> MAD</h2>
                                    <i class="fas fa-money-bill-wave fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card bg-info text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Commissions</h5>
                                <div class="d-flex justify-content-between align-items-center">
                                    <h2 class="mb-0"><?php echo number_format($total_commission, 2); ?> MAD</h2>
                                    <i class="fas fa-hand-holding-usd fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Status Statistics -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card stat-card" style="background: linear-gradient(135deg, #FFB74D 0%, #FFA726 100%); color: white;">
                            <div class="card-body">
                                <h5 class="card-title">Nouvelles Commandes</h5>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h2 class="mb-0"><?php echo $order_stats['new']['count'] ?? 0; ?></h2>
                                        <small><?php echo number_format($order_stats['new']['total_amount'] ?? 0, 2); ?> MAD</small>
                                    </div>
                                    <i class="fas fa-clock fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card" style="background: linear-gradient(135deg, #81C784 0%, #66BB6A 100%); color: white;">
                            <div class="card-body">
                                <h5 class="card-title">Commandes Confirmées</h5>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h2 class="mb-0"><?php echo $order_stats['confirmed']['count'] ?? 0; ?></h2>
                                        <small><?php echo number_format($order_stats['confirmed']['total_amount'] ?? 0, 2); ?> MAD</small>
                                    </div>
                                    <i class="fas fa-check-circle fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card" style="background: linear-gradient(135deg, #64B5F6 0%, #42A5F5 100%); color: white;">
                            <div class="card-body">
                                <h5 class="card-title">En Livraison</h5>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h2 class="mb-0"><?php echo $order_stats['shipping']['count'] ?? 0; ?></h2>
                                        <small><?php echo number_format($order_stats['shipping']['total_amount'] ?? 0, 2); ?> MAD</small>
                                    </div>
                                    <i class="fas fa-truck fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card" style="background: linear-gradient(135deg, #4DB6AC 0%, #26A69A 100%); color: white;">
                            <div class="card-body">
                                <h5 class="card-title">Livrées</h5>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h2 class="mb-0"><?php echo $order_stats['delivered']['count'] ?? 0; ?></h2>
                                        <small><?php echo number_format($order_stats['delivered']['total_amount'] ?? 0, 2); ?> MAD</small>
                                    </div>
                                    <i class="fas fa-box-check fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Performance Metrics -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">Performance du mois</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-3">
                                    <div>
                                        <h6 class="text-muted">Taux de livraison</h6>
                                        <h4><?php 
                                            $total_orders = ($performance_stats['delivered_orders'] ?? 0) + ($performance_stats['cancelled_orders'] ?? 0);
                                            echo $total_orders > 0 ? round(($performance_stats['delivered_orders'] / $total_orders) * 100, 1) : 0;
                                        ?>%</h4>
                                    </div>
                                    <i class="fas fa-truck-loading fa-2x text-primary"></i>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="text-muted">Valeur moyenne commande</h6>
                                        <h4><?php echo number_format($performance_stats['avg_order_value'] ?? 0, 2); ?> MAD</h4>
                                    </div>
                                    <i class="fas fa-chart-line fa-2x text-primary"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Top Affiliates -->
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0">Meilleurs Affiliés</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Affilié</th>
                                                <th>Commandes</th>
                                                <th>Ventes</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($top_affiliates as $affiliate): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($affiliate['username']); ?></td>
                                                <td><?php echo $affiliate['total_orders'] ?? 0; ?></td>
                                                <td><?php echo number_format($affiliate['total_sales'] ?? 0, 2); ?> MAD</td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Top Products -->
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0">Produits Populaires</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Produit</th>
                                                <th>Vendus</th>
                                                <th>Revenus</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($top_products as $product): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <?php if (!empty($product['image'])): ?>
                                                            <img src="<?php echo htmlspecialchars($product['image']); ?>" 
                                                                 class="rounded me-2" 
                                                                 style="width: 30px; height: 30px; object-fit: cover;">
                                                        <?php endif; ?>
                                                        <?php echo htmlspecialchars($product['name']); ?>
                                                    </div>
                                                </td>
                                                <td><?php echo $product['total_quantity'] ?? 0; ?></td>
                                                <td><?php echo number_format($product['total_revenue'] ?? 0, 2); ?> MAD</td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Ventes des 30 derniers jours</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="salesChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Répartition des commandes par statut</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="ordersStatusChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Alerts and Recent Orders -->
                <div class="row">
                    <!-- Stock Alerts -->
                    <div class="col-md-4">
                        <div class="card mb-4">
                            <div class="card-header bg-danger text-white">
                                <h5 class="mb-0">Alertes Stock</h5>
                            </div>
                            <div class="card-body">
                                <?php if (count($produitsStockFaible) > 0): ?>
                                    <?php foreach ($produitsStockFaible as $produit): ?>
                                        <div class="alert alert-warning mb-2">
                                            <strong><?php echo htmlspecialchars($produit['name']); ?></strong>
                                            <br>
                                            Stock actuel: <?php echo $produit['stock']; ?>
                                            <br>
                                            Seuil d'alerte: <?php echo $produit['reorder_point']; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-success">Aucune alerte de stock</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Orders -->
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Commandes Récentes</h5>
                                <a href="sales.php" class="btn btn-sm btn-primary">Voir tout</a>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>N° Commande</th>
                                                <th>Client</th>
                                                <th>Date</th>
                                                <th>Montant</th>
                                                <th>Statut</th>
                                                <th>Affilié</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($dernieresCommandes as $commande): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($commande['order_number']); ?></td>
                                                    <td><?php echo htmlspecialchars($commande['customer_name']); ?></td>
                                                    <td><?php echo date('d/m/Y H:i', strtotime($commande['created_at'])); ?></td>
                                                    <td><?php echo number_format($commande['final_sale_price'], 2); ?> MAD</td>
                                                    <td>
                                                        <span class="badge status-badge bg-<?php 
                                                            echo match($commande['status']) {
                                                                'new' => 'warning',
                                                                'confirmed' => 'success',
                                                                'processing' => 'info',
                                                                'shipped' => 'primary',
                                                                'delivered' => 'success',
                                                                'cancelled' => 'danger',
                                                                default => 'secondary'
                                                            };
                                                        ?>">
                                                            <?php echo ucfirst($commande['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($commande['affiliate'] ?? 'N/A'); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Données pour le graphique des ventes
        const salesCtx = document.getElementById('salesChart').getContext('2d');
        new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: <?php 
                    $dates = [];
                    $sales = [];
                    $stmt = $pdo->query("
                        SELECT DATE(created_at) as date, SUM(final_sale_price) as total
                        FROM orders
                        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                        GROUP BY DATE(created_at)
                        ORDER BY date
                    ");
                    while ($row = $stmt->fetch()) {
                        $dates[] = date('d/m', strtotime($row['date']));
                        $sales[] = $row['total'];
                    }
                    echo json_encode($dates);
                ?>,
                datasets: [{
                    label: 'Ventes (MAD)',
                    data: <?php echo json_encode($sales); ?>,
                    borderColor: '#28a745',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        // Données pour le graphique des statuts de commande
        const statusCtx = document.getElementById('ordersStatusChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Nouvelle', 'Confirmée', 'En livraison', 'Livrée', 'Annulée'],
                datasets: [{
                    data: <?php 
                        $status_counts = [];
                        $stmt = $pdo->query("
                            SELECT status, COUNT(*) as count
                            FROM orders
                            GROUP BY status
                        ");
                        while ($row = $stmt->fetch()) {
                            $status_counts[$row['status']] = $row['count'];
                        }
                        echo json_encode([
                            $status_counts['new'] ?? 0,
                            $status_counts['confirmed'] ?? 0,
                            $status_counts['shipping'] ?? 0,
                            $status_counts['delivered'] ?? 0,
                            $status_counts['cancelled'] ?? 0
                        ]);
                    ?>,
                    backgroundColor: ['#ffc107', '#28a745', '#17a2b8', '#28a745', '#dc3545']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    </script>
</body>
</html> 