<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/database.php';
require_once 'includes/auth.php';
require_once 'includes/AdminPermissions.php';

// Vérification de l'authentification
if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$database = new Database();
$conn = $database->getConnection();

$permissions = new AdminPermissions($conn, $_SESSION['admin_id']);

if (!$permissions->canManageOrders()) {
    header('Location: dashboard.php?error=access_denied');
    exit;
}

// Définir les traductions des statuts pour l'affichage et le menu déroulant
$status_translations = [
    'new' => 'Nouveau',
    'unconfirmed' => 'Non confirmé',
    'confirmed' => 'Confirmé',
    'shipping' => 'En livraison',
    'delivered' => 'Livré',
    'returned' => 'Retourné',
    'refused' => 'Refusé',
    'cancelled' => 'Annulé',
    'duplicate' => 'Dupliqué',
    'changed' => 'Changé'
];

// Récupération de tous les affiliés avec leurs statistiques
$stmt = $conn->query("
    SELECT 
        u.id,
        u.username,
        u.email,
        u.phone,
        u.city,
        u.status,
        u.created_at,
        COUNT(DISTINCT o.id) as total_orders,
        COALESCE(SUM(CASE WHEN o.status = 'delivered' THEN o.final_sale_price ELSE 0 END), 0) as total_sales,
        COALESCE(SUM(CASE WHEN o.status = 'delivered' THEN o.commission_amount ELSE 0 END), 0) as total_commission,
        COUNT(DISTINCT CASE WHEN o.status = 'confirmed' THEN o.id END) as confirmed_orders,
        COUNT(DISTINCT CASE WHEN o.status = 'cancelled' THEN o.id END) as cancelled_orders,
        COUNT(DISTINCT CASE WHEN o.status = 'pending' THEN o.id END) as pending_orders
    FROM users u
    LEFT JOIN orders o ON o.affiliate_id = u.id
    LEFT JOIN order_items oi ON o.id = oi.order_id
    LEFT JOIN products p ON oi.product_id = p.id
    WHERE u.type = 'affiliate'
    GROUP BY u.id, u.username, u.email, u.phone, u.city, u.status, u.created_at
    ORDER BY total_sales DESC
");
$affiliates = $stmt->fetchAll();

// Récupération des commandes détaillées pour chaque affilié
$affiliate_orders = [];
foreach ($affiliates as $affiliate) {
    $stmt = $conn->prepare("
        SELECT 
            o.*,
            COUNT(oi.id) as total_items,
            GROUP_CONCAT(CONCAT(p.name, ' (', oi.quantity, ')') SEPARATOR ', ') as products
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE o.affiliate_id = ?
        GROUP BY o.id
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$affiliate['id']]);
    $affiliate_orders[$affiliate['id']] = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Affiliés et leurs Commandes - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background-color: #f8f9fa;
            border-right: 1px solid #dee2e6;
        }
        .affiliate-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            transition: transform 0.3s ease;
        }
        .affiliate-card:hover {
            transform: translateY(-5px);
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
        }
        .status-active { background-color: #28a745; color: white; }
        .status-pending { background-color: #ffc107; color: black; }
        .status-suspended { background-color: #dc3545; color: white; }
        .order-status {
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 0.75rem;
        }
        .order-confirmed { background-color: #28a745; color: white; }
        .order-pending { background-color: #ffc107; color: black; }
        .order-cancelled { background-color: #dc3545; color: white; }
        .stats-row {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
        }
        .collapse-btn {
            background: none;
            border: none;
            color: #007bff;
            cursor: pointer;
        }
        .collapse-btn:hover {
            color: #0056b3;
        }
        .orders-table {
            font-size: 0.9rem;
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
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Affiliés et leurs Commandes</h2>
                    <div>
                        <button class="btn btn-outline-primary" onclick="expandAll()">
                            <i class="fas fa-expand-alt me-2"></i>Tout Déplier
                        </button>
                        <button class="btn btn-outline-secondary" onclick="collapseAll()">
                            <i class="fas fa-compress-alt me-2"></i>Tout Replier
                        </button>
                    </div>
                </div>

                <?php foreach ($affiliates as $affiliate): ?>
                <?php
                // Calculer les ventes totales payées par le client (uniquement livrées)
                $total_sales = array_sum(array_map(function($order) {
                    return $order['status'] === 'delivered' ? $order['final_sale_price'] : 0;
                }, $affiliate_orders[$affiliate['id']] ?? []));
                ?>
                <div class="card affiliate-card">
                    <div class="card-header bg-primary text-white">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h5 class="mb-0">
                                    <i class="fas fa-user me-2"></i>
                                    <?php echo htmlspecialchars($affiliate['username']); ?>
                                    <span class="status-badge status-<?php echo strtolower($affiliate['status']); ?> ms-2">
                                        <?php echo ucfirst($affiliate['status']); ?>
                                    </span>
                                </h5>
                                <small class="text-light">
                                    <?php echo htmlspecialchars($affiliate['email']); ?> | 
                                    <?php echo htmlspecialchars($affiliate['phone']); ?> | 
                                    <?php echo htmlspecialchars($affiliate['city']); ?>
                                </small>
                            </div>
                            <div class="col-md-4 text-end">
                                <button class="collapse-btn" data-bs-toggle="collapse" 
                                        data-bs-target="#orders-<?php echo $affiliate['id']; ?>">
                                    <i class="fas fa-chevron-down"></i> Voir les commandes
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <!-- Statistiques de l'affilié -->
                        <div class="stats-row">
                            <div class="row text-center">
                                <div class="col-md-2">
                                    <h6 class="text-muted">Total Commandes</h6>
                                    <h4 class="text-primary"><?php echo $affiliate['total_orders']; ?></h4>
                                </div>
                                <div class="col-md-2">
                                    <h6 class="text-muted">Ventes Totales</h6>
                                    <h4 class="text-success"><?php echo number_format($total_sales, 2); ?> MAD</h4>
                                </div>
                                <div class="col-md-2">
                                    <h6 class="text-muted">Commissions</h6>
                                    <h4 class="text-info"><?php echo number_format($affiliate['total_commission'] ?? 0, 2); ?> MAD</h4>
                                </div>
                                <div class="col-md-2">
                                    <h6 class="text-muted">Confirmées</h6>
                                    <h4 class="text-success"><?php echo $affiliate['confirmed_orders']; ?></h4>
                                </div>
                                <div class="col-md-2">
                                    <h6 class="text-muted">En Attente</h6>
                                    <h4 class="text-warning"><?php echo $affiliate['pending_orders']; ?></h4>
                                </div>
                                <div class="col-md-2">
                                    <h6 class="text-muted">Annulées</h6>
                                    <h4 class="text-danger"><?php echo $affiliate['cancelled_orders']; ?></h4>
                                </div>
                            </div>
                        </div>

                        <!-- Commandes de l'affilié -->
                        <div class="collapse" id="orders-<?php echo $affiliate['id']; ?>">
                            <div class="table-responsive">
                                <table class="table table-striped orders-table">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>N° Commande</th>
                                            <th>Date</th>
                                            <th>Client</th>
                                            <th>Produits</th>
                                            <th>Montant</th>
                                            <th>Commission</th>
                                            <th>Statut</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($affiliate_orders[$affiliate['id']])): ?>
                                        <tr>
                                            <td colspan="8" class="text-center text-muted">
                                                <i class="fas fa-inbox fa-2x mb-2"></i><br>
                                                Aucune commande pour cet affilié
                                            </td>
                                        </tr>
                                        <?php else: ?>
                                            <?php foreach ($affiliate_orders[$affiliate['id']] as $order): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($order['order_number']); ?></strong>
                                                </td>
                                                <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                                                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                                <td>
                                                    <small class="text-muted">
                                                        <?php echo htmlspecialchars($order['products'] ?? 'N/A'); ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <strong><?php echo number_format($order['final_sale_price'] ?? 0, 2); ?> MAD</strong>
                                                </td>
                                                <td>
                                                    <?php 
                                                    // Afficher la marge affilié réelle si disponible
                                                    if (isset($order['affiliate_margin'])) {
                                                        echo number_format($order['affiliate_margin'], 2);
                                                    } else {
                                                        // Fallback: ancienne logique
                                                        $commission = ($order['final_sale_price'] ?? 0) * 0.05;
                                                        echo number_format($commission, 2);
                                                    }
                                                    ?> MAD
                                                </td>
                                                <td>
                                                    <span class="order-status order-<?php echo strtolower($order['status']); ?>">
                                                        <?php echo ucfirst($order['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="order_details.php?id=<?php echo $order['id']; ?>" 
                                                           class="btn btn-outline-primary" 
                                                           title="Voir détails">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="affiliate_details.php?id=<?php echo $affiliate['id']; ?>" 
                                                           class="btn btn-outline-info" 
                                                           title="Détails affilié">
                                                            <i class="fas fa-user"></i>
                                                        </a>
                                                    </div>
                                                    <div class="dropdown d-inline-block ms-1">
                                                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                            <i class="fas fa-sync-alt"></i> Statut
                                                        </button>
                                                        <ul class="dropdown-menu">
                                                            <?php foreach ($status_translations as $status => $label): ?>
                                                                <li>
                                                                    <form method="post" action="change_order_status.php" style="margin:0;">
                                                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                                        <button class="dropdown-item text-<?php
                                                                            if ($status === 'delivered') echo 'success';
                                                                            elseif ($status === 'shipping') echo 'warning';
                                                                            elseif ($status === 'cancelled' || $status === 'refused') echo 'danger';
                                                                            elseif ($status === 'confirmed') echo 'primary';
                                                                            else echo 'secondary';
                                                                        ?><?php if ($order['status'] === $status) echo ' fw-bold'; ?>" name="new_status" value="<?php echo $status; ?>">
                                                                            <?php if ($status === 'delivered'): ?><i class="fas fa-check-circle me-1"></i><?php endif; ?>
                                                                            <?php if ($status === 'shipping'): ?><i class="fas fa-truck me-1"></i><?php endif; ?>
                                                                            <?php if ($status === 'cancelled' || $status === 'refused'): ?><i class="fas fa-times-circle me-1"></i><?php endif; ?>
                                                                            <?php if ($status === 'confirmed'): ?><i class="fas fa-check me-1"></i><?php endif; ?>
                                                                            <?php echo $label; ?>
                                                                            <?php if ($order['status'] === $status): ?> <i class="fas fa-check ms-1"></i><?php endif; ?>
                                                                        </button>
                                                                    </form>
                                                                </li>
                                                            <?php endforeach; ?>
                                                        </ul>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function expandAll() {
            const collapseElements = document.querySelectorAll('.collapse');
            collapseElements.forEach(element => {
                const bsCollapse = new bootstrap.Collapse(element, { show: true });
            });
        }

        function collapseAll() {
            const collapseElements = document.querySelectorAll('.collapse');
            collapseElements.forEach(element => {
                const bsCollapse = new bootstrap.Collapse(element, { hide: true });
            });
        }

        // Mettre à jour le texte du bouton lors du clic
        document.addEventListener('DOMContentLoaded', function() {
            const collapseButtons = document.querySelectorAll('.collapse-btn');
            collapseButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const target = this.getAttribute('data-bs-target');
                    const collapseElement = document.querySelector(target);
                    const icon = this.querySelector('i');
                    
                    if (collapseElement.classList.contains('show')) {
                        icon.className = 'fas fa-chevron-down';
                        this.innerHTML = '<i class="fas fa-chevron-down"></i> Voir les commandes';
                    } else {
                        icon.className = 'fas fa-chevron-up';
                        this.innerHTML = '<i class="fas fa-chevron-up"></i> Masquer les commandes';
                    }
                });
            });
        });
    </script>
</body>
</html> 