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

if (!$permissions->canViewReports()) {
    header('Location: dashboard.php?error=access_denied');
    exit;
}

// Récupération des commandes avec les détails
$stmt = $conn->query("
    SELECT 
        o.*,
        u.username as affiliate_username,
        u.email as affiliate_email,
        o.final_sale_price as total_amount,
        o.commission_amount as commission_amount
    FROM orders o
    LEFT JOIN users u ON o.affiliate_id = u.id
    ORDER BY o.created_at DESC
");
$orders = $stmt->fetchAll();

// Calcul des totaux (seulement pour les commandes livrées)
$total_sales = 0;
$total_commission = 0;
foreach ($orders as $order) {
    if ($order['status'] === 'delivered') {
        $total_sales += $order['total_amount'];
        $total_commission += $order['commission_amount'];
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Ventes - Admin</title>
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
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'includes/sidebar.php'; ?>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 p-4">
                <h2 class="mb-4">Gestion des Ventes</h2>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card stat-card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total des Ventes</h5>
                                <h2 class="mb-0"><?php echo number_format($total_sales ?? 0, 2); ?> MAD</h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card stat-card bg-info text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total des Commissions</h5>
                                <h2 class="mb-0"><?php echo number_format($total_commission ?? 0, 2); ?> MAD</h2>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Orders Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Liste des Commandes</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID Commande</th>
                                        <th>Client</th>
                                        <th>Affilié</th>
                                        <th>Montant</th>
                                        <th>Commission</th>
                                        <th>Statut</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($order['id']); ?></td>
                                        <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                        <td>
                                            <?php if ($order['affiliate_username']): ?>
                                                <?php echo htmlspecialchars($order['affiliate_username']); ?><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($order['affiliate_email']); ?></small>
                                            <?php else: ?>
                                                <span class="text-muted">Aucun affilié</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo number_format($order['final_sale_price'] ?? 0, 2); ?> MAD</td>
                                        <td><?php echo number_format($order['commission_amount'] ?? 0, 2); ?> MAD</td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo match($order['status']) {
                                                    'pending' => 'warning',
                                                    'confirmed' => 'success',
                                                    'cancelled' => 'danger',
                                                    default => 'secondary'
                                                };
                                            ?>">
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <button class="btn btn-sm btn-primary view-order" data-id="<?php echo $order['id']; ?>">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <?php if ($order['status'] === 'pending'): ?>
                                                <a href="update_order.php?id=<?php echo $order['id']; ?>&status=confirmed" 
                                                   class="btn btn-sm btn-success">
                                                    <i class="fas fa-check"></i>
                                                </a>
                                                <a href="update_order.php?id=<?php echo $order['id']; ?>&status=cancelled" 
                                                   class="btn btn-sm btn-danger">
                                                    <i class="fas fa-times"></i>
                                                </a>
                                                <?php endif; ?>
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

    <!-- Modal Bootstrap -->
    <div class="modal fade" id="orderDetailsModal" tabindex="-1" role="dialog" aria-labelledby="orderDetailsModalLabel" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="orderDetailsModalLabel">Détails de la commande</h5>
            <button type="button" class="close" data-bs-dismiss="modal" aria-label="Fermer">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body" id="order-details-content">
            <!-- Les détails de la commande seront chargés ici -->
          </div>
        </div>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    $(document).on('click', '.view-order', function() {
        var orderId = $(this).data('id');
        $.ajax({
            url: 'get_order_details.php',
            type: 'GET',
            data: { id: orderId },
            success: function(data) {
                $('#order-details-content').html(data);
                var modal = new bootstrap.Modal(document.getElementById('orderDetailsModal'));
                modal.show();
            },
            error: function() {
                $('#order-details-content').html('<div class="alert alert-danger">Erreur lors du chargement des détails.</div>');
                var modal = new bootstrap.Modal(document.getElementById('orderDetailsModal'));
                modal.show();
            }
        });
    });
    </script>
</body>
</html> 