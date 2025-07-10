<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/database.php';
require_once 'includes/auth.php';

// Connexion à la base de données
$database = new Database();
$conn = $database->getConnection();

// Gestion des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                try {
                    $conn->beginTransaction();

                    // Génération du slug
                    $base_slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $_POST['name'])));
                    $slug = $base_slug;
                    $counter = 1;
                    
                    // Vérification si le slug existe déjà
                    $stmt = $conn->prepare("SELECT COUNT(*) FROM products WHERE name = ?");
                    $stmt->execute([$_POST['name']]);
                    if ($stmt->fetchColumn() > 0) {
                        $slug = $base_slug . '-' . uniqid();
                    }

                    // Insertion du produit principal
                    $stmt = $conn->prepare("
                        INSERT INTO products (
                            name, slug, description, seller_price, reseller_price,
                            category_id, status, has_discount, discount_price,
                            affiliate_visibility
                        ) VALUES (
                            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                        )
                    ");
                    
                    if (!$stmt->execute([
                        $_POST['name'],
                        $slug,
                        $_POST['description'],
                        $_POST['seller_price'],
                        $_POST['reseller_price'],
                        $_POST['category_id'],
                        $_POST['status'],
                        isset($_POST['has_discount']) ? 1 : 0,
                        $_POST['discount_price'] ?? null,
                        $_POST['affiliate_visibility']
                    ])) {
                        throw new Exception("Erreur lors de l'insertion du produit");
                    }

                    $product_id = $conn->lastInsertId();

                    // Gestion des couleurs et stocks
                    if (isset($_POST['colors']) && isset($_POST['color_stocks'])) {
                        $stmt = $conn->prepare("
                            INSERT INTO product_colors (product_id, color_code, stock)
                            VALUES (?, ?, ?)
                        ");
                        foreach ($_POST['colors'] as $key => $color) {
                            $stock = $_POST['color_stocks'][$key];
                            if (!$stmt->execute([$product_id, $color, $stock])) {
                                throw new Exception("Erreur lors de l'ajout des couleurs");
                            }
                        }
                    }

                    // Gestion des tailles et stocks
                    if (isset($_POST['sizes']) && isset($_POST['size_stocks'])) {
                        $stmt = $conn->prepare("
                            INSERT INTO product_sizes (product_id, size, stock)
                            VALUES (?, ?, ?)
                        ");
                        foreach ($_POST['sizes'] as $key => $size) {
                            $stock = $_POST['size_stocks'][$key];
                            if (!$stmt->execute([$product_id, $size, $stock])) {
                                throw new Exception("Erreur lors de l'ajout des tailles");
                            }
                        }
                    }

                    // Gestion des affiliés spécifiques
                    if ($_POST['affiliate_visibility'] === 'specific' && isset($_POST['selected_affiliates'])) {
                        $stmt = $conn->prepare("
                            INSERT INTO product_affiliates (product_id, affiliate_id)
                            VALUES (?, ?)
                        ");
                        foreach ($_POST['selected_affiliates'] as $affiliate_id) {
                            if (!$stmt->execute([$product_id, $affiliate_id])) {
                                throw new Exception("Erreur lors de l'ajout des affiliés");
                            }
                        }
                    }

                    // Gestion des images
                    if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
                        $upload_dir = '../uploads/products/';
                        if (!file_exists($upload_dir)) {
                            mkdir($upload_dir, 0777, true);
                        }

                        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                            $file_name = $_FILES['images']['name'][$key];
                            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                            $new_file_name = uniqid() . '.' . $file_ext;
                            $file_path = $upload_dir . $new_file_name;

                            if (move_uploaded_file($tmp_name, $file_path)) {
                                $stmt = $conn->prepare("INSERT INTO product_images (product_id, image_url) VALUES (?, ?)");
                                if (!$stmt->execute([$product_id, 'uploads/products/' . $new_file_name])) {
                                    throw new Exception("Erreur lors de l'ajout des images");
                                }
                            }
                        }
                    }

                    $conn->commit();
                    $_SESSION['success_message'] = "Produit ajouté avec succès.";
                    header('Location: products.php');
                    exit;
                } catch (Exception $e) {
                    $conn->rollBack();
                    $_SESSION['error_message'] = $e->getMessage();
                    header('Location: products.php');
                    exit;
                }
                break;
        }
    }
}

// Récupération des catégories pour le formulaire
$categories = $conn->query("SELECT * FROM categories WHERE status = 'active'")->fetchAll();

// Récupération des couleurs et tailles existantes
// Supprimer ces lignes au début du fichier
$existing_colors = $conn->query("SELECT DISTINCT color_code FROM product_colors")->fetchAll();
$existing_sizes = $conn->query("SELECT DISTINCT size FROM product_sizes")->fetchAll();

