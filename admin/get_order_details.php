<?php
require_once '../config/database.php';

if (isset($_GET['id'])) {
    $order_id = $_GET['id'];
} elseif (isset($_GET['order_id'])) {
    $order_id = $_GET['order_id'];
} else {
    echo '<div class="alert alert-danger">ID de commande manquant</div>';
    exit;
}

$database = new Database();
$conn = $database->getConnection();

// Récupérer les données de la commande principale
$stmt = $conn->prepare("
    SELECT o.*, u.username as affiliate_username, u.email as affiliate_email
    FROM orders o
    LEFT JOIN users u ON o.affiliate_id = u.id
    WHERE o.id = ?
");
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo '<div class="alert alert-danger">Commande non trouvée</div>';
    exit;
}

// Récupérer les articles de la commande
$stmt = $conn->prepare("
    SELECT oi.*, p.name as product_name_full, p.seller_price, p.reseller_price
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<div class="container-fluid">
  <div class="row mb-3">
    <div class="col-md-6">
      <h5 class="mb-2">Informations de la commande</h5>
      <ul class="list-group list-group-flush">
        <li class="list-group-item"><strong>Numéro :</strong> <?php echo htmlspecialchars($order['order_number']); ?></li>
        <li class="list-group-item"><strong>Date :</strong> <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></li>
        <li class="list-group-item"><strong>Statut :</strong> <span class="badge bg-secondary"><?php echo ucfirst($order['status']); ?></span></li>
        <li class="list-group-item"><strong>Client :</strong> <?php echo htmlspecialchars($order['customer_name']); ?></li>
        <li class="list-group-item"><strong>Email :</strong> <?php echo htmlspecialchars($order['customer_email']); ?></li>
        <li class="list-group-item"><strong>Téléphone :</strong> <?php echo htmlspecialchars($order['customer_phone']); ?></li>
        <li class="list-group-item"><strong>Adresse :</strong> <?php echo htmlspecialchars($order['customer_address']); ?>, <?php echo htmlspecialchars($order['customer_city']); ?></li>
        <?php if (!empty($order['notes'])): ?>
        <li class="list-group-item"><strong>Commentaire :</strong> <?php echo nl2br(htmlspecialchars($order['notes'])); ?></li>
        <?php endif; ?>
      </ul>
    </div>
    <div class="col-md-6">
      <h5 class="mb-2">Résumé financier</h5>
      <ul class="list-group list-group-flush">
        <li class="list-group-item"><strong>Prix payé par le client :</strong> <?php echo number_format($order['final_sale_price'], 2); ?> DH</li>
        <li class="list-group-item"><strong>Frais de livraison :</strong> <?php echo number_format($order['delivery_fee'], 2); ?> DH</li>
        <li class="list-group-item"><strong>Marge affilié :</strong> <?php echo number_format($order['affiliate_margin'], 2); ?> DH</li>
        <li class="list-group-item"><strong>Commission totale (marge affiliée) :</strong> <?php echo number_format($order['commission_amount'], 2); ?> DH</li>
      </ul>
      <h6 class="mt-3">Affilié</h6>
      <div class="mb-2">
        <?php if ($order['affiliate_username']): ?>
          <span class="badge bg-info">Affilié : <?php echo htmlspecialchars($order['affiliate_username']); ?></span><br>
          <small><?php echo htmlspecialchars($order['affiliate_email']); ?></small>
        <?php else: ?>
          <span class="text-muted">Aucun affilié</span>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <h5 class="mb-2">Produits commandés</h5>
  <div class="table-responsive">
    <table class="table table-bordered align-middle">
      <thead class="table-light">
        <tr>
          <th>Produit</th>
          <th>Quantité</th>
          <th>Marge affilié</th>
          <th>Total payé par le client</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($order_items as $item): ?>
        <tr>
          <td><?php echo htmlspecialchars($item['product_name_full']); ?></td>
          <td><?php echo $item['quantity']; ?></td>
                                  <td><?php echo number_format($order['commission_amount'], 2); ?> DH</td>
          <td><?php echo number_format($order['final_sale_price'], 2); ?> DH</td>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr>
          <th colspan="3" class="text-end">Total payé par le client</th>
          <th><?php echo number_format($order['final_sale_price'], 2); ?> DH</th>
        </tr>
      </tfoot>
    </table>
  </div>
</div> 