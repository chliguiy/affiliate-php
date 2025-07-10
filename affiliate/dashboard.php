<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
echo 'TEST DEBUG<br>';
session_start();
require_once '../config/database.php';
require_once '../config/auth.php';

// Vérifier si l'utilisateur est affilié
requireRole('affiliate');

$user_id = $_SESSION['user_id'];

// Vérification du statut affilié
$stmt = $conn->prepare("SELECT status FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user_status = $stmt->fetchColumn();
if ($user_status !== 'active') {
    session_destroy();
    die('<div style="margin:50px auto;max-width:500px;padding:30px 40px;background:#fff;border-radius:10px;text-align:center;font-size:1.2rem;color:#c0392b;box-shadow:0 4px 16px rgba(0,0,0,0.08)">Accès refusé : votre compte affilié n\'est pas actif.<br><a href="../login.php">Retour à la connexion</a></div>');
}

// Récupérer les statistiques de l'affilié
$stats = [
    'total_sales' => 0,
    'total_commission' => 0,
    'pending_commission' => 0,
    'total_clicks' => 0
];

// Total des ventes et commissions (basées sur la marge affiliée)
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_sales,
        SUM(final_sale_price) as total_amount,
        SUM(commission_amount) as total_commission
    FROM orders 
    WHERE affiliate_id = ? AND status = 'delivered'
");
$stmt->execute([$user_id]);
$sales_stats = $stmt->fetch();

$stats['total_sales'] = $sales_stats['total_sales'] ?? 0;
$stats['total_commission'] = $sales_stats['total_commission'] ?? 0;

// Commissions en attente (commandes avec statut 'new')
$stmt = $conn->prepare("
    SELECT SUM(commission_amount) as pending_amount
    FROM orders 
    WHERE affiliate_id = ? AND status = 'new'
");
$stmt->execute([$user_id]);
$pending = $stmt->fetch();
$stats['pending_commission'] = $pending['pending_amount'] ?? 0;

// Total des clics
$stmt = $conn->prepare("
    SELECT SUM(clicks) as total_clicks
    FROM affiliate_links 
    WHERE affiliate_id = ?
");
$stmt->execute([$user_id]);
$clicks = $stmt->fetch();
$stats['total_clicks'] = $clicks['total_clicks'] ?? 0;

// Récupérer les dernières commandes
$stmt = $conn->prepare("
    SELECT o.*, 
           COUNT(oi.id) as product_count
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE o.affiliate_id = ?
    GROUP BY o.id
    ORDER BY o.created_at DESC
    LIMIT 5
");
$stmt->execute([$user_id]);
$recent_sales = $stmt->fetchAll();

// Récupérer les produits les plus performants (basé sur les commandes)
$stmt = $conn->prepare("
    SELECT 
        p.*,
        COUNT(DISTINCT o.id) as sales_count,
        SUM(o.commission_amount) as total_commission,
        SUM(al.clicks) as total_clicks
    FROM products p
    JOIN affiliate_links al ON p.id = al.product_id
    LEFT JOIN order_items oi ON p.id = oi.product_id
    LEFT JOIN orders o ON oi.order_id = o.id AND o.affiliate_id = ?
    WHERE al.affiliate_id = ?
    GROUP BY p.id
    ORDER BY sales_count DESC 
    LIMIT 5
");
$stmt->execute([$user_id, $user_id]);
$top_products = $stmt->fetchAll();

// Récupérer les 5 dernières commandes passées par l'affilié
$stmt = $conn->prepare("
    SELECT *
    FROM orders
    WHERE affiliate_id = ?
    ORDER BY created_at DESC
    LIMIT 5
");
$stmt->execute([$user_id]);
$recent_orders = $stmt->fetchAll();

// AJOUT : Statistiques commandes (total et par statut)
$orderStats = [
    'total' => 0,
    'nouveau' => 0,
    'non_confirme' => 0,
    'confirme' => 0,
    'en_livraison' => 0,
    'livre' => 0,
    'retourne' => 0,
    'refuse' => 0,
    'annule' => 0,
    'duplique' => 0,
    'change' => 0
];
$stmt = $conn->prepare("
    SELECT status, COUNT(*) as count
    FROM orders
    WHERE affiliate_id = ?
    GROUP BY status
");
$stmt->execute([$user_id]);
$rows = $stmt->fetchAll();
foreach ($rows as $row) {
    $status = strtolower($row['status']);
    $orderStats['total'] += $row['count'];
    if (isset($orderStats[$status])) {
        $orderStats[$status] = $row['count'];
    }
}

// Récupérer le chiffre d'affaires total payé par le client
$stmt = $conn->prepare("SELECT SUM(final_sale_price) as total_orders_amount FROM orders WHERE affiliate_id = ?");
$stmt->execute([$user_id]);
$total_orders_amount = $stmt->fetchColumn() ?? 0;

// Récupérer le gain actuel (commissions en attente)
$stmt = $conn->prepare("SELECT SUM(commission_amount) FROM orders WHERE affiliate_id = ? AND status = 'delivered' AND commission_paid = 0");
$stmt->execute([$user_id]);
$pending_commission = $stmt->fetchColumn() ?? 0;

// Récupérer l'historique des paiements
$stmt = $conn->prepare("SELECT montant, date_paiement, statut FROM affiliate_payments WHERE affiliate_id = ? ORDER BY date_paiement DESC");
$stmt->execute([$user_id]);
$payments_history = $stmt->fetchAll();

// Affichage du message publicitaire activé
try {
    $stmt = $conn->prepare("SELECT message, date_debut, date_fin FROM admin_messages WHERE is_active = 1 AND date_debut <= NOW() AND date_fin >= NOW() ORDER BY id DESC LIMIT 1");
    $pub_message = $stmt->execute() ? $stmt->fetch() : null;
} catch (Exception $e) {
    $pub_message = null;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم - شيك أفلييت</title>
    <!-- Bootstrap 5 RTL CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }

        .sidebar {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            min-height: 100vh;
            padding: 20px;
        }

        .sidebar .nav-link {
            color: white;
            padding: 10px 15px;
            margin: 5px 0;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .sidebar .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .sidebar .nav-link.active {
            background-color: rgba(255, 255, 255, 0.2);
        }

        .main-content {
            padding: 0 1rem 0;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            transition: transform 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .stat-card {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }

        .stat-icon {
            font-size: 2rem;
            opacity: 0.8;
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: bold;
        }

        .product-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 8px;
        }

        .chart-container {
            position: relative;
            height: 300px;
        }
    </style>
</head>
<body>
    <?php
    // Supprime le message de test jaune
    // Affichage du message publicitaire activé juste sous la barre de bienvenue
    try {
        $stmt = $conn->prepare("SELECT message, date_debut, date_fin FROM admin_messages WHERE is_active = 1 AND date_debut <= NOW() AND date_fin >= NOW() ORDER BY id DESC LIMIT 1");
        $pub_message = $stmt->execute() ? $stmt->fetch() : null;
    } catch (Exception $e) {
        $pub_message = null;
    }
    ?>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <h4 class="mb-4">لوحة التحكم</h4>
                <nav class="nav flex-column">
                    <a class="nav-link active" href="dashboard.php">
                        <i class="fas fa-home me-2"></i> الرئيسية
                    </a>
                    <a class="nav-link" href="products.php">
                        <i class="fas fa-box me-2"></i> المنتجات
                    </a>
                    <a class="nav-link" href="links.php">
                        <i class="fas fa-link me-2"></i> روابط التسويق
                    </a>
                    <a class="nav-link" href="sales.php">
                        <i class="fas fa-chart-line me-2"></i> المبيعات
                    </a>
                    <a class="nav-link" href="commissions.php">
                        <i class="fas fa-money-bill-wave me-2"></i> العمولات
                    </a>
                    <a class="nav-link" href="profile.php">
                        <i class="fas fa-user me-2"></i> الملف الشخصي
                    </a>
                    <a class="nav-link" href="../logout.php">
                        <i class="fas fa-sign-out-alt me-2"></i> تسجيل الخروج
                    </a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>مرحباً بك في لوحة التحكم</h2>
                    <a href="products.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>اختر منتجات للترويج
                    </a>
                </div>
                <?php if (!empty($pub_message) && !empty($pub_message['message'])): ?>
                <div class="alert alert-info text-center mb-4" style="font-size:1.2rem; font-weight:500; border-radius:12px; box-shadow:0 2px 8px rgba(44,62,80,0.08);">
                    <i class="fas fa-bullhorn me-2"></i>
                    <?= nl2br(htmlspecialchars($pub_message['message'])) ?>
                    <div style="font-size:0.95em; color:#555; margin-top:4px;">
                        <i class="fas fa-calendar-alt me-1"></i>
                        من <?= date('d/m/Y H:i', strtotime($pub_message['date_debut'])) ?> إلى <?= date('d/m/Y H:i', strtotime($pub_message['date_fin'])) ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title mb-0">إجمالي المبيعات</h6>
                                        <div class="stat-value"><?php echo $stats['total_sales']; ?></div>
                                    </div>
                                    <i class="fas fa-shopping-cart stat-icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title mb-0">إجمالي العمولات</h6>
                                        <div class="stat-value"><?php echo number_format($stats['total_commission'], 2); ?> درهم</div>
                                    </div>
                                    <i class="fas fa-money-bill-wave stat-icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title mb-0">عمولات معلقة</h6>
                                        <div class="stat-value"><?php echo number_format($stats['pending_commission'], 2); ?> درهم</div>
                                    </div>
                                    <i class="fas fa-clock stat-icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title mb-0">إجمالي النقرات</h6>
                                        <div class="stat-value"><?php echo $stats['total_clicks']; ?></div>
                                    </div>
                                    <i class="fas fa-mouse-pointer stat-icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title mb-0">إجمالي الطلبات</h6>
                                        <div class="stat-value"><?php echo $orderStats['total']; ?></div>
                                    </div>
                                    <i class="fas fa-shopping-bag stat-icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title mb-0">Total payé par les clients</h6>
                                        <div class="stat-value"><?php echo number_format($total_orders_amount, 2); ?> DH</div>
                                    </div>
                                    <i class="fas fa-coins stat-icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section gain actuel et historique des paiements -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title">Gains en attente</h5>
                                <h2 class="card-text text-success"><?php echo number_format($pending_commission, 2); ?> MAD</h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Historique des paiements</h5>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm">
                                        <thead>
                                            <tr>
                                                <th>Montant</th>
                                                <th>Date de paiement</th>
                                                <th>Statut</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($payments_history)): ?>
                                                <tr><td colspan="3" class="text-center">Aucun paiement effectué.</td></tr>
                                            <?php else: ?>
                                                <?php foreach ($payments_history as $pay): ?>
                                                    <tr>
                                                        <td><?php echo number_format($pay['montant'], 2); ?> MAD</td>
                                                        <td><?php echo $pay['date_paiement']; ?></td>
                                                        <td>
                                                            <?php if ($pay['statut'] === 'payé'): ?>
                                                                <span class="badge bg-success">Payé</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-warning text-dark">En attente</span>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Recent Sales -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">آخر المبيعات</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>المنتج</th>
                                                <th>المبلغ</th>
                                                <th>العمولة</th>
                                                <th>الحالة</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recent_sales as $sale): ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <?php if ($sale['image_url']): ?>
                                                                <img src="../<?php echo $sale['image_url']; ?>" alt="<?php echo $sale['product_name']; ?>" class="product-image me-2">
                                                            <?php endif; ?>
                                                            <?php echo $sale['product_name']; ?>
                                                        </div>
                                                    </td>
                                                    <td><?php echo number_format($sale['amount'], 2); ?> درهم</td>
                                                    <td><?php echo number_format($sale['commission'], 2); ?> درهم</td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $sale['status'] === 'completed' ? 'success' : ($sale['status'] === 'pending' ? 'warning' : 'danger'); ?>">
                                                            <?php echo $sale['status'] === 'completed' ? 'مكتمل' : ($sale['status'] === 'pending' ? 'معلق' : 'مسترد'); ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Top Products -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">أفضل المنتجات أداءً</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>المنتج</th>
                                                <th>المبيعات</th>
                                                <th>النقرات</th>
                                                <th>العمولات</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($top_products as $product): ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <?php if ($product['image_url']): ?>
                                                                <img src="../<?php echo $product['image_url']; ?>" alt="<?php echo $product['name']; ?>" class="product-image me-2">
                                                            <?php endif; ?>
                                                            <?php echo $product['name']; ?>
                                                        </div>
                                                    </td>
                                                    <td><?php echo $product['sales_count']; ?></td>
                                                    <td><?php echo $product['total_clicks']; ?></td>
                                                    <td><?php echo number_format($product['total_commission'], 2); ?> درهم</td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ajout : Dernières commandes passées -->
                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Dernières commandes passées</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>N° Commande</th>
                                                <th>Date</th>
                                                <th>Client</th>
                                                <th>Ville</th>
                                                <th>Total</th>
                                                <th>Statut</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recent_orders as $order): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                                                <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                                                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                                <td><?php echo htmlspecialchars($order['customer_city']); ?></td>
                                                <td><?php echo number_format($order['final_sale_price'], 2); ?> Dhs</td>
                                                <td>
                                                    <span class="badge bg-<?php echo $order['status'] === 'delivered' ? 'success' : ($order['status'] === 'confirmed' ? 'primary' : ($order['status'] === 'cancelled' ? 'danger' : 'secondary')); ?>">
                                                        <?php echo ucfirst($order['status']); ?>
                                                    </span>
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

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 