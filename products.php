<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

require_once 'config/database.php';

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

// Récupération des catégories pour le filtre
$categories = $conn->query("SELECT * FROM categories WHERE status = 'active' ORDER BY name")->fetchAll();

// Gestion des filtres
$category_filter = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'name_asc';

// Construction de la requête simplifiée
$query = "
    SELECT p.*, c.name as category_name,
           (SELECT image_url FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC, sort_order ASC, id ASC LIMIT 1) as main_image_url,
           COUNT(DISTINCT o.id) as total_orders
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN order_items oi ON p.id = oi.product_id
    LEFT JOIN orders o ON oi.order_id = o.id
    WHERE p.status = 'active'
";
$params = [];

if ($category_filter) {
    $query .= " AND p.category_id = ?";
    $params[] = $category_filter;
}

if ($search) {
    $query .= " AND (p.name LIKE ? OR p.description LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
}

$query .= " GROUP BY p.id";

// Gestion du tri
switch ($sort) {
    case 'price_asc':
        $query .= " ORDER BY p.reseller_price ASC";
        break;
    case 'price_desc':
        $query .= " ORDER BY p.reseller_price DESC";
        break;
    case 'popularity':
        $query .= " ORDER BY total_orders DESC";
        break;
    case 'name_desc':
        $query .= " ORDER BY p.name DESC";
        break;
    default: // name_asc
        $query .= " ORDER BY p.name ASC";
}

$stmt = $conn->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nos Produits</title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        body {
            background: #f7f7f7;
        }
        .products-container {
            display: flex;
            flex-wrap: wrap;
            gap: 2rem;
            justify-content: flex-start;
            align-items: flex-start;
            margin-top: 2rem;
        }
        .product-card {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 2px 12px rgba(44,62,80,0.08);
            width: 270px;
            display: flex;
            flex-direction: column;
            margin-bottom: 2rem;
            transition: box-shadow 0.2s, transform 0.2s;
        }
        .product-card:hover {
            box-shadow: 0 6px 24px rgba(44,62,80,0.13);
            transform: translateY(-4px) scale(1.02);
        }
        .product-image {
            width: 100%;
            height: 270px;
            object-fit: cover;
            border-radius: 14px 14px 0 0;
            background: #f0f0f0;
        }
        .product-info {
            padding: 1.1rem 1.2rem 1.2rem 1.2rem;
            display: flex;
            flex-direction: column;
            flex: 1;
        }
        .product-title {
            font-size: 1.08rem;
            font-weight: 600;
            color: #222;
            margin-bottom: 0.3rem;
            min-height: 2.2em;
        }
        .product-desc {
            color: #888;
            font-size: 0.98rem;
            margin-bottom: 0.7rem;
            min-height: 2.1em;
        }
        .product-price {
            font-size: 1.08rem;
            font-weight: 700;
            color: #1976d2;
            margin-bottom: 0.7rem;
        }
        .btn-details {
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
        }
        .btn-details:hover {
            background: #1976d2;
            color: #fff;
        }
        @media (max-width: 1200px) {
            .products-container { gap: 1.2rem; }
            .product-card { width: 220px; }
            .product-image { height: 180px; }
        }
        @media (max-width: 900px) {
            .products-container { justify-content: center; }
            .product-card { width: 90vw; max-width: 350px; }
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
                            <i class="fas fa-box-open" style="color:#1976d2;"></i> Nos Produits
                        </h1>
                    </div>
                    <div class="col-md-6">
                        <form class="d-flex gap-2" style="justify-content:flex-end;">
                            <input type="text" name="search" class="form-control" placeholder="Rechercher un produit..." value="<?php echo htmlspecialchars($search); ?>">
                            <select name="category" class="form-select" style="width:auto;">
                                <option value="">Toutes les catégories</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <select name="sort" class="form-select" style="width:auto;">
                                <option value="name_asc" <?php echo $sort === 'name_asc' ? 'selected' : ''; ?>>Nom (A-Z)</option>
                                <option value="name_desc" <?php echo $sort === 'name_desc' ? 'selected' : ''; ?>>Nom (Z-A)</option>
                                <option value="price_asc" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>>Prix croissant</option>
                                <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>Prix décroissant</option>
                                <option value="popularity" <?php echo $sort === 'popularity' ? 'selected' : ''; ?>>Popularité</option>
                            </select>
                            <button type="submit" class="btn btn-primary" style="padding:0.5rem 1.2rem;">
                                <i class="fas fa-search"></i> Filtrer
                            </button>
                        </form>
                    </div>
                </div>

                <?php if (empty($products)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> Aucun produit ne correspond à vos critères de recherche.
                </div>
                <?php else: ?>
                <div class="products-container">
                    <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <a href="product_details.php?id=<?php echo htmlspecialchars($product['id']); ?>">
                            <img src="<?php echo htmlspecialchars($product['main_image_url']); ?>" class="product-image" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        </a>
                        <div class="product-info">
                            <div class="product-title"><?php echo htmlspecialchars($product['name']); ?></div>
                            <div class="product-desc"><?php echo htmlspecialchars(substr($product['description'], 0, 70)); ?>...</div>
                            <div class="product-price"><?php echo number_format($product['seller_price'] ?? 0, 2); ?> MAD</div>
                            <a href="product_details.php?id=<?php echo htmlspecialchars($product['id']); ?>" class="btn-details">Voir détails</a>
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
    <script>
        // Auto-submit du formulaire lors du changement des filtres
        document.querySelectorAll('select[name="category"], select[name="sort"]').forEach(select => {
            select.addEventListener('change', () => {
                select.closest('form').submit();
            });
        });
    </script>
</body>
</html> 