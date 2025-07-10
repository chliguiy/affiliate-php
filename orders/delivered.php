<?php
// Récupérer les commandes livrées
$stmt = $conn->prepare("
    SELECT 
        o.*,
        GROUP_CONCAT(p.name SEPARATOR ', ') as products
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    LEFT JOIN products p ON oi.product_id = p.id
    WHERE o.affiliate_id = ? 
    AND o.status = 'delivered'
    GROUP BY o.id
    ORDER BY o.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();
?>

<div class="orders-container">
    <!-- Statistiques des commandes livrées -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6 class="card-title">Total des Commandes Livrées</h6>
                    <h2 class="card-text"><?php echo count($orders); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6 class="card-title">Chiffre d'Affaires</h6>
                    <h2 class="card-text"><?php echo number_format(array_sum(array_column($orders, 'final_sale_price')), 2); ?> DH</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6 class="card-title">Commission Totale</h6>
                    <h2 class="card-text"><?php echo number_format(array_sum(array_column($orders, 'commission_amount')), 2); ?> DH</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h6 class="card-title">Taux de Livraison</h6>
                    <h2 class="card-text">100%</h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Tableau des commandes -->
    <div class="table-responsive">
        <table class="table table-hover" id="deliveredOrdersTable">
            <thead>
                <tr>
                    <th>N° Commande</th>
                    <th>Date de Livraison</th>
                    <th>Client</th>
                    <th>Téléphone</th>
                    <th>Ville</th>
                    <th>Produits</th>
                    <th>Total</th>
                    <th>Commission</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                <tr>
                    <td>#<?php echo $order['id']; ?></td>
                    <td><?php echo date('d/m/Y', strtotime($order['delivered_at'])); ?></td>
                    <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                    <td><?php echo htmlspecialchars($order['customer_phone']); ?></td>
                    <td><?php echo htmlspecialchars($order['city']); ?></td>
                    <td><?php echo htmlspecialchars($order['products']); ?></td>
                                            <td><?php echo number_format($order['final_sale_price'], 2); ?> DH</td>
                                            <td><?php echo number_format($order['commission_amount'], 2); ?> DH</td>
                    <td>
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-info" title="Voir les détails"
                                    onclick="viewOrder(<?php echo $order['id']; ?>)">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-warning" title="Imprimer la facture"
                                    onclick="printInvoice(<?php echo $order['id']; ?>)">
                                <i class="fas fa-print"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#deliveredOrdersTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/fr-FR.json'
        },
        order: [[1, 'desc']],
        pageLength: 25,
        dom: '<"row"<"col-md-6"l><"col-md-6"f>>rtip'
    });
});

function viewOrder(orderId) {
    window.location.href = `order_details.php?id=${orderId}`;
}

function printInvoice(orderId) {
    window.open(`invoice.php?id=${orderId}`, '_blank');
}
</script> 