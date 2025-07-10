<?php
require_once 'config/database.php';

$db = new Database();
$conn = $db->getConnection();

// Mettre à jour les commandes avec created_at NULL ou trop ancienne
$sql = "UPDATE orders SET created_at = NOW() WHERE created_at IS NULL OR created_at < '2024-01-01'";
$count = $conn->exec($sql);

echo "Mises à jour effectuées : $count commandes corrigées.";
?> 