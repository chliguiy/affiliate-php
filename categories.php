<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

require_once 'config/database.php';
require_once 'includes/functions.php';

// Récupérer les informations de l'utilisateur
$database = new Database();
$conn = $database->getConnection();

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header('Location: index.php');
    exit();
}

// Récupérer toutes les catégories actives
$stmt = $conn->query("SELECT * FROM categories WHERE status = 'active' ORDER BY name ASC");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer le nombre de produits par catégorie
$cat_ids = array_column($categories, 'id');
$product_counts = [];
if ($cat_ids) {
    $in = str_repeat('?,', count($cat_ids) - 1) . '?';
    $stmt2 = $conn->prepare("SELECT category_id, COUNT(*) as nb FROM products WHERE status = 'active' AND category_id IN ($in) GROUP BY category_id");
    $stmt2->execute($cat_ids);
    foreach ($stmt2->fetchAll() as $row) {
        $product_counts[$row['category_id']] = $row['nb'];
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nos Catégories</title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        body {
            background: #f7f7f7;
        }
        .categories-container {
            display: flex;
            flex-wrap: wrap;
            gap: 2rem;
            justify-content: flex-start;
            align-items: flex-start;
            margin-top: 2rem;
        }
        .category-card {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 2px 12px rgba(44,62,80,0.08);
            width: 270px;
            display: flex;
            flex-direction: column;
            margin-bottom: 2rem;
            transition: box-shadow 0.2s, transform 0.2s;
            text-decoration: none;
            color: inherit;
        }
        .category-card:hover {
            box-shadow: 0 6px 24px rgba(44,62,80,0.13);
            transform: translateY(-4px) scale(1.02);
            text-decoration: none;
            color: inherit;
        }
        .category-image {
            width: 100%;
            height: 270px;
            object-fit: cover;
            border-radius: 14px 14px 0 0;
            background: #f0f0f0;
        }
        .category-info {
            padding: 1.1rem 1.2rem 1.2rem 1.2rem;
            display: flex;
            flex-direction: column;
            flex: 1;
        }
        .category-title {
            font-size: 1.08rem;
            font-weight: 600;
            color: #222;
            margin-bottom: 0.3rem;
            min-height: 2.2em;
        }
        .category-desc {
            color: #888;
            font-size: 0.98rem;
            margin-bottom: 0.7rem;
            min-height: 2.1em;
        }
        .category-count {
            font-size: 1.08rem;
            font-weight: 700;
            color: #1976d2;
            margin-bottom: 0.7rem;
        }
        .btn-category {
            background: #f7f7f7;
            color: #1976d2;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 0.5rem 1.2rem;
            font-size: 1rem;
            font-weight: 500;
            text-decoration: none;
            transition: background 0.2s, color 0.2s;
            margin-top: auto;
            text-align: center;
        }
        .btn-category:hover {
            background: #1976d2;
            color: #fff;
            text-decoration: none;
        }
        @media (max-width: 1200px) {
            .categories-container { gap: 1.2rem; }
            .category-card { width: 220px; }
            .category-image { height: 180px; }
        }
        @media (max-width: 900px) {
            .categories-container { justify-content: center; }
            .category-card { width: 90vw; max-width: 350px; }
        }
    </style>
</head>
<body>
<?php include 'includes/topbar.php'; ?>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 p-0">
                <?php include 'includes/sidebar.php'; ?>
            </div>
            <!-- Main content -->
            <div class="col-md-10 p-4">
                <div class="row mb-4 align-items-center">
                    <div class="col-md-6">
                        <h1 style="font-weight:700; color:#222; font-size:2rem; margin-bottom:0.5rem;">
                            <i class="fas fa-tags" style="color:#1976d2;"></i> Nos Catégories
                        </h1>
                    </div>
                </div>

                <?php if (empty($categories)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> Aucune catégorie disponible pour le moment.
                </div>
                <?php else: ?>
                <div class="categories-container">
                    <?php foreach ($categories as $category): ?>
                    <div class="category-card">
                        <a href="products.php?category=<?php echo htmlspecialchars($category['id']); ?>">
                            <?php if (!empty($category['image'])): ?>
                                <img src="uploads/categories/<?php echo htmlspecialchars($category['image']); ?>" class="category-image" alt="<?php echo htmlspecialchars($category['name']); ?>">
                            <?php else: ?>
                                <div class="category-image d-flex align-items-center justify-content-center">
                                    <i class="fas fa-image fa-3x text-muted"></i>
                                </div>
                            <?php endif; ?>
                        </a>
                        <div class="category-info">
                            <div class="category-title"><?php echo htmlspecialchars($category['name']); ?></div>
                            <div class="category-desc"><?php echo htmlspecialchars(substr($category['description'], 0, 70)); ?>...</div>
                            <div class="category-count"><?php echo $product_counts[$category['id']] ?? 0; ?> produits</div>
                            <a href="products.php?category=<?php echo htmlspecialchars($category['id']); ?>" class="btn-category">Voir les produits</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 
