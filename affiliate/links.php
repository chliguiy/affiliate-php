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

// Traitement de la création d'un nouveau lien
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_link') {
    $product_id = $_POST['product_id'];
    
    // Vérifier si le lien existe déjà
    $stmt = $conn->prepare("SELECT id FROM affiliate_links WHERE affiliate_id = ? AND product_id = ?");
    $stmt->execute([$user_id, $product_id]);
    
    if (!$stmt->fetch()) {
        // Générer un code unique
        $unique_code = uniqid('ref_');
        
        // Créer le lien
        $stmt = $conn->prepare("INSERT INTO affiliate_links (affiliate_id, product_id, unique_code) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $product_id, $unique_code]);
    }
}

// Récupérer les liens d'affiliation avec statistiques
$stmt = $conn->prepare("
    SELECT 
        al.*,
        p.name as product_name,
        p.price,
        p.commission_rate,
        p.image_url,
        COUNT(s.id) as total_sales,
        SUM(s.commission) as total_commission
    FROM affiliate_links al
    JOIN products p ON al.product_id = p.id
    LEFT JOIN sales s ON al.id = s.affiliate_id
    WHERE al.affiliate_id = ?
    GROUP BY al.id
    ORDER BY al.created_at DESC
");
$stmt->execute([$user_id]);
$affiliate_links = $stmt->fetchAll();

// Récupérer les produits disponibles
$stmt = $conn->prepare("
    SELECT p.*
    FROM products p
    WHERE p.status = 'active'
    AND NOT EXISTS (
        SELECT 1 FROM affiliate_links al 
        WHERE al.affiliate_id = ? AND al.product_id = p.id
    )
");
$stmt->execute([$user_id]);
$available_products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>روابط التسويق - شيك أفلييت</title>
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

        .product-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 10px;
        }

        .link-card {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }

        .link-card .card-body {
            padding: 1.5rem;
        }

        .link-input {
            background-color: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
        }

        .link-input:focus {
            background-color: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.3);
            color: white;
            box-shadow: none;
        }

        .link-input::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        .btn-copy {
            background-color: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
        }

        .btn-copy:hover {
            background-color: rgba(255, 255, 255, 0.3);
            color: white;
        }

        .stats-badge {
            background-color: rgba(255, 255, 255, 0.2);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
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
                    <a class="nav-link active" href="links.php">
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
                    <h2>روابط التسويق</h2>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addLinkModal">
                        <i class="fas fa-plus me-2"></i>إضافة رابط جديد
                    </button>
                </div>

                <!-- Affiliate Links -->
                <div class="row">
                    <?php foreach ($affiliate_links as $link): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card link-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <?php if ($link['image_url']): ?>
                                        <img src="../<?php echo $link['image_url']; ?>" alt="<?php echo $link['product_name']; ?>" class="product-image me-3">
                                    <?php endif; ?>
                                    <div>
                                        <h5 class="card-title mb-1"><?php echo $link['product_name']; ?></h5>
                                        <p class="mb-0">نسبة العمولة: <?php echo $link['commission_rate']; ?>%</p>
                                    </div>
                                </div>
                                
                                <div class="input-group mb-3">
                                    <input type="text" class="form-control link-input" value="<?php echo "https://chic-affiliate.com/ref/{$link['unique_code']}"; ?>" readonly>
                                    <button class="btn btn-copy" onclick="copyToClipboard(this)">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <span class="stats-badge">
                                        <i class="fas fa-mouse-pointer me-1"></i>
                                        <?php echo $link['clicks']; ?> نقرة
                                    </span>
                                    <span class="stats-badge">
                                        <i class="fas fa-shopping-cart me-1"></i>
                                        <?php echo $link['total_sales']; ?> عملية بيع
                                    </span>
                                    <span class="stats-badge">
                                        <i class="fas fa-money-bill-wave me-1"></i>
                                        <?php echo number_format($link['total_commission'], 2); ?> درهم
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Link Modal -->
    <div class="modal fade" id="addLinkModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">إضافة رابط تسويق جديد</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create_link">
                        
                        <div class="mb-3">
                            <label class="form-label">اختر المنتج</label>
                            <select class="form-select" name="product_id" required>
                                <option value="">اختر منتجاً...</option>
                                <?php foreach ($available_products as $product): ?>
                                    <option value="<?php echo $product['id']; ?>">
                                        <?php echo $product['name']; ?> - <?php echo number_format($product['price'], 2); ?> درهم (<?php echo $product['commission_rate']; ?>% عمولة)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-primary">إضافة</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function copyToClipboard(button) {
            const input = button.previousElementSibling;
            input.select();
            document.execCommand('copy');
            
            // Feedback visuel
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-check"></i>';
            setTimeout(() => {
                button.innerHTML = originalText;
            }, 2000);
        }
    </script>
</body>
</html> 