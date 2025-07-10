<?php
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

// Période de filtrage
$period = isset($_GET['period']) ? $_GET['period'] : 'month';
$start_date = date('Y-m-d');
$end_date = date('Y-m-d');

switch ($period) {
    case 'week':
        $start_date = date('Y-m-d', strtotime('-7 days'));
        break;
    case 'month':
        $start_date = date('Y-m-d', strtotime('-30 days'));
        break;
    case 'year':
        $start_date = date('Y-m-d', strtotime('-365 days'));
        break;
}

// Statistiques globales (basées sur les commandes)
$stmt = $conn->prepare("
    SELECT 
        COUNT(DISTINCT o.id) as total_sales,
        SUM(o.final_sale_price) as total_amount,
        SUM(o.commission_amount) as total_commission
    FROM orders o
    WHERE o.affiliate_id = ? AND o.status = 'delivered' AND o.created_at BETWEEN ? AND ?
");
$stmt->execute([$user_id, $start_date, $end_date]);
$stats = $stmt->fetch();

// Ventes par produit (basées sur les commandes)
$stmt = $conn->prepare("
    SELECT 
        p.name as product_name,
        p.image_url,
        COUNT(DISTINCT o.id) as sales_count,
        SUM(o.final_sale_price) as total_amount,
        SUM(o.commission_amount) as total_commission
    FROM products p
    JOIN affiliate_links al ON p.id = al.product_id
    LEFT JOIN order_items oi ON p.id = oi.product_id
    LEFT JOIN orders o ON oi.order_id = o.id AND o.affiliate_id = ? AND o.created_at BETWEEN ? AND ?
    WHERE al.affiliate_id = ?
    GROUP BY p.id
    ORDER BY total_commission DESC
");
$stmt->execute([$user_id, $start_date, $end_date, $user_id]);
$product_sales = $stmt->fetchAll();

// Historique des ventes (commandes)
$stmt = $conn->prepare("
    SELECT 
        o.*,
        COUNT(oi.id) as product_count
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE o.affiliate_id = ?
    AND o.created_at BETWEEN ? AND ?
    GROUP BY o.id
    ORDER BY o.created_at DESC
    LIMIT 10
");
$stmt->execute([$user_id, $start_date, $end_date]);
$recent_sales = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>المبيعات - شيك أفلييت</title>
    <!-- Bootstrap 5 RTL CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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

        .stats-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
            transition: transform 0.3s ease;
        }

        .stats-card:hover {
            transform: translateY(-5px);
        }

        .stats-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 15px;
        }

        .stats-value {
            font-size: 1.8rem;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .stats-label {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .product-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 8px;
        }

        .table th {
            font-weight: 600;
            color: var(--primary-color);
        }

        .period-selector {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 15px;
            margin-bottom: 20px;
        }

        .period-btn {
            border: none;
            background: none;
            padding: 8px 15px;
            border-radius: 20px;
            color: #6c757d;
            transition: all 0.3s ease;
        }

        .period-btn.active {
            background-color: var(--primary-color);
            color: white;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <h4 class="mb-4">لوحة التحكم</h4>
                <nav class="nav flex-column">
                    <a class="nav-link" href="dashboard.php">
                        <i class="fas fa-home me-2"></i> الرئيسية
                    </a>
                    <a class="nav-link" href="products.php">
                        <i class="fas fa-box me-2"></i> المنتجات
                    </a>
                    <a class="nav-link" href="links.php">
                        <i class="fas fa-link me-2"></i> روابط التسويق
                    </a>
                    <a class="nav-link active" href="sales.php">
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
                <h2 class="mb-4">المبيعات والعمولات</h2>

                <!-- Period Selector -->
                <div class="period-selector">
                    <div class="btn-group">
                        <a href="?period=week" class="period-btn <?php echo $period === 'week' ? 'active' : ''; ?>">
                            آخر 7 أيام
                        </a>
                        <a href="?period=month" class="period-btn <?php echo $period === 'month' ? 'active' : ''; ?>">
                            آخر 30 يوم
                        </a>
                        <a href="?period=year" class="period-btn <?php echo $period === 'year' ? 'active' : ''; ?>">
                            آخر سنة
                        </a>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="row">
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="stats-icon" style="background-color: rgba(52, 152, 219, 0.1); color: #3498db;">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                            <div class="stats-value"><?php echo $stats['total_sales']; ?></div>
                            <div class="stats-label">إجمالي المبيعات</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="stats-icon" style="background-color: rgba(46, 204, 113, 0.1); color: #2ecc71;">
                                <i class="fas fa-money-bill-wave"></i>
                            </div>
                            <div class="stats-value"><?php echo number_format($stats['total_amount'], 2); ?> درهم</div>
                            <div class="stats-label">إجمالي المبيعات</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="stats-icon" style="background-color: rgba(155, 89, 182, 0.1); color: #9b59b6;">
                                <i class="fas fa-hand-holding-usd"></i>
                            </div>
                            <div class="stats-value"><?php echo number_format($stats['total_commission'], 2); ?> درهم</div>
                            <div class="stats-label">إجمالي العمولات</div>
                        </div>
                    </div>
                </div>

                <!-- Product Sales -->
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">المبيعات حسب المنتج</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>المنتج</th>
                                        <th>المبيعات</th>
                                        <th>إجمالي المبيعات</th>
                                        <th>العمولات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($product_sales as $sale): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if ($sale['image_url']): ?>
                                                    <img src="../<?php echo $sale['image_url']; ?>" class="product-image me-3" alt="<?php echo $sale['product_name']; ?>">
                                                <?php endif; ?>
                                                <?php echo $sale['product_name']; ?>
                                            </div>
                                        </td>
                                        <td><?php echo $sale['sales_count']; ?></td>
                                        <td><?php echo number_format($sale['total_amount'], 2); ?> درهم</td>
                                        <td><?php echo number_format($sale['total_commission'], 2); ?> درهم</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Recent Sales -->
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">آخر المبيعات</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>رقم الطلب</th>
                                        <th>العميل</th>
                                        <th>المبلغ النهائي</th>
                                        <th>العمولة (المارجن)</th>
                                        <th>التاريخ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_sales as $sale): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo $sale['order_number']; ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($sale['customer_name']); ?></td>
                                        <td><?php echo number_format($sale['final_sale_price'], 2); ?> درهم</td>
                                        <td>
                                            <strong><?php echo number_format($sale['commission_amount'], 2); ?> درهم</strong>
                                            <br>
                                            <small class="text-muted">المارجن: <?php echo number_format($sale['affiliate_margin'], 2); ?> درهم</small>
                                        </td>
                                        <td><?php echo date('Y/m/d H:i', strtotime($sale['created_at'])); ?></td>
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

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 