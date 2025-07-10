<?php
session_start();
if (!isset($_SESSION['confirmateur_id'])) {
    header('Location: ../login.php');
    exit();
}
$confirmateur_id = $_SESSION['confirmateur_id'];

require_once '../config/database.php';
$database = new Database();
$conn = $database->getConnection();

// Statuts autorisés pour le confirmateur
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

// Récupérer les clients assignés
$stmt = $conn->prepare("SELECT u.id, u.username, u.full_name, u.email, u.phone, u.city FROM confirmateur_clients cc JOIN users u ON cc.client_id = u.id WHERE cc.confirmateur_id = ? AND cc.status = 'active'");
$stmt->execute([$confirmateur_id]);
$clients = $stmt->fetchAll();

// Récupérer les commandes pour chaque client
$client_orders = [];
foreach ($clients as $client) {
    $stmt = $conn->prepare("
        SELECT 
            o.*, 
            COUNT(oi.id) as total_items,
            GROUP_CONCAT(CONCAT(p.name, ' (', oi.quantity, ')') SEPARATOR ', ') as products
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE o.user_id = ?
        GROUP BY o.id
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$client['id']]);
    $client_orders[$client['id']] = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Clients & Commandes - Confirmateur</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .client-card { border: none; border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.08); margin-bottom: 20px; }
        .client-card:hover { transform: translateY(-3px); }
        .order-status { padding: 3px 8px; border-radius: 10px; font-size: 0.85rem; }
        .order-confirmed { background-color: #28a745; color: white; }
        .order-delivered { background-color: #007bff; color: white; }
        .order-new, .order-unconfirmed, .order-processing, .order-shipped { background-color: #ffc107; color: black; }
        .order-cancelled, .order-returned { background-color: #dc3545; color: white; }
        .orders-table { font-size: 0.95rem; }
    </style>
</head>
<body>
<div class="container py-4">
    <h2 class="mb-4">Mes Clients & Commandes</h2>
    <?php foreach ($clients as $client): ?>
    <div class="card client-card">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0">
                <i class="fas fa-user me-2"></i>
                <?php echo htmlspecialchars($client['full_name'] ?: $client['username']); ?>
                <small class="text-light ms-2"><?php echo htmlspecialchars($client['email']); ?> | <?php echo htmlspecialchars($client['phone']); ?> | <?php echo htmlspecialchars($client['city']); ?></small>
            </h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped orders-table">
                    <thead class="table-dark">
                        <tr>
                            <th>N° Commande</th>
                            <th>Date</th>
                            <th>Produits</th>
                            <th>Montant</th>
                            <th>Commission</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($client_orders[$client['id']])): ?>
                        <tr><td colspan="7" class="text-center text-muted">Aucune commande pour ce client</td></tr>
                    <?php else: ?>
                        <?php foreach ($client_orders[$client['id']] as $order): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($order['order_number']); ?></strong></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                            <td><small><?php echo htmlspecialchars($order['products'] ?? 'N/A'); ?></small></td>
                            <td><strong><?php echo number_format($order['final_sale_price'] ?? 0, 2); ?> MAD</strong></td>
                            <td><?php echo number_format($order['affiliate_margin'] ?? 0, 2); ?> MAD</td>
                            <td><span class="order-status order-<?php echo strtolower($order['status']); ?>"><?php echo ucfirst($order['status']); ?></span></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="../admin/order_details.php?id=<?php echo $order['id']; ?>" class="btn btn-outline-primary" title="Voir détails"><i class="fas fa-eye"></i></a>
                                </div>
                                <div class="dropdown d-inline-block ms-1">
                                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fas fa-sync-alt"></i> Statut
                                    </button>
                                    <ul class="dropdown-menu">
                                        <?php foreach (
                                            $status_translations as $status => $label): ?>
                                            <li>
                                                <form method="post" action="../admin/change_order_status.php" style="margin:0;">
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
    <?php endforeach; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 