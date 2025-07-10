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

// Vérification de l'ID de l'affilié
if (!isset($_GET['id'])) {
    header('Location: affiliates.php');
    exit;
}

$affiliate_id = $_GET['id'];

// Connexion à la base de données
$host = 'localhost';
$db   = 'chic_affiliate';
$user = 'root';
$pass = '';
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
if (!$permissions->hasPermission('canViewAffiliates')) {
    header('Location: index.php');
    exit;
}

$logger = new AdminLogger($pdo, $_SESSION['admin_id']);

try {
    // Informations de base de l'affilié
    $stmt = $pdo->prepare("
        SELECT * FROM users 
        WHERE id = ? AND type = 'affiliate'
    ");
    $stmt->execute([$affiliate_id]);
    $affiliate = $stmt->fetch();

    if (!$affiliate) {
        header('Location: affiliates.php');
        exit;
    }

    // Statistiques globales
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT o.id) as total_orders,
            COALESCE(SUM(CASE WHEN o.status = 'delivered' THEN o.final_sale_price ELSE 0 END), 0) as total_sales,
            COALESCE(SUM(CASE WHEN o.status = 'delivered' THEN o.commission_amount ELSE 0 END), 0) as total_commission,
            COUNT(DISTINCT CASE WHEN o.status = 'delivered' THEN o.id END) as delivered_orders,
            COUNT(DISTINCT CASE WHEN o.status = 'cancelled' THEN o.id END) as cancelled_orders,
            COUNT(DISTINCT CASE WHEN o.status = 'new' THEN o.id END) as new_orders,
            COUNT(DISTINCT CASE WHEN o.status = 'confirmed' THEN o.id END) as confirmed_orders,
            COUNT(DISTINCT CASE WHEN o.status = 'shipping' THEN o.id END) as shipping_orders
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        WHERE o.affiliate_id = ?
    ");
    $stmt->execute([$affiliate_id]);
    $stats = $stmt->fetch();

    // Historique des commissions
    $stmt = $pdo->prepare("
        SELECT 
            t.*,
            o.order_number
        FROM transactions t
        LEFT JOIN orders o ON t.order_id = o.id
        WHERE t.user_id = ? AND t.type = 'commission'
        ORDER BY t.created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$affiliate_id]);
    $commissions = $stmt->fetchAll();

    // Commandes récentes
    $stmt = $pdo->prepare("
        SELECT 
            o.*,
            COUNT(oi.id) as total_items
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        WHERE o.affiliate_id = ?
        GROUP BY o.id
        ORDER BY o.created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$affiliate_id]);
    $recent_orders = $stmt->fetchAll();

    // Produits les plus vendus
    $stmt = $pdo->prepare("
        SELECT 
            p.name,
            p.image,
            COUNT(oi.id) as total_sold,
            SUM(oi.quantity) as total_quantity,
            SUM(oi.price * oi.quantity) as total_revenue
        FROM products p
        JOIN order_items oi ON p.id = oi.product_id
        JOIN orders o ON oi.order_id = o.id
        WHERE o.affiliate_id = ?
        GROUP BY p.id
        ORDER BY total_sold DESC
        LIMIT 5
    ");
    $stmt->execute([$affiliate_id]);
    $top_products = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des données : " . $e->getMessage());
    $error = "Une erreur est survenue lors de la récupération des données.";
}

// Initialisation pour éviter les warnings
$recent_orders = [];
$top_products = [];
$commissions = [];

