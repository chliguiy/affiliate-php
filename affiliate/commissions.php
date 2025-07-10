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

// Statistiques des commissions (basées sur la marge affiliée)
$stmt = $conn->prepare("
    SELECT 
        SUM(CASE WHEN o.status = 'delivered' THEN o.commission_amount ELSE 0 END) as paid_commission,
        COUNT(DISTINCT CASE WHEN o.status = 'delivered' THEN o.id END) as paid_sales
    FROM orders o
    WHERE o.affiliate_id = ?
    AND o.created_at BETWEEN ? AND ?
");
$stmt->execute([$user_id, $start_date, $end_date]);
$stats = $stmt->fetch();

// Commissions par statut (basées sur la marge affiliée)
$stmt = $conn->prepare("
    SELECT 
        o.id,
        o.order_number,
        o.customer_name,
        o.total_amount,
        o.commission_amount,
        o.affiliate_margin,
        o.final_sale_price,
        o.delivery_fee,
        o.status,
        o.created_at,
        DATE_FORMAT(o.created_at, '%Y-%m-%d') as sale_date
    FROM orders o
    WHERE o.affiliate_id = ?
    AND o.created_at BETWEEN ? AND ?
    ORDER BY o.created_at DESC
");
$stmt->execute([$user_id, $start_date, $end_date]);
$commissions = $stmt->fetchAll();

// Grouper les commissions par date
$grouped_commissions = [];
foreach ($commissions as $commission) {
    $date = $commission['sale_date'];
    if (!isset($grouped_commissions[$date])) {
        $grouped_commissions[$date] = [
            'date' => $date,
            'commissions' => [],
            'total' => 0
        ];
    }
    $grouped_commissions[$date]['commissions'][] = $commission;
    $grouped_commissions[$date]['total'] += $commission['commission'];
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>العمولات - شيك أفلييت</title>
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

        .commission-group {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }

        .commission-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .commission-body {
            padding: 20px;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-pending {
            background-color: #f1c40f;
            color: #fff;
        }

        .status-approved {
            background-color: #2ecc71;
            color: #fff;
        }

        .status-paid {
            background-color: #3498db;
            color: #fff;
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
                    <a class="nav-link" href="sales.php">
                        <i class="fas fa-chart-line me-2"></i> المبيعات
                    </a>
                    <a class="nav-link active" href="commissions.php">
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
                <h2 class="mb-4">العمولات</h2>

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
                    <div class="col-md-4">
                        <div class="stats-card">
                            <div class="stats-icon" style="background-color: rgba(241, 196, 15, 0.1); color: #f1c40f;">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stats-value"><?php echo number_format($stats['paid_commission'], 2); ?> درهم</div>
                            <div class="stats-label">عمولات معلقة (<?php echo $stats['paid_sales']; ?> عملية)</div>
                        </div>
                    </div>
                </div>

                <!-- Commissions by Date -->
                <?php foreach ($grouped_commissions as $group): ?>
                <div class="commission-group">
                    <div class="commission-header">
                        <div>
                            <h5 class="mb-0"><?php echo date('d/m/Y', strtotime($group['date'])); ?></h5>
                        </div>
                        <div>
                            <span class="h5 mb-0"><?php echo number_format($group['total'], 2); ?> درهم</span>
                        </div>
                    </div>
                    <div class="commission-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>رقم الطلب</th>
                                        <th>العميل</th>
                                        <th>المبلغ النهائي</th>
                                        <th>العمولة (المارجن)</th>
                                        <th>الحالة</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($group['commissions'] as $commission): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo $commission['order_number']; ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($commission['customer_name']); ?></td>
                                        <td><?php echo number_format($commission['final_sale_price'], 2); ?> درهم</td>
                                        <td>
                                            <strong><?php echo number_format($commission['commission_amount'], 2); ?> درهم</strong>
                                            <br>
                                            <small class="text-muted">المارجن: <?php echo number_format($commission['affiliate_margin'], 2); ?> درهم</small>
                                        </td>
                                        <td>
                                            <?php
                                            $status_class = '';
                                            $status_text = '';
                                            switch ($commission['status']) {
                                                case 'new':
                                                    $status_class = 'status-pending';
                                                    $status_text = 'جديد';
                                                    break;
                                                case 'confirmed':
                                                    $status_class = 'status-approved';
                                                    $status_text = 'مؤكد';
                                                    break;
                                                case 'delivered':
                                                    $status_class = 'status-paid';
                                                    $status_text = 'تم التسليم';
                                                    break;
                                                default:
                                                    $status_class = 'status-pending';
                                                    $status_text = $commission['status'];
                                            }
                                            ?>
                                            <span class="status-badge <?php echo $status_class; ?>">
                                                <?php echo $status_text; ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 