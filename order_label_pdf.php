<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/database.php';

$order_id = (int)($_GET['order_id'] ?? 0);
if (!$order_id) die('Commande introuvable.');

$database = new Database();
$conn = $database->getConnection();

// Récupérer la commande
$stmt = $conn->prepare("SELECT o.*, u.username as affiliate_name FROM orders o LEFT JOIN users u ON o.affiliate_id = u.id WHERE o.id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch();
if (!$order) die('Commande introuvable.');

// Récupérer les produits
$stmt = $conn->prepare("SELECT oi.*, p.name as product_name FROM order_items oi LEFT JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll();

ob_start();
?>
<style>
body { font-family: Arial, sans-serif; font-size: 12px; }
.header { background: #4CAF50; color: #fff; padding: 10px; border-radius: 8px 8px 0 0; }
.section { margin-bottom: 10px; }
.table { width: 100%; border-collapse: collapse; margin-top: 10px; }
.table th, .table td { border: 1px solid #ddd; padding: 6px; text-align: left; }
.table th { background: #f5f5f5; }
.summary { margin-top: 10px; }
</style>
<div class="header">
    <h2>Étiquette Commande #<?php echo htmlspecialchars($order['order_number']); ?></h2>
</div>
<div class="section">
    <strong>Client :</strong> <?php echo htmlspecialchars($order['customer_name']); ?><br>
    <strong>Adresse :</strong> <?php echo htmlspecialchars($order['customer_address']); ?>, <?php echo htmlspecialchars($order['customer_city']); ?><br>
    <strong>Téléphone :</strong> <?php echo htmlspecialchars($order['customer_phone']); ?><br>
    <?php if (!empty($order['notes'])): ?>
        <strong>Commentaire :</strong> <?php echo nl2br(htmlspecialchars($order['notes'])); ?><br>
    <?php endif; ?>
</div>
<div class="section">
    <strong>Date :</strong> <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?><br>
    <strong>Statut :</strong> <?php echo ucfirst($order['status']); ?><br>
    <?php if ($order['affiliate_name']): ?>
        <strong>Affilié :</strong> <?php echo htmlspecialchars($order['affiliate_name']); ?><br>
    <?php endif; ?>
</div>
<div class="section">
    <table class="table">
        <thead>
            <tr>
                <th>Produit</th>
                <th>Quantité</th>
                <th>Prix unitaire</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($order_items as $item): ?>
            <tr>
                <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                <td><?php echo $item['quantity']; ?></td>
                <td><?php echo number_format($item['price'], 2); ?> DH</td>
                <td><?php echo number_format($item['price'] * $item['quantity'], 2); ?> DH</td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<div class="summary">
    <strong>Total payé par le client :</strong> <?php echo number_format($order['final_sale_price'], 2); ?> DH<br>
    <strong>Frais de livraison :</strong> <?php echo number_format($order['delivery_fee'], 2); ?> DH<br>
    <strong>Marge affilié :</strong> <?php echo number_format($order['affiliate_margin'], 2); ?> DH<br>
</div>
<?php
$html = ob_get_clean();
$mpdf = new \Mpdf\Mpdf(['format' => 'A6']);
$mpdf->WriteHTML($html);
$mpdf->Output('etiquette_commande_'.$order['order_number'].'.pdf', 'D');
exit; 