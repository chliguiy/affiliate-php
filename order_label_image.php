<?php
require_once 'config/database.php';
$order_id = (int)($_GET['order_id'] ?? 0);
if (!$order_id) die('Commande introuvable.');
$database = new Database();
$conn = $database->getConnection();
$stmt = $conn->prepare("SELECT o.*, u.username as affiliate_name FROM orders o LEFT JOIN users u ON o.affiliate_id = u.id WHERE o.id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch();
if (!$order) die('Commande introuvable.');
$stmt = $conn->prepare("SELECT oi.*, p.name as product_name FROM order_items oi LEFT JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll();
?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Étiquette Commande</title>
    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
    <style>
        body { background: #f8fafc; }
        .etiquette { background: #fff; padding: 20px; border-radius: 10px; width: 350px; margin: 40px auto; box-shadow: 0 4px 16px #0001; }
        .etiquette h2 { font-size: 1.2rem; margin-bottom: 10px; }
        .etiquette .section { margin-bottom: 10px; }
        .etiquette table { width: 100%; border-collapse: collapse; font-size: 0.95em; }
        .etiquette th, .etiquette td { border: 1px solid #ddd; padding: 4px 6px; }
        .etiquette th { background: #f5f5f5; }
        .download-btn { display: block; margin: 15px auto 0; background: #4CAF50; color: #fff; border: none; padding: 8px 18px; border-radius: 5px; font-size: 1em; cursor: pointer; }
    </style>
</head>
<body>
    <div class="etiquette" id="etiquette">
        <h2>Commande #<?= htmlspecialchars($order['order_number']) ?></h2>
        <div class="section">
            <strong>Client :</strong> <?= htmlspecialchars($order['customer_name']) ?><br>
            <strong>Adresse :</strong> <?= htmlspecialchars($order['customer_address']) ?>, <?= htmlspecialchars($order['customer_city']) ?><br>
            <strong>Téléphone :</strong> <?= htmlspecialchars($order['customer_phone']) ?><br>
            <?php if (!empty($order['notes'])): ?>
                <strong>Commentaire :</strong> <?= nl2br(htmlspecialchars($order['notes'])) ?><br>
            <?php endif; ?>
        </div>
        <div class="section">
            <strong>Date :</strong> <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?><br>
            <strong>Statut :</strong> <?= ucfirst($order['status']) ?><br>
            <?php if ($order['affiliate_name']): ?>
                <strong>Affilié :</strong> <?= htmlspecialchars($order['affiliate_name']) ?><br>
            <?php endif; ?>
        </div>
        <div class="section">
            <table>
                <thead>
                    <tr>
                        <th>Produit</th>
                        <th>Qté</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($order_items as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['product_name']) ?></td>
                        <td><?= $item['quantity'] ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <button class="download-btn" onclick="downloadEtiquette()">Télécharger en image</button>
    <script>
    function downloadEtiquette() {
        html2canvas(document.getElementById('etiquette')).then(function(canvas) {
            var link = document.createElement('a');
            link.download = 'etiquette_commande_<?= htmlspecialchars($order['order_number']) ?>.png';
            link.href = canvas.toDataURL();
            link.click();
        });
    }
    // Téléchargement auto si ouvert depuis le bouton imprimante
    window.onload = function() {
        setTimeout(downloadEtiquette, 500);
    };
    </script>
</body>
</html> 