<?php
session_start();
require_once '../config/database.php';

// Vérification de l'authentification et du rôle
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'vendor') {
    header('Location: ../login.php');
    exit;
}

// Récupération des statistiques
$vendor_id = $_SESSION['user_id'];

// Nombre total de produits
$stmt = $conn->prepare("SELECT COUNT(*) FROM products WHERE vendor_id = ?");
$stmt->execute([$vendor_id]);
$total_products = $stmt->fetchColumn();

// Nombre total de ventes
$stmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM sales s 
    JOIN products p ON s.product_id = p.id 
    WHERE p.vendor_id = ?
");
$stmt->execute([$vendor_id]);
$total_sales = $stmt->fetchColumn();

// Chiffre d'affaires total
$stmt = $conn->prepare("
    SELECT SUM(s.amount) 
    FROM sales s 
    JOIN products p ON s.product_id = p.id 
    WHERE p.vendor_id = ? AND s.status = 'completed'
");
$stmt->execute([$vendor_id]);
$total_revenue = $stmt->fetchColumn() ?: 0;

// Récupération des produits
$stmt = $conn->prepare("
    SELECT p.*, 
           COUNT(s.id) as total_sales,
           SUM(CASE WHEN s.status = 'completed' THEN s.amount ELSE 0 END) as revenue
    FROM products p
    LEFT JOIN sales s ON p.id = s.product_id
    WHERE p.vendor_id = ?
    GROUP BY p.id
    ORDER BY p.created_at DESC
");
$stmt->execute([$vendor_id]);
$products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord Vendeur - Chic Affiliate</title>
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
        }
        .product-card {
            transition: transform 0.2s;
        }
        .product-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar p-3">
                <h4 class="mb-4">Chic Affiliate</h4>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="fas fa-home me-2"></i> Tableau de bord
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="products.php">
                            <i class="fas fa-box me-2"></i> Produits
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="sales.php">
                            <i class="fas fa-chart-line me-2"></i> Ventes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i> Déconnexion
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Tableau de bord</h2>
                    <a href="add_product.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Ajouter un produit
                    </a>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card stat-card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title">Produits</h5>
                                <h2 class="mb-0"><?php echo $total_products; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stat-card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title">Ventes totales</h5>
                                <h2 class="mb-0"><?php echo $total_sales; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stat-card bg-info text-white">
                            <div class="card-body">
                                <h5 class="card-title">Chiffre d'affaires</h5>
                                <h2 class="mb-0"><?php echo number_format($total_revenue, 2); ?> €</h2>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Products List -->
                <h3 class="mb-4">Vos produits</h3>
                <div class="row">
                    <?php foreach ($products as $product): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card product-card h-100">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                                <p class="card-text text-muted">
                                    <?php echo substr(htmlspecialchars($product['description']), 0, 100) . '...'; ?>
                                </p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="h5 mb-0"><?php echo number_format($product['price'], 2); ?> €</span>
                                    <span class="badge bg-<?php echo $product['status'] === 'active' ? 'success' : 'warning'; ?>">
                                        <?php echo $product['status']; ?>
                                    </span>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between text-muted">
                                    <small>Ventes: <?php echo $product['total_sales']; ?></small>
                                    <small>CA: <?php echo number_format($product['revenue'], 2); ?> €</small>
                                </div>
                            </div>
                            <div class="card-footer bg-white">
                                <div class="btn-group w-100">
                                    <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="btn btn-outline-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="product_stats.php?id=<?php echo $product['id']; ?>" class="btn btn-outline-info">
                                        <i class="fas fa-chart-bar"></i>
                                    </a>
                                    <button class="btn btn-outline-danger" onclick="deleteProduct(<?php echo $product['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function deleteProduct(productId) {
            if (confirm('Êtes-vous sûr de vouloir supprimer ce produit ?')) {
                window.location.href = `delete_product.php?id=${productId}`;
            }
        }
    </script>
</body>
</html> 