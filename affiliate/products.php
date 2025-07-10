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

// Récupérer les catégories
$stmt = $conn->query("SELECT DISTINCT category FROM products WHERE status = 'active' ORDER BY category");
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Filtres
$category = isset($_GET['category']) ? $_GET['category'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'name_asc';

// Construire la requête
$query = "
    SELECT p.*, 
           (SELECT COUNT(*) FROM affiliate_links al WHERE al.product_id = p.id) as total_affiliates
    FROM products p
    WHERE p.status = 'active'
      AND (
        p.affiliate_visibility = 'all'
        OR (
          p.affiliate_visibility = 'specific'
          AND EXISTS (SELECT 1 FROM product_affiliates pa WHERE pa.product_id = p.id AND pa.affiliate_id = ?)
        )
      )
";
$params = [$user_id];

if ($category) {
    $query .= " AND p.category = ?";
    $params[] = $category;
}

if ($search) {
    $query .= " AND (p.name LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Tri
switch ($sort) {
    case 'price_asc':
        $query .= " ORDER BY p.price ASC";
        break;
    case 'price_desc':
        $query .= " ORDER BY p.price DESC";
        break;
    case 'commission_desc':
        $query .= " ORDER BY p.commission_rate DESC";
        break;
    case 'name_asc':
    default:
        $query .= " ORDER BY p.name ASC";
        break;
}

$stmt = $conn->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Vérifier quels produits sont déjà promus par l'affilié
$stmt = $conn->prepare("SELECT product_id FROM affiliate_links WHERE affiliate_id = ?");
$stmt->execute([$user_id]);
$promoted_products = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>المنتجات - شيك أفلييت</title>
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

        .product-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            height: 100%;
        }

        .product-card:hover {
            transform: translateY(-5px);
        }

        .product-image {
            height: 200px;
            object-fit: cover;
            border-radius: 15px 15px 0 0;
        }

        .product-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(255, 255, 255, 0.9);
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.9rem;
        }

        .commission-badge {
            background: linear-gradient(135deg, var(--accent-color), #c0392b);
            color: white;
        }

        .filter-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }

        .search-input {
            border-radius: 20px;
            padding-left: 40px;
        }

        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
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
                    <a class="nav-link active" href="products.php">
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
                <h2 class="mb-4">المنتجات المتاحة</h2>

                <!-- Filters -->
                <div class="filter-card">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <div class="position-relative">
                                <i class="fas fa-search search-icon"></i>
                                <input type="text" class="form-control search-input" name="search" placeholder="ابحث عن منتج..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="category">
                                <option value="">جميع الفئات</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat; ?>" <?php echo $category === $cat ? 'selected' : ''; ?>>
                                        <?php echo $cat; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="sort">
                                <option value="name_asc" <?php echo $sort === 'name_asc' ? 'selected' : ''; ?>>الاسم (أ-ي)</option>
                                <option value="price_asc" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>>السعر (منخفض-مرتفع)</option>
                                <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>السعر (مرتفع-منخفض)</option>
                                <option value="commission_desc" <?php echo $sort === 'commission_desc' ? 'selected' : ''; ?>>أعلى عمولة</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">تصفية</button>
                        </div>
                    </form>
                </div>

                <!-- Products Grid -->
                <div class="row">
                    <?php foreach ($products as $product): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card product-card">
                            <?php if ($product['image_url']): ?>
                                <img src="../<?php echo $product['image_url']; ?>" class="product-image" alt="<?php echo $product['name']; ?>">
                            <?php endif; ?>
                            
                            <div class="product-badge commission-badge">
                                <?php echo $product['commission_rate']; ?>% عمولة
                            </div>
                            
                            <div class="card-body">
                                <h5 class="card-title"><?php echo $product['name']; ?></h5>
                                <p class="card-text text-muted"><?php echo substr($product['description'], 0, 100); ?>...</p>
                                
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="h5 mb-0"><?php echo number_format($product['price'], 2); ?> درهم</span>
                                    <small class="text-muted">
                                        <i class="fas fa-users me-1"></i>
                                        <?php echo $product['total_affiliates']; ?> مسوق
                                    </small>
                                </div>

                                <?php if (in_array($product['id'], $promoted_products)): ?>
                                    <button class="btn btn-success w-100" disabled>
                                        <i class="fas fa-check me-2"></i>تمت الإضافة
                                    </button>
                                <?php else: ?>
                                    <form method="POST" action="links.php">
                                        <input type="hidden" name="action" value="create_link">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="fas fa-plus me-2"></i>إضافة للترويج
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 