// Récupération des produits
$query = "
    SELECT p.*, c.name as category_name,
           COUNT(DISTINCT o.id) as total_orders,
           SUM(oi.quantity) as total_sold
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN order_items oi ON p.id = oi.product_id
    LEFT JOIN orders o ON oi.order_id = o.id
    GROUP BY p.id
    ORDER BY p.created_at DESC
";
$products = $conn->query($query)->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Produits - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background-color: #f8f9fa;
            border-right: 1px solid #dee2e6;
        }
        .product-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
        }
        .color-preview {
            border: 1px solid #ddd;
        }
        .stock-badge {
            font-size: 0.8rem;
            padding: 2px 6px;
            background-color: #f8f9fa;
            border-radius: 4px;
            color: #666;
        }
        .size-badge {
            font-size: 0.8rem;
            padding: 2px 6px;
            background-color: #e9ecef;
            border-radius: 4px;
            color: #495057;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'includes/sidebar.php'; ?>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 p-4">
                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php 
                        echo $_SESSION['error_message'];
                        unset($_SESSION['error_message']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php 
                        echo $_SESSION['success_message'];
                        unset($_SESSION['success_message']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Gestion des Produits</h2>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                        <i class="fas fa-plus me-2"></i>Ajouter un produit
                    </button>
                </div>

                <!-- Liste des produits -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Image</th>
                                        <th>Nom</th>
                                        <th>Catégorie</th>
                                        <th>Prix Vendeur</th>
                                        <th>Prix Revendeur</th>
                                        <th>Profit</th>
                                        <th>Réduction</th>
                                        <th>Disponibilité</th>
                                        <th>Couleurs</th>
                                        <th>Tailles</th>
                                        <th>Stock</th>
                                        <th>Ventes</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($products as $product): ?>
                                    <tr>
                                        <td>
                                            <div class="product-image bg-light d-flex align-items-center justify-content-center">
                                                <i class="fas fa-image text-muted"></i>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                                        <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                                        <td><?php echo number_format($product['seller_price'], 2); ?> DH</td>
                                        <td><?php echo number_format($product['reseller_price'], 2); ?> DH</td>
                                        <td><?php echo number_format($product['reseller_price'] - $product['seller_price'], 2); ?> DH</td>
                                        <td>
                                            <?php if ($product['has_discount']): ?>
                                                <?php echo number_format($product['discount_price'], 2); ?> DH
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($product['status'] == 'active'): ?>
                                                <span class="badge bg-success">Disponible</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Non disponible</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $colorStmt = $conn->prepare("SELECT color_code, stock FROM product_colors WHERE product_id = ?");
                                            $colorStmt->execute([$product['id']]);
                                            $colors = $colorStmt->fetchAll(PDO::FETCH_ASSOC);
                                            
                                            foreach ($colors as $color) {
                                                echo '<div class="d-flex align-items-center mb-1">';
                                                echo '<div class="color-preview me-2" style="width: 20px; height: 20px; background-color: ' . $color['color_code'] . '; border-radius: 50%;"></div>';
                                                echo '<span class="stock-badge">' . $color['stock'] . '</span>';
                                                echo '</div>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                            $sizeStmt = $conn->prepare("SELECT size, stock FROM product_sizes WHERE product_id = ?");
                                            $sizeStmt->execute([$product['id']]);
                                            $sizes = $sizeStmt->fetchAll(PDO::FETCH_ASSOC);
                                            
                                            foreach ($sizes as $size) {
                                                echo '<div class="d-flex align-items-center mb-1">';
                                                echo '<span class="size-badge me-2">' . $size['size'] . '</span>';
                                                echo '<span class="stock-badge">' . $size['stock'] . '</span>';
                                                echo '</div>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php echo $product['total_sold'] ?: 0; ?> unité(s)<br>
                                            <small class="text-muted"><?php echo $product['total_orders'] ?: 0; ?> commande(s)</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $product['status'] === 'active' ? 'success' : 'danger'; ?>">
                                                <?php echo $product['status']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-info" title="Modifier"
                                                        data-bs-toggle="modal" data-bs-target="#editProductModal<?php echo $product['id']; ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form method="post" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce produit ?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger" title="Supprimer">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
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

    <!-- Modal d'ajout de produit -->
    <div class="modal fade" id="addProductModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ajouter un produit</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="mb-3">
                            <label class="form-label">Nom</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>

                        <!-- Images -->
                        <div class="mb-3">
                            <label class="form-label">Images du produit</label>
                            <input type="file" name="images[]" class="form-control" multiple accept="image/*">
                            <small class="text-muted">La première image sera l'image principale. Vous pouvez sélectionner plusieurs images.</small>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Prix Vendeur (DH)</label>
                                <input type="number" name="seller_price" class="form-control" step="0.01" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Prix Revendeur (DH)</label>
                                <input type="number" name="reseller_price" class="form-control" step="0.01" required>
                            </div>
                        </div>

                        <!-- Réduction -->
                        <div class="mb-3">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="has_discount" id="hasDiscount" onchange="toggleDiscount()">
                                <label class="form-check-label" for="hasDiscount">
                                    Activer la réduction
                                </label>
                            </div>
                            <div id="discountFields" style="display: none;">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label class="form-label">Prix avec réduction (DH)</label>
                                        <input type="number" name="discount_price" class="form-control" step="0.01">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Disponibilité pour les affiliés -->
                        <div class="mb-3">
                            <label class="form-label">Disponibilité pour les affiliés</label>
                            <select class="form-select" name="affiliate_visibility" id="affiliateVisibility" onchange="toggleAffiliateSelection()">
                                <option value="all">Tous les affiliés</option>
                                <option value="specific">Affiliés spécifiques</option>
                            </select>
                        </div>

                        <div id="specificAffiliates" style="display: none;" class="mb-3">
                            <label class="form-label">Sélectionner les affiliés</label>
                            <div class="border rounded p-3" style="max-height: 200px; overflow-y: auto;">
                                <?php
                                $affiliates = $conn->query("SELECT id, name, email FROM affiliates WHERE status = 'active' ORDER BY name")->fetchAll();
                                foreach ($affiliates as $affiliate):
                                ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="selected_affiliates[]" value="<?php echo $affiliate['id']; ?>">
                                    <label class="form-check-label">
                                        <?php echo htmlspecialchars($affiliate['name']); ?> 
                                        <small class="text-muted">(<?php echo htmlspecialchars($affiliate['email']); ?>)</small>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Catégorie -->
                        <div class="mb-3">
                            <label class="form-label">Catégorie</label>
                            <select name="category_id" class="form-select" required>
                                <option value="">Sélectionnez une catégorie</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Status -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select" required>
                                    <option value="active">Actif</option>
                                    <option value="inactive">Inactif</option>
                                </select>
                            </div>
                        </div>

                        <!-- Couleurs et Stocks -->
                        <div class="mb-3">
                            <label class="form-label">Couleurs et Stocks</label>
                            <div id="colorsContainer">
                                <div class="color-entry mb-2">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="input-group">
                                                <input type="color" name="colors[]" class="form-control form-control-color" required>
                                                <input type="number" name="color_stocks[]" class="form-control" placeholder="Stock" min="0" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <button type="button" class="btn btn-danger remove-color" onclick="removeColor(this)">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-secondary mt-2" onclick="addColor()">
                                <i class="fas fa-plus"></i> Ajouter une couleur
                            </button>
                        </div>

                        <!-- Tailles et Stocks -->
                        <div class="mb-3">
                            <label class="form-label">Tailles et Stocks</label>
                            <div id="sizesContainer">
                                <div class="size-entry mb-2">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="input-group">
                                                <input type="text" name="sizes[]" class="form-control" placeholder="Taille" required>
                                                <input type="number" name="size_stocks[]" class="form-control" placeholder="Stock" min="0" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <button type="button" class="btn btn-danger remove-size" onclick="removeSize(this)">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-secondary mt-2" onclick="addSize()">
                                <i class="fas fa-plus"></i> Ajouter une taille
                            </button>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select" required>
                                    <option value="active">Actif</option>
                                    <option value="inactive">Inactif</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Ajouter</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="js/products.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleDiscount() {
            const discountFields = document.getElementById('discountFields');
            const hasDiscount = document.getElementById('hasDiscount').checked;
            discountFields.style.display = hasDiscount ? 'block' : 'none';
        }

        function toggleAffiliateSelection() {
            const specificAffiliates = document.getElementById('specificAffiliates');
            const visibility = document.getElementById('affiliateVisibility').value;
            specificAffiliates.style.display = visibility === 'specific' ? 'block' : 'none';
        }

        function addColor() {
            const container = document.getElementById('colorsContainer');
            const newColor = document.createElement('div');
            newColor.className = 'color-entry mb-2';
            newColor.innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <div class="input-group">
                            <input type="color" name="colors[]" class="form-control form-control-color" required>
                            <input type="number" name="color_stocks[]" class="form-control" placeholder="Stock" min="0" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <button type="button" class="btn btn-danger remove-color" onclick="removeColor(this)">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            `;
            container.appendChild(newColor);
        }

        function removeColor(button) {
            button.closest('.color-entry').remove();
        }

        function addSize() {
            const container = document.getElementById('sizesContainer');
            const newSize = document.createElement('div');
            newSize.className = 'size-entry mb-2';
            newSize.innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <div class="input-group">
                            <input type="text" name="sizes[]" class="form-control" placeholder="Taille" required>
                            <input type="number" name="size_stocks[]" class="form-control" placeholder="Stock" min="0" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <button type="button" class="btn btn-danger remove-size" onclick="removeSize(this)">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            `;
            container.appendChild(newSize);
        }

        function removeSize(button) {
            button.closest('.size-entry').remove();
        }
    </script>
</body>
</html>