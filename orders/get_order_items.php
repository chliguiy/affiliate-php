<?php
session_start();
require_once '../config/database.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'message' => 'Non autorisé']));
}

// Vérifier si l'ID de la commande est fourni
if (!isset($_GET['order_id'])) {
    die(json_encode(['success' => false, 'message' => 'ID de commande manquant']));
}

$order_id = intval($_GET['order_id']);

try {
    // Connexion à la base de données
    $database = new Database();
    $conn = $database->getConnection();

    // Vérifier si la commande appartient à l'affilié
    $stmt = $conn->prepare("
        SELECT id FROM orders 
        WHERE id = ? AND affiliate_id = ?
    ");
    $stmt->execute([$order_id, $_SESSION['user_id']]);
    
    if (!$stmt->fetch()) {
        die(json_encode(['success' => false, 'message' => 'Commande non trouvée']));
    }

    // Récupérer les articles de la commande
    $stmt = $conn->prepare("
        SELECT 
            oi.product_id,
            oi.name,
            oi.sku,
            oi.quantity,
            oi.price,
            oi.commission_rate,
            oi.commission,
            oi.total
        FROM order_items oi
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$order_id]);
    $items = $stmt->fetchAll();

    die(json_encode([
        'success' => true,
        'items' => $items
    ]));

} catch (PDOException $e) {
    die(json_encode([
        'success' => false,
        'message' => 'Erreur lors de la récupération des articles: ' . $e->getMessage()
    ]));
} 