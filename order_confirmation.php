<?php
session_start();
require_once 'config/database.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Vérifier si un ID de commande est fourni
if (!isset($_GET['order_id'])) {
    header('Location: dashboard.php');
    exit();
}

$order_id = (int)$_GET['order_id'];
$database = new Database();
$conn = $database->getConnection();

// Récupérer les détails de la commande
$stmt = $conn->prepare("
    SELECT o.*, u.username as affiliate_name
    FROM orders o
    LEFT JOIN users u ON o.affiliate_id = u.id
    WHERE o.id = ?
");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) {
    $_SESSION['error'] = "Commande introuvable.";
    header('Location: dashboard.php');
    exit();
}

// Récupérer les produits de la commande
$stmt = $conn->prepare("
    SELECT oi.*, p.name as product_name, p.image_url
    FROM order_items oi
    LEFT JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmation de Commande - Chic Affiliate</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f8fafc 0%, #e3e9f7 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', 'Roboto', Arial, sans-serif;
        }
        .confirmation-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .success-header {
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .order-details {
            padding: 2rem;
        }
        .product-item {
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }
        .status-badge {
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="confirmation-card">
                    <!-- En-tête de succès -->
                    <div class="success-header">
                        <i class="fas fa-check-circle" style="font-size: 4rem; margin-bottom: 1rem;"></i>
                        <h1 class="mb-3">Commande Confirmée !</h1>
                        <p class="mb-0">Votre commande a été enregistrée avec succès</p>
                    </div>

                    <!-- Détails de la commande -->
                    <div class="order-details">
                        <!-- Informations générales -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h5><i class="fas fa-receipt me-2"></i>Informations de commande</h5>
                                <p><strong>Numéro de commande:</strong> <?php echo htmlspecialchars($order['order_number']); ?></p>
                                <p><strong>Date de commande:</strong> <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></p>
                                <p><strong>Statut:</strong> 
                                    <span class="badge bg-<?php echo $order['status'] === 'pending' ? 'warning' : 'success'; ?> status-badge">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <h5><i class="fas fa-user me-2"></i>Informations client</h5>
                                <p><strong>Nom:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($order['customer_email']); ?></p>
                                <p><strong>Téléphone:</strong> <?php echo htmlspecialchars($order['customer_phone']); ?></p>
                                <p><strong>Adresse:</strong> <?php echo htmlspecialchars($order['customer_address']); ?>, <?php echo htmlspecialchars($order['customer_city']); ?>
<?php if (!empty($order['notes'])): ?>
    <br><span style="color:#888;"><strong>Commentaire:</strong> <?php echo nl2br(htmlspecialchars($order['notes'])); ?></span>
<?php endif; ?></p>
                            </div>
                        </div>

                        <!-- Produits commandés -->
                        <h5><i class="fas fa-shopping-cart me-2"></i>Produits commandés</h5>
                        <?php foreach ($order_items as $item): ?>
                            <div class="product-item">
                                <div class="row align-items-center">
                                    <div class="col-md-2">
                                        <?php if ($item['image_url']): ?>
                                            <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>" class="product-image">
                                        <?php else: ?>
                                            <div class="product-image bg-light d-flex align-items-center justify-content-center">
                                                <i class="fas fa-image text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($item['product_name']); ?></h6>
                                        <small class="text-muted">Quantité: <?php echo $item['quantity']; ?></small>
                                    </div>
                                    <div class="col-md-2 text-end">
                                        <strong><?php echo number_format($item['price'], 2); ?> MAD</strong>
                                    </div>
                                    <div class="col-md-2 text-end">
                                        <strong><?php echo number_format($item['price'], 2); ?> MAD</strong>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <!-- Résumé financier -->
                        <div class="row mt-4">
                            <div class="col-md-6 offset-md-6">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title">Résumé financier</h6>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Prix de vente:</span>
                                            <span><?php echo number_format($order_items[0]['price'], 2); ?> MAD</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Prix de livraison:</span>
                                            <span><?php echo isset($order['delivery_fee']) ? number_format($order['delivery_fee'], 2) : '0.00'; ?> MAD</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Prix de vente final:</span>
                                            <span><?php echo isset($order['final_sale_price']) ? number_format($order['final_sale_price'], 2) : '-'; ?> MAD</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Marge affilié:</span>
                                            <span><?php echo isset($order['affiliate_margin']) ? number_format($order['affiliate_margin'], 2) : '-'; ?> MAD</span>
                                        </div>
                                        <hr>
                                        <div class="d-flex justify-content-between fw-bold">
                                            <span>Total payé client:</span>
                                            <span>
                                                <?php
                                                    echo isset(
                                                        $order['final_sale_price']) ? number_format($order['final_sale_price'], 2) : '-';
                                                ?> MAD
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Informations affilié -->
                        <?php if ($order['affiliate_name']): ?>
                            <div class="alert alert-info mt-4">
                                <h6><i class="fas fa-handshake me-2"></i>Votre affilié</h6>
                                <p class="mb-0">Cette commande a été générée par l'affilié <strong><?php echo htmlspecialchars($order['affiliate_name']); ?></strong></p>
                            </div>
                        <?php endif; ?>

                        <!-- Prochaines étapes -->
                        <div class="alert alert-success mt-4">
                            <h6><i class="fas fa-info-circle me-2"></i>Prochaines étapes</h6>
                            <ul class="mb-0">
                                <li>Votre commande sera traitée dans les plus brefs délais</li>
                                <li>Vous recevrez une confirmation par appel téléphonique</li>
                                <li>Un confirmateur vous contactera pour valider votre commande</li>
                                <li>Le délai de livraison est de 2-3 jours ouvrables</li>
                            </ul>
                        </div>

                        <!-- Actions -->
                        <div class="text-center mt-4">
                            <a href="dashboard.php" class="btn btn-primary me-2">
                                <i class="fas fa-home me-2"></i>Retour au tableau de bord
                            </a>
                            <a href="products.php" class="btn btn-outline-primary">
                                <i class="fas fa-shopping-bag me-2"></i>Continuer les achats
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 