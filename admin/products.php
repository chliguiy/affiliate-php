<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/database.php';
require_once 'includes/auth.php';
require_once 'includes/AdminPermissions.php';

$database = new Database();
$pdo = $database->getConnection();

$permissions = new AdminPermissions($pdo, $_SESSION['admin_id']);

if (!$permissions->canManageStock()) {
    header('Location: dashboard.php?error=access_denied');
    exit;
}

// Connexion à la base de données
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
                    
                    // Vérification si le slug existe déjà
                    $stmt = $conn->prepare("SELECT COUNT(*) FROM products WHERE name = ?");
                    $stmt->execute([$_POST['name']]);
                    if ($stmt->fetchColumn() > 0) {
                        $slug = $base_slug . '-' . uniqid();
                    }

                    // Insertion du produit
                    $stmt = $conn->prepare("
                        INSERT INTO products (
                            name, slug, description, seller_price, reseller_price,
                            category_id, status, stock_quantity, has_discount, sale_price, affiliate_visibility, disponibilite
                        ) VALUES (
                            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                        )
                    ");
                    
                    $stmt->execute([
                        $_POST['name'],
                        $slug,
                        $_POST['description'],
                        $_POST['seller_price'],
                        $_POST['reseller_price'],
                        $_POST['category_id'],
                        $_POST['status'],
                        0,
                        isset($_POST['has_discount']) ? 1 : 0,
                        $_POST['sale_price'] ?? null,
                        $_POST['affiliate_visibility'],
                        $_POST['disponibilite']
                    ]);
                    
                    $product_id = $conn->lastInsertId();

                    // Gestion des affiliés spécifiques si la visibilité est 'specific'
                    if ($_POST['affiliate_visibility'] === 'specific' && isset($_POST['specific_affiliates']) && is_array($_POST['specific_affiliates'])) {
                        $stmt_affiliate = $conn->prepare("INSERT INTO product_affiliates (product_id, affiliate_id) VALUES (?, ?)");
                        foreach ($_POST['specific_affiliates'] as $affiliate_id) {
                            $stmt_affiliate->execute([$product_id, $affiliate_id]);
                        }
                    }

                    // Gestion des couleurs et stock
                    if (isset($_POST['color_stock']) && is_array($_POST['color_stock'])) {
                        $stmt = $conn->prepare("INSERT INTO product_colors (product_id, color_id, stock) VALUES (?, ?, ?)");
                        foreach ($_POST['color_stock'] as $color_id => $stock) {
                            if ($stock > 0) {
                                $stmt->execute([$product_id, $color_id, $stock]);
                            }
                        }
                    }

                    // Gestion des tailles et stock
                    if (isset($_POST['size_stock']) && is_array($_POST['size_stock'])) {
                        $stmt = $conn->prepare("INSERT INTO product_sizes (product_id, size_id, stock) VALUES (?, ?, ?)");
                        foreach ($_POST['size_stock'] as $size_id => $stock) {
                            if ($stock > 0) {
                                $stmt->execute([$product_id, $size_id, $stock]);
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
                                $stmt->execute([$product_id, 'uploads/products/' . $new_file_name]);
                            }
                        }
                    }

                    $conn->commit();
                    $_SESSION['success_message'] = "Produit ajouté avec succès.";
                } catch (Exception $e) {
                    $conn->rollBack();
                    $_SESSION['error_message'] = $e->getMessage();
                }
                header('Location: products.php');
                exit;
                break;

            case 'edit':
                try {
                    $conn->beginTransaction();
                    
                    $stmt = $conn->prepare("
                        UPDATE products SET
                            name = ?,
                            description = ?,
                            seller_price = ?,
                            reseller_price = ?,
                            category_id = ?,
                            status = ?,
                            has_discount = ?,
                            sale_price = ?,
                            affiliate_visibility = ?,
                            disponibilite = ?,
                            updated_at = CURRENT_TIMESTAMP
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $_POST['name'],
                        $_POST['description'],
                        $_POST['seller_price'],
                        $_POST['reseller_price'],
                        $_POST['category_id'],
                        $_POST['status'],
                        isset($_POST['has_discount']) ? 1 : 0,
                        $_POST['sale_price'] ?? null,
                        $_POST['affiliate_visibility'],
                        $_POST['edit_disponibilite'],
                        $_POST['id']
                    ]);

                    // Gestion des affiliés spécifiques pour l'édition
                    // Supprimer les anciens affiliés spécifiques pour ce produit
                    $stmt_delete_affiliates = $conn->prepare("DELETE FROM product_affiliates WHERE product_id = ?");
                    $stmt_delete_affiliates->execute([$_POST['id']]);

                    // Ajouter les nouveaux affiliés spécifiques si la visibilité est 'specific'
                    if ($_POST['affiliate_visibility'] === 'specific' && isset($_POST['specific_affiliates']) && is_array($_POST['specific_affiliates'])) {
                        $stmt_insert_affiliate = $conn->prepare("INSERT INTO product_affiliates (product_id, affiliate_id) VALUES (?, ?)");
                        foreach ($_POST['specific_affiliates'] as $affiliate_id) {
                            $stmt_insert_affiliate->execute([$_POST['id'], $affiliate_id]);
                        }
                    }

                    // Mise à jour des couleurs
                    if (isset($_POST['color_stock']) && is_array($_POST['color_stock'])) {
                        // Supprimer les anciennes associations
                        $stmt = $conn->prepare("DELETE FROM product_colors WHERE product_id = ?");
                        $stmt->execute([$_POST['id']]);
                        
                        // Ajouter les nouvelles
                        $stmt = $conn->prepare("INSERT INTO product_colors (product_id, color_id, stock) VALUES (?, ?, ?)");
                        foreach ($_POST['color_stock'] as $color_id => $stock) {
                            if ($stock > 0) {
                                $stmt->execute([$_POST['id'], $color_id, $stock]);
                            }
                        }
                    }

                    // Mise à jour des tailles
                    if (isset($_POST['size_stock']) && is_array($_POST['size_stock'])) {
                        // Supprimer les anciennes associations
                        $stmt = $conn->prepare("DELETE FROM product_sizes WHERE product_id = ?");
                        $stmt->execute([$_POST['id']]);
                        
                        // Ajouter les nouvelles
                        $stmt = $conn->prepare("INSERT INTO product_sizes (product_id, size_id, stock) VALUES (?, ?, ?)");
                        foreach ($_POST['size_stock'] as $size_id => $stock) {
                            if ($stock > 0) {
                                $stmt->execute([$_POST['id'], $size_id, $stock]);
                            }
                        }
                    }

                    $conn->commit();
                    $_SESSION['success_message'] = "Produit mis à jour avec succès.";
                } catch (Exception $e) {
                    $conn->rollBack();
                    $_SESSION['error_message'] = $e->getMessage();
                }
                header('Location: products.php');
                exit;
                break;

            case 'delete':
                $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                $_SESSION['success_message'] = "Produit supprimé avec succès.";
                header('Location: products.php');
                exit;
                break;
        }
    }
}

// Récupération des catégories
try {
    $stmt = $conn->prepare("SELECT * FROM categories");
    $stmt->execute();
    $categories = $stmt->fetchAll();
    error_log("Nombre de catégories trouvées : " . count($categories));
} catch(PDOException $e) {
    error_log("Erreur lors de la récupération des catégories : " . $e->getMessage());
    $categories = [];
}

// Récupération des couleurs
try {
    // Vérifier si la table colors existe
    $tableExists = $conn->query("SHOW TABLES LIKE 'colors'")->rowCount() > 0;
    if (!$tableExists) {
        // Créer la table colors si elle n'existe pas
        $conn->exec("CREATE TABLE colors (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL,
            color_code VARCHAR(7) NOT NULL,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
    } else {
        // Vérifier si la colonne color_code existe
        $columnExists = $conn->query("SHOW COLUMNS FROM colors LIKE 'color_code'")->rowCount() > 0;
        if (!$columnExists) {
            // Ajouter la colonne color_code si elle n'existe pas
            $conn->exec("ALTER TABLE colors ADD COLUMN color_code VARCHAR(7) NOT NULL DEFAULT '#000000'");
        }
    }
    
    $colors = $conn->query("SELECT * FROM colors WHERE status = 'active' ORDER BY name")->fetchAll();
} catch(PDOException $e) {
    error_log("Erreur lors de la récupération des couleurs : " . $e->getMessage());
    $colors = [];
}

// Récupération des tailles
try {
    // Vérifier d'abord si la table sizes existe
    $tableExists = $conn->query("SHOW TABLES LIKE 'sizes'")->rowCount() > 0;
    if (!$tableExists) {
        // Créer la table sizes si elle n'existe pas
        $conn->exec("CREATE TABLE sizes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
    }
    
    // Vérifier si la colonne status existe
    $columnExists = $conn->query("SHOW COLUMNS FROM sizes LIKE 'status'")->rowCount() > 0;
    if (!$columnExists) {
        // Ajouter la colonne status si elle n'existe pas
        $conn->exec("ALTER TABLE sizes ADD COLUMN status ENUM('active', 'inactive') DEFAULT 'active'");
    }
    
    $sizes = $conn->query("SELECT * FROM sizes WHERE status = 'active' ORDER BY name")->fetchAll();
} catch(PDOException $e) {
    error_log("Erreur lors de la récupération des tailles : " . $e->getMessage());
    $sizes = [];
}

// Récupération des affiliés actifs
try {
    $affiliates = $conn->query("SELECT id, username, email FROM users WHERE type = 'affiliate' AND status = 'active' ORDER BY username")->fetchAll();
} catch(PDOException $e) {
    error_log("Erreur lors de la récupération des affiliés : " . $e->getMessage());
    $affiliates = [];
}

// Récupération des produits
try {
    // Vérifier si la table products existe
    $tableExists = $conn->query("SHOW TABLES LIKE 'products'")->rowCount() > 0;
    if (!$tableExists) {
        // Créer la table products si elle n'existe pas
        $conn->exec("CREATE TABLE products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            category_id INT,
            seller_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            reseller_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (category_id) REFERENCES categories(id)
        )");
    }

    // Vérifier si la colonne has_discount existe
    $columnExists = $conn->query("SHOW COLUMNS FROM products LIKE 'has_discount'")->rowCount() > 0;
    if (!$columnExists) {
        // Ajouter la colonne has_discount si elle n'existe pas
        $conn->exec("ALTER TABLE products ADD COLUMN has_discount BOOLEAN NOT NULL DEFAULT FALSE");
    }

    // Vérifier si la colonne sale_price existe
    $columnExists = $conn->query("SHOW COLUMNS FROM products LIKE 'sale_price'")->rowCount() > 0;
    if (!$columnExists) {
        // Ajouter la colonne sale_price si elle n'existe pas
        $conn->exec("ALTER TABLE products ADD COLUMN sale_price DECIMAL(10,2) NULL DEFAULT NULL AFTER reseller_price");
    }

    // Vérifier si la table product_sizes existe
    $tableExists = $conn->query("SHOW TABLES LIKE 'product_sizes'")->rowCount() > 0;
    if (!$tableExists) {
        // Créer la table product_sizes si elle n'existe pas
        $conn->exec("CREATE TABLE product_sizes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            size_id INT NOT NULL,
            stock INT NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
            FOREIGN KEY (size_id) REFERENCES sizes(id) ON DELETE CASCADE
        )");
    } else {
        // Vérifier si la colonne size_id existe
        $columnExists = $conn->query("SHOW COLUMNS FROM product_sizes LIKE 'size_id'")->rowCount() > 0;
        if (!$columnExists) {
            // Ajouter la colonne size_id si elle n'existe pas
            $conn->exec("ALTER TABLE product_sizes ADD COLUMN size_id INT NOT NULL AFTER product_id");
            // Ajouter la clé étrangère
            $conn->exec("ALTER TABLE product_sizes ADD FOREIGN KEY (size_id) REFERENCES sizes(id) ON DELETE CASCADE");
        }
    }

    // Vérifier si la table product_colors existe
    $tableExists = $conn->query("SHOW TABLES LIKE 'product_colors'")->rowCount() > 0;
    if (!$tableExists) {
        // Créer la table product_colors si elle n'existe pas
        $conn->exec("CREATE TABLE product_colors (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            color_id INT NOT NULL,
            stock INT NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
            FOREIGN KEY (color_id) REFERENCES colors(id) ON DELETE CASCADE
        )");
    } else {
        // Vérifier si la colonne color_id existe
        $columnExists = $conn->query("SHOW COLUMNS FROM product_colors LIKE 'color_id'")->rowCount() > 0;
        if (!$columnExists) {
            // Ajouter la colonne color_id si elle n'existe pas
            $conn->exec("ALTER TABLE product_colors ADD COLUMN color_id INT NOT NULL AFTER product_id");
            // Ajouter la clé étrangère
            $conn->exec("ALTER TABLE product_colors ADD FOREIGN KEY (color_id) REFERENCES colors(id) ON DELETE CASCADE");
        }
    }

    // Vérifier si la table product_images existe
    $tableExists = $conn->query("SHOW TABLES LIKE 'product_images'")->rowCount() > 0;
    if (!$tableExists) {
        // Créer la table product_images si elle n'existe pas
        $conn->exec("CREATE TABLE product_images (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            image_url VARCHAR(255) NOT NULL,
            is_primary BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        )");
    }

    // Vérifier si la table order_items existe
    $tableExists = $conn->query("SHOW TABLES LIKE 'order_items'")->rowCount() > 0;
    if (!$tableExists) {
        // Créer la table order_items si elle n'existe pas
        $conn->exec("CREATE TABLE order_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            product_id INT NOT NULL,
            quantity INT NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        )");
    }

    // Vérifier si la table orders existe
    $tableExists = $conn->query("SHOW TABLES LIKE 'orders'")->rowCount() > 0;
    if (!$tableExists) {
        // Créer la table orders si elle n'existe pas
        $conn->exec("CREATE TABLE orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            total_amount DECIMAL(10,2) NOT NULL,
            status ENUM('pending', 'processing', 'completed', 'cancelled') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )");
    }

    // Vérifier si la table product_affiliates existe
    $tableExists = $conn->query("SHOW TABLES LIKE 'product_affiliates'")->rowCount() > 0;
    if (!$tableExists) {
        // Créer la table product_affiliates si elle n'existe pas
        $conn->exec("CREATE TABLE product_affiliates (
            product_id INT NOT NULL,
            affiliate_id INT NOT NULL,
            PRIMARY KEY (product_id, affiliate_id),
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
            FOREIGN KEY (affiliate_id) REFERENCES users(id) ON DELETE CASCADE
        )");
    }

    // Vérifier si la colonne affiliate_visibility existe
    $columnExists = $conn->query("SHOW COLUMNS FROM products LIKE 'affiliate_visibility'")->rowCount() > 0;
    if (!$columnExists) {
        // Ajouter la colonne affiliate_visibility si elle n'existe pas
        $conn->exec("ALTER TABLE products ADD COLUMN affiliate_visibility ENUM('all', 'specific') NOT NULL DEFAULT 'all' AFTER status");
    }

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
} catch(PDOException $e) {
    error_log("Erreur lors de la récupération des produits : " . $e->getMessage());
    $products = [];
}

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
        .sidebar { min-height: 100vh; background-color: #f8f9fa; border-right: 1px solid #dee2e6; }
        .product-image { width: 50px; height: 50px; object-fit: cover; }
        .color-preview { 
            width: 20px; 
            height: 20px; 
            border-radius: 50%; 
            border: 1px solid #ddd; 
            display: inline-block;
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
        .color-size-item {
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 10px;
            background-color: #f8f9fa;
        }
        .color-size-item:hover {
            background-color: #e9ecef;
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
                        <i class="fas fa-plus"></i> Ajouter un produit
                    </button>
                </div>

                <!-- Liste des produits -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Image</th>
                                        <th>Nom</th>
                                        <th>Catégorie</th>
                                        <th>Prix Vendeur</th>
                                        <th>Prix Revendeur</th>
                                        <th>Profit</th>
                                        <th>Stock</th>
                                        <th>Couleurs</th>
                                        <th>Tailles</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($products as $product): ?>
                                    <tr>
                                        <td>
                                        <?php
                                        $stmt = $conn->prepare("SELECT image_url FROM product_images WHERE product_id = ? LIMIT 1");
                                        $stmt->execute([$product['id']]);
                                        $image = $stmt->fetch();
                                        if ($image): ?>
                                            <img src="../<?php echo htmlspecialchars($image['image_url'] ?? ''); ?>" class="product-image" alt="">
                                        <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($product['name'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($product['category_name'] ?? ''); ?></td>
                                        <td><?php echo number_format($product['seller_price'] ?? 0, 2); ?> MAD</td>
                                        <td><?php echo number_format($product['reseller_price'] ?? 0, 2); ?> MAD</td>
                                        <td><?php echo number_format(($product['reseller_price'] ?? 0) - ($product['seller_price'] ?? 0), 2); ?> MAD</td>
                                        <td>
                                            <?php
                                            $stmt = $conn->prepare("SELECT SUM(stock) FROM product_sizes WHERE product_id = ?");
                                            $stmt->execute([$product['id']]);
                                            $stock_sizes = (int)$stmt->fetchColumn();

                                            $stmt = $conn->prepare("SELECT SUM(stock) FROM product_colors WHERE product_id = ?");
                                            $stmt->execute([$product['id']]);
                                            $stock_colors = (int)$stmt->fetchColumn();

                                            $total_stock = max($stock_colors, $stock_sizes);
                                            echo htmlspecialchars($total_stock ?? '');
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                            // Vérifier d'abord si le produit a des couleurs
                                            $checkStmt = $conn->prepare("SELECT COUNT(*) FROM product_colors WHERE product_id = ?");
                                            $checkStmt->execute([$product['id']]);
                                            $hasColors = $checkStmt->fetchColumn() > 0;

                                            if ($hasColors) {
                                                $colorStmt = $conn->prepare("
                                                    SELECT c.name, c.color_code, pc.stock
                                                    FROM product_colors pc
                                                    JOIN colors c ON pc.color_id = c.id
                                                    WHERE pc.product_id = ?
                                                ");
                                                $colorStmt->execute([$product['id']]);
                                                $productColors = $colorStmt->fetchAll(PDO::FETCH_ASSOC);
                                                
                                                foreach ($productColors as $color) {
                                                    echo '<div class="d-flex align-items-center mb-1">';
                                                    echo '<div class="color-preview me-2" style="background-color: ' . htmlspecialchars($color['color_code'] ?? '') . ';"></div>';
                                                    echo '<span class="stock-badge">' . (int)$color['stock'] . '</span>';
                                                    echo '</div>';
                                                }
                                            } else {
                                                echo '<div class="text-muted">Aucune couleur</div>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                            // Vérifier d'abord si le produit a des tailles
                                            $checkStmt = $conn->prepare("SELECT COUNT(*) FROM product_sizes WHERE product_id = ?");
                                            $checkStmt->execute([$product['id']]);
                                            $hasSizes = $checkStmt->fetchColumn() > 0;

                                            if ($hasSizes) {
                                                $sizeStmt = $conn->prepare("
                                                    SELECT s.name, ps.stock
                                                    FROM product_sizes ps
                                                    JOIN sizes s ON ps.size_id = s.id
                                                    WHERE ps.product_id = ?
                                                ");
                                                $sizeStmt->execute([$product['id']]);
                                                $productSizes = $sizeStmt->fetchAll(PDO::FETCH_ASSOC);
                                                
                                                foreach ($productSizes as $size) {
                                                    echo '<div class="d-flex align-items-center mb-1">';
                                                    echo '<span class="size-badge me-2">' . htmlspecialchars($size['name'] ?? '') . '</span>';
                                                    echo '<span class="stock-badge">' . (int)$size['stock'] . '</span>';
                                                    echo '</div>';
                                                }
                                            } else {
                                                echo '<div class="text-muted">Aucune taille</div>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $product['status'] === 'active' ? 'success' : 'danger'; ?>">
                                            <?php echo $product['status'] === 'active' ? 'Actif' : 'Inactif'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-primary edit-product-btn" data-product-id="<?php echo $product['id']; ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger delete-product-btn" data-product-id="<?php echo $product['id']; ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
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
                <form action="products.php" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nom du produit</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Prix vendeur</label>
                                    <input type="number" class="form-control" name="seller_price" step="0.01" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Prix revendeur</label>
                                    <input type="number" class="form-control" name="reseller_price" step="0.01" required>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Catégorie</label>
                            <select class="form-select" name="category_id" required>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name'] ?? ''); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Statut</label>
                            <select class="form-select" name="status">
                                <option value="active">Actif (Disponible)</option>
                                <option value="inactive">Inactif</option>
                                <option value="draft">Brouillon</option>
                                <option value="out_of_stock">Rupture de stock</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Disponibilité</label>
                            <select class="form-select" name="disponibilite" required>
                                <option value="oui">Oui</option>
                                <option value="non">Non</option>
                            </select>
                        </div>
                        
                        <!-- Section Couleurs -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">🎨 Couleurs disponibles</label>
                            <div class="row">
                                <?php foreach ($colors as $color): ?>
                                <div class="col-md-6 mb-2">
                                    <div class="color-size-item">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="d-flex align-items-center">
                                                <div class="color-preview me-2" style="background-color: <?php echo $color['color_code'] ?? ''; ?>;"></div>
                                                <span><?php echo htmlspecialchars($color['name'] ?? ''); ?></span>
                                            </div>
                                            <input type="number" 
                                                   class="form-control form-control-sm" 
                                                   name="color_stock[<?php echo $color['id']; ?>]" 
                                                   value="0" 
                                                   min="0" 
                                                   placeholder="Stock"
                                                   style="width: 80px">
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <!-- Section Tailles -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">📏 Tailles disponibles</label>
                            <div class="row">
                                <?php foreach ($sizes as $size): ?>
                                <div class="col-md-6 mb-2">
                                    <div class="color-size-item">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <span class="fw-bold"><?php echo htmlspecialchars($size['name'] ?? ''); ?></span>
                                            <input type="number" 
                                                   class="form-control form-control-sm" 
                                                   name="size_stock[<?php echo $size['id']; ?>]" 
                                                   value="0" 
                                                   min="0" 
                                                   placeholder="Stock"
                                                   style="width: 80px">
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <!-- Section Réduction -->
                        <div class="mb-3 card p-3 shadow-sm">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="addHasDiscountToggle" name="has_discount">
                                <label class="form-check-label" for="addHasDiscountToggle">Appliquer une réduction</label>
                            </div>
                            <div class="mb-3" id="addDiscountPriceField" style="display: none;">
                                <label class="form-label">Prix de réduction</label>
                                <input type="number" class="form-control" name="sale_price" step="0.01" placeholder="Prix après réduction">
                            </div>
                        </div>

                        <!-- Section Visibilité Affiliés -->
                        <div class="mb-3 card p-3 shadow-sm">
                            <label class="form-label fw-bold">Visibilité pour les affiliés</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="affiliate_visibility" id="visibilityAll" value="all" checked>
                                <label class="form-check-label" for="visibilityAll">Tous les affiliés</label>
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="radio" name="affiliate_visibility" id="visibilitySpecific" value="specific">
                                <label class="form-check-label" for="visibilitySpecific">Affiliés spécifiques</label>
                            </div>
                            <div id="specificAffiliatesSelect" style="display: none;">
                                <label class="form-label">Sélectionner les affiliés</label>
                                <select class="form-select" name="specific_affiliates[]" multiple size="5">
                                    <?php foreach ($affiliates as $affiliate): ?>
                                        <option value="<?php echo $affiliate['id']; ?>"><?php echo htmlspecialchars($affiliate['username'] ?? '') . ' (' . htmlspecialchars($affiliate['email'] ?? '') . ')'; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="form-text text-muted">Maintenez Ctrl/Cmd pour sélectionner plusieurs affiliés.</small>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Images</label>
                            <input type="file" class="form-control" name="images[]" multiple accept="image/*">
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

    <!-- Modal d'édition de produit -->
    <div class="modal fade" id="editProductModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Modifier le produit</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editProductForm" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit_product_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Nom du produit</label>
                            <input type="text" class="form-control" name="name" id="edit_name" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" id="edit_description" rows="3"></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Prix vendeur</label>
                                    <input type="number" class="form-control" name="seller_price" id="edit_seller_price" step="0.01" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Prix revendeur</label>
                                    <input type="number" class="form-control" name="reseller_price" id="edit_reseller_price" step="0.01" required>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Catégorie</label>
                            <select class="form-select" name="category_id" id="edit_category_id" required>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name'] ?? ''); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Statut</label>
                            <select class="form-select" name="status" id="edit_status">
                                <option value="active">Actif (Disponible)</option>
                                <option value="inactive">Inactif</option>
                                <option value="draft">Brouillon</option>
                                <option value="out_of_stock">Rupture de stock</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Disponibilité</label>
                            <select class="form-select" name="edit_disponibilite" id="edit_disponibilite" required>
                                <option value="oui">Oui</option>
                                <option value="non">Non</option>
                            </select>
                        </div>

                        <div class="mb-3 form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="edit_hasDiscountToggle" name="has_discount">
                            <label class="form-check-label" for="edit_hasDiscountToggle">Appliquer une réduction</label>
                        </div>

                        <div class="mb-3" id="edit_discountPriceField" style="display: none;">
                            <label class="form-label">Prix de réduction</label>
                            <input type="number" class="form-control" name="sale_price" id="edit_sale_price" step="0.01">
                        </div>

                        <!-- Section Visibilité Affiliés pour édition -->
                        <div class="mb-3 card p-3 shadow-sm">
                            <label class="form-label fw-bold">Visibilité pour les affiliés</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="affiliate_visibility" id="edit_visibilityAll" value="all">
                                <label class="form-check-label" for="edit_visibilityAll">Tous les affiliés</label>
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="radio" name="affiliate_visibility" id="edit_visibilitySpecific" value="specific">
                                <label class="form-check-label" for="edit_visibilitySpecific">Affiliés spécifiques</label>
                            </div>
                            <div id="edit_specificAffiliatesSelect" style="display: none;">
                                <label class="form-label">Sélectionner les affiliés</label>
                                <select class="form-select" name="specific_affiliates[]" id="edit_specific_affiliates" multiple size="5">
                                    <?php foreach ($affiliates as $affiliate): ?>
                                        <option value="<?php echo $affiliate['id']; ?>"><?php echo htmlspecialchars($affiliate['username'] ?? '') . ' (' . htmlspecialchars($affiliate['email'] ?? '') . ')'; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="form-text text-muted">Maintenez Ctrl/Cmd pour sélectionner plusieurs affiliés.</small>
                            </div>
                        </div>

                        <!-- Section Couleurs pour édition -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">🎨 Couleurs disponibles</label>
                            <div id="edit_colors_container" class="row">
                                <!-- Les couleurs seront ajoutées ici dynamiquement -->
                            </div>
                        </div>

                        <!-- Section Tailles pour édition -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">📏 Tailles disponibles</label>
                            <div id="edit_sizes_container" class="row">
                                <!-- Les tailles seront ajoutées ici dynamiquement -->
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Images</label>
                            <input type="file" class="form-control" name="images[]" multiple accept="image/*">
                            <div id="edit_current_images" class="mt-2">
                                <!-- Les images actuelles seront affichées ici -->
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" form="editProductForm" class="btn btn-primary">Enregistrer</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Fonction pour éditer un produit
        function editProduct(id) {
            // Charger les données du produit
            fetch(`get_product.php?id=${id}`)
                .then(response => response.json())
                .then(product => {
                    // Remplir les champs du formulaire
                    document.getElementById('edit_product_id').value = product.id;
                    document.getElementById('edit_name').value = product.name;
                    document.getElementById('edit_description').value = product.description;
                    document.getElementById('edit_seller_price').value = product.seller_price;
                    document.getElementById('edit_reseller_price').value = product.reseller_price;
                    document.getElementById('edit_category_id').value = product.category_id;
                    document.getElementById('edit_status').value = product.status;
                    document.getElementById('edit_sale_price').value = product.sale_price || '';
                    document.getElementById('edit_hasDiscountToggle').checked = product.has_discount == 1;
                    document.getElementById('edit_disponibilite').value = product.disponibilite === 'oui' ? 'oui' : 'non';
                    
                    // Afficher/masquer le champ de réduction si nécessaire
                    if (product.has_discount == 1) {
                        document.getElementById('edit_discountPriceField').style.display = 'block';
                    } else {
                        document.getElementById('edit_discountPriceField').style.display = 'none';
                    }

                    // Gérer la visibilité des affiliés
                    document.getElementById('edit_visibilityAll').checked = product.affiliate_visibility === 'all';
                    document.getElementById('edit_visibilitySpecific').checked = product.affiliate_visibility === 'specific';
                    
                    const editSpecificAffiliatesSelectDiv = document.getElementById('edit_specificAffiliatesSelect');
                    const editSpecificAffiliatesDropdown = document.getElementById('edit_specific_affiliates');

                    if (product.affiliate_visibility === 'specific') {
                        editSpecificAffiliatesSelectDiv.style.display = 'block';
                        // Réinitialiser toutes les options à non sélectionnées
                        Array.from(editSpecificAffiliatesDropdown.options).forEach(option => {
                            option.selected = false;
                        });
                        // Sélectionner les affiliés spécifiques
                        const currentAffiliates = product.specific_affiliates || [];
                        Array.from(editSpecificAffiliatesDropdown.options).forEach(option => {
                            if (currentAffiliates.includes(parseInt(option.value))) {
                                option.selected = true;
                            }
                        });
                    } else {
                        editSpecificAffiliatesSelectDiv.style.display = 'none';
                    }

                    // Gérer les couleurs
                    const colorsContainer = document.getElementById('edit_colors_container');
                    colorsContainer.innerHTML = '';
                    <?php foreach ($colors as $color): ?>
                    colorsContainer.innerHTML += `
                        <div class="col-md-6 mb-2">
                            <div class="color-size-item">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center">
                                        <div class="color-preview me-2" style="background-color: <?php echo $color['color_code'] ?? ''; ?>;"></div>
                                        <span><?php echo htmlspecialchars($color['name'] ?? ''); ?></span>
                                    </div>
                                    <input type="number" 
                                           class="form-control form-control-sm" 
                                           name="color_stock[<?php echo $color['id']; ?>]" 
                                           value="${product.colors.find(c => c.color_id == <?php echo $color['id']; ?>)?.stock || 0}" 
                                           min="0" 
                                           placeholder="Stock"
                                           style="width: 80px">
                                </div>
                            </div>
                        </div>
                    `;
                    <?php endforeach; ?>

                    // Gérer les tailles
                    const sizesContainer = document.getElementById('edit_sizes_container');
                    sizesContainer.innerHTML = '';
                    <?php foreach ($sizes as $size): ?>
                    sizesContainer.innerHTML += `
                        <div class="col-md-6 mb-2">
                            <div class="color-size-item">
                                <div class="d-flex align-items-center justify-content-between">
                                    <span class="fw-bold"><?php echo htmlspecialchars($size['name'] ?? ''); ?></span>
                                    <input type="number" 
                                           class="form-control form-control-sm" 
                                           name="size_stock[<?php echo $size['id']; ?>]" 
                                           value="${product.sizes.find(s => s.size_id == <?php echo $size['id']; ?>)?.stock || 0}" 
                                           min="0" 
                                           placeholder="Stock"
                                           style="width: 80px">
                                </div>
                            </div>
                        </div>
                    `;
                    <?php endforeach; ?>

                    // Afficher le modal
                    const editModal = new bootstrap.Modal(document.getElementById('editProductModal'));
                    editModal.show();
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Erreur lors du chargement des données du produit');
                });
        }

        // Fonction pour supprimer un produit
        function deleteProduct(id) {
            if (confirm('Êtes-vous sûr de vouloir supprimer ce produit ?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Ajouter les événements de clic sur les boutons d'édition
        document.querySelectorAll('.edit-product-btn').forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.dataset.productId;
                editProduct(productId);
            });
        });

        // Ajouter les événements de clic sur les boutons de suppression
        document.querySelectorAll('.delete-product-btn').forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.dataset.productId;
                deleteProduct(productId);
            });
        });

        // JavaScript pour gérer l'affichage du champ de réduction et de la visibilité des affiliés
        const editHasDiscountToggle = document.getElementById('edit_hasDiscountToggle');
        const editDiscountPriceField = document.getElementById('edit_discountPriceField');
        const editVisibilityAllRadio = document.getElementById('edit_visibilityAll');
        const editVisibilitySpecificRadio = document.getElementById('edit_visibilitySpecific');
        const editSpecificAffiliatesSelectDiv = document.getElementById('edit_specificAffiliatesSelect');

        function toggleEditDiscountField() {
            if (editHasDiscountToggle.checked) {
                editDiscountPriceField.style.display = 'block';
            } else {
                editDiscountPriceField.style.display = 'none';
            }
        }

        function toggleEditSpecificAffiliates() {
            if (editVisibilitySpecificRadio.checked) {
                editSpecificAffiliatesSelectDiv.style.display = 'block';
            } else {
                editSpecificAffiliatesSelectDiv.style.display = 'none';
            }
        }

        editHasDiscountToggle.addEventListener('change', toggleEditDiscountField);
        editVisibilityAllRadio.addEventListener('change', toggleEditSpecificAffiliates);
        editVisibilitySpecificRadio.addEventListener('change', toggleEditSpecificAffiliates);
        
        // Initialiser l'état au chargement de la page pour le formulaire d'édition
        toggleEditDiscountField();
        toggleEditSpecificAffiliates();

        // Pour le modal d'édition
        const addHasDiscountToggle = document.getElementById('addHasDiscountToggle');
        const addDiscountPriceField = document.getElementById('addDiscountPriceField');
        const addVisibilityAllRadio = document.getElementById('visibilityAll');
        const addVisibilitySpecificRadio = document.getElementById('visibilitySpecific');
        const addSpecificAffiliatesSelect = document.getElementById('specificAffiliatesSelect');

        function toggleAddDiscountField() {
            if (addHasDiscountToggle.checked) {
                addDiscountPriceField.style.display = 'block';
            } else {
                addDiscountPriceField.style.display = 'none';
            }
        }

        function toggleAddSpecificAffiliates() {
            if (addVisibilitySpecificRadio.checked) {
                addSpecificAffiliatesSelect.style.display = 'block';
            } else {
                addSpecificAffiliatesSelect.style.display = 'none';
            }
        }

        addHasDiscountToggle.addEventListener('change', toggleAddDiscountField);
        addVisibilityAllRadio.addEventListener('change', toggleAddSpecificAffiliates);
        addVisibilitySpecificRadio.addEventListener('change', toggleAddSpecificAffiliates);
        
        // Initialiser l'état au chargement de la page pour le formulaire d'ajout
        toggleAddDiscountField();
        toggleAddSpecificAffiliates();
    </script>
</body>
</html>