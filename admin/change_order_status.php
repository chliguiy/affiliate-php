<?php
require_once '../config/database.php';
$database = new Database();
$conn = $database->getConnection();

if (isset($_POST['order_id'], $_POST['new_status'])) {
    $order_id = (int)$_POST['order_id'];
    $new_status = $_POST['new_status'];
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $order_id]);
}
// Rediriger vers la page précédente
header('Location: ' . $_SERVER['HTTP_REFERER']);
exit; 