<?php
require_once __DIR__ . '/config/database.php';
$database = new Database();
$conn = $database->getConnection();

try {
    // Récupérer toutes les commandes
    $orders = $conn->query("SELECT id FROM orders")->fetchAll(PDO::FETCH_ASSOC);
    $updated = 0;
    foreach ($orders as $order) {
        $order_id = $order['id'];
        // Calculer la somme réelle des commissions des produits de la commande
        $stmt = $conn->prepare("
            SELECT SUM((p.price * p.commission_rate / 100) * oi.quantity) as total_commission
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$order_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $commission = $row['total_commission'] ?? 0;
        // Mettre à jour la commande
        $update = $conn->prepare("UPDATE orders SET commission_amount = ? WHERE id = ?");
        $update->execute([$commission, $order_id]);
        $updated++;
    }
    echo "Mise à jour terminée : $updated commandes corrigées.";
} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage();
}
?>
 