// Journaliser l'accès
$logger->log('view', 'affiliate_details', $affiliate_id, ['ip' => $_SERVER['REMOTE_ADDR']]);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails Affilié - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
        }
        .status-active { background-color: #28a745; color: white; }
        .status-pending { background-color: #ffc107; color: black; }
        .status-suspended { background-color: #dc3545; color: white; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'includes/sidebar.php'; ?>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Détails de l'Affilié</h2>
                    <a href="affiliates.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Retour
                    </a>
                </div>

                <!-- Affiliate Info -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h4><?php echo htmlspecialchars($affiliate['username']); ?></h4>
                                <p class="text-muted"><?php echo htmlspecialchars($affiliate['email']); ?></p>
                                <p>
                                    <span class="status-badge status-<?php echo strtolower($affiliate['status']); ?>">
                                        <?php echo ucfirst($affiliate['status']); ?>
                                    </span>
                                </p>
                                <p>Inscrit le: <?php echo date('d/m/Y', strtotime($affiliate['created_at'])); ?></p>
                            </div>
                            <div class="col-md-6 text-end">
                                <div class="btn-group">
                                    <a href="affiliate_edit.php?id=<?php echo $affiliate['id']; ?>" 
                                       class="btn btn-warning">
                                        <i class="fas fa-edit me-2"></i>Modifier
                                    </a>
                                    <?php if ($affiliate['status'] === 'active'): ?>
                                    <a href="affiliate_suspend.php?id=<?php echo $affiliate['id']; ?>" 
                                       class="btn btn-danger"
                                       onclick="return confirm('Êtes-vous sûr de vouloir suspendre cet affilié ?')">
                                        <i class="fas fa-ban me-2"></i>Suspendre
                                    </a>
                                    <?php else: ?>
                                    <a href="affiliate_activate.php?id=<?php echo $affiliate['id']; ?>" 
                                       class="btn btn-success"
                                       onclick="return confirm('Êtes-vous sûr de vouloir activer cet affilié ?')">
                                        <i class="fas fa-check me-2"></i>Activer
                                    </a>
                                    <?php endif; ?>
                                    <!-- Bouton Payer les commissions -->
                                    <form method="post" action="pay_affiliates.php" style="display:inline-block; margin-left:10px;">
                                        <input type="hidden" name="pay_affiliate_id" value="<?php echo $affiliate['id']; ?>">
                                        <button type="submit" class="btn btn-success" onclick="return confirm('Confirmer le paiement de toutes les commissions de cet affilié ?');">
                                            <i class="fas fa-money-bill-wave me-2"></i>Payer les commissions
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card stat-card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Commandes</h5>
                                <div class="d-flex justify-content-between align-items-center">
                                    <h2 class="mb-0"><?php echo $stats['total_orders'] ?? 0; ?></h2>
                                    <i class="fas fa-shopping-cart fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Ventes</h5>
                                <div class="d-flex justify-content-between align-items-center">
                                    <h2 class="mb-0"><?php echo number_format($stats['total_sales'] ?? 0, 2); ?> MAD</h2>
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
                                    <h2 class="mb-0"><?php echo number_format($stats['total_commission'] ?? 0, 2); ?> MAD</h2>
                                    <i class="fas fa-hand-holding-usd fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card bg-warning text-white">
                            <div class="card-body">
                                <h5 class="card-title">Taux de Livraison</h5>
                                <div class="d-flex justify-content-between align-items-center">
                                    <h2 class="mb-0">
                                        <?php
                                        $total_orders = ($stats['delivered_orders'] ?? 0) + ($stats['cancelled_orders'] ?? 0);
                                        echo $total_orders > 0 
                                            ? round((($stats['delivered_orders'] ?? 0) / $total_orders) * 100, 1) . '%'
                                            : '0%';
                                        ?>
                                    </h2>
                                    <i class="fas fa-truck fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Recent Orders -->
                    <div class="col-md-8">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Commandes Récentes</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>N° Commande</th>
                                                <th>Date</th>
                                                <th>Montant</th>
                                                <th>Statut</th>
                                                <th>Articles</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recent_orders as $order): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                                                <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                                                <td><?php echo number_format($order['final_sale_price'], 2); ?> MAD</td>
                                                <td>
                                                    <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                                        <?php echo ucfirst($order['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $order['total_items']; ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Top Products -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Produits les Plus Vendus</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Produit</th>
                                                <th>Quantité</th>
                                                <th>Revenus</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($top_products as $product): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <?php if ($product['image']): ?>
                                                            <img src="<?php echo htmlspecialchars($product['image']); ?>" 
                                                                 class="rounded me-2" 
                                                                 style="width: 30px; height: 30px; object-fit: cover;">
                                                        <?php endif; ?>
                                                        <?php echo htmlspecialchars($product['name']); ?>
                                                    </div>
                                                </td>
                                                <td><?php echo $product['total_quantity']; ?></td>
                                                <td><?php echo number_format($product['total_revenue'], 2); ?> MAD</td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Commissions History -->
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Historique des Commissions</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Montant</th>
                                                <th>Commande</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($commissions as $commission): ?>
                                            <tr>
                                                <td><?php echo date('d/m/Y', strtotime($commission['created_at'])); ?></td>
                                                <td><?php echo number_format($commission['amount'], 2); ?> MAD</td>
                                                <td>
                                                    <?php if ($commission['order_number']): ?>
                                                        <a href="order_details.php?id=<?php echo $commission['order_id']; ?>">
                                                            <?php echo htmlspecialchars($commission['order_number']); ?>
                                                        </a>
                                                    <?php else: ?>
                                                        -
                                                    <?php endif; ?>
                                                </td>
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
</body>
</html> 