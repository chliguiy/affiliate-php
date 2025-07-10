<?php
require_once 'config/database.php';
$database = new Database();
$conn = $database->getConnection();

$order_number = $_GET['order_number'] ?? '';
$order_id = $_GET['order_id'] ?? '';

if (!$order_number && !$order_id) {
    echo '<form method="get">';
    echo 'Numéro de commande : <input type="text" name="order_number">';
    echo ' ou ID : <input type="text" name="order_id">';
    echo ' <button type="submit">Vérifier</button>';
    echo '</form>';
    exit;
}

if ($order_number) {
    $stmt = $conn->prepare("SELECT id, order_number, status FROM orders WHERE order_number = ?");
    $stmt->execute([$order_number]);
} else {
    $stmt = $conn->prepare("SELECT id, order_number, status FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);
}
$order = $stmt->fetch();

if ($order) {
    echo '<h2>Commande trouvée</h2>';
    echo '<ul>';
    echo '<li>ID : ' . htmlspecialchars($order['id']) . '</li>';
    echo '<li>Numéro : ' . htmlspecialchars($order['order_number']) . '</li>';
    echo '<li>Status : <b>' . htmlspecialchars($order['status']) . '</b></li>';
    echo '</ul>';
} else {
    echo '<p style="color:red">Commande non trouvée.</p>';
} 