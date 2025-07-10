<?php
session_start();
require_once '../config/database.php';

// Vérification de l'authentification et du rôle
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'vendor') {
    header('Location: ../login.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = htmlspecialchars($_POST['name'], ENT_QUOTES, 'UTF-8');
    $description = htmlspecialchars($_POST['description'], ENT_QUOTES, 'UTF-8');
    $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
    $commission_rate = filter_input(INPUT_POST, 'commission_rate', FILTER_VALIDATE_FLOAT);

    if ($name && $description && $price !== false && $commission_rate !== false) {
        if ($price > 0 && $commission_rate >= 0 && $commission_rate <= 100) {
            $stmt = $conn->prepare("
                INSERT INTO products (vendor_id, name, description, price, commission_rate, status)
                VALUES (?, ?, ?, ?, ?, 'pending')
            ");
            
            if ($stmt->execute([$_SESSION['user_id'], $name, $description, $price, $commission_rate])) {
                $success = 'Produit ajouté avec succès. En attente de validation par un administrateur.';
            } else {
                $error = 'Une erreur est survenue lors de l\'ajout du produit.';
            }
        } else {
            $error = 'Le prix doit être supérieur à 0 et le taux de commission entre 0 et 100.';
        }
    } else {
        $error = 'Veuillez remplir tous les champs correctement.';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un produit - Chic Affiliate</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background-color: #f8f9fa;
            border-right: 1px solid #dee2e6;
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
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-home me-2"></i> Tableau de bord
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="products.php">
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
                    <h2>Ajouter un produit</h2>
                    <a href="dashboard.php" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left me-2"></i>Retour
                    </a>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="name" class="form-label">Nom du produit</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="price" class="form-label">Prix (€)</label>
                                        <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="commission_rate" class="form-label">Taux de commission (%)</label>
                                        <input type="number" class="form-control" id="commission_rate" name="commission_rate" step="0.01" min="0" max="100" required>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Ajouter le produit
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 