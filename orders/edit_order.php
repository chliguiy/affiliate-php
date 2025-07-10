<?php
session_start();
require_once '../config/database.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'message' => 'Non autorisé']));
}

// Vérifier si les paramètres nécessaires sont présents
if (!isset($_POST['order_id'])) {
    die(json_encode(['success' => false, 'message' => 'ID de commande manquant']));
}

$order_id = intval($_POST['order_id']);

// Champs modifiables
$editable_fields = [
    'customer_name' => 'string',
    'customer_email' => 'email',
    'customer_phone' => 'string',
    'customer_address' => 'string',
    'customer_city' => 'string',
    'postal_code' => 'string',
    'notes' => 'string',
    'shipping_method' => 'string',
    'payment_method' => 'string'
];

// Préparer les données à mettre à jour
$updates = [];
$params = [];
foreach ($editable_fields as $field => $type) {
    if (isset($_POST[$field]) && $_POST[$field] !== '') {
        // Validation basique
        if ($type === 'email' && !filter_var($_POST[$field], FILTER_VALIDATE_EMAIL)) {
            die(json_encode(['success' => false, 'message' => 'Email invalide']));
        }
        $updates[] = "`$field` = ?";
        $params[] = $_POST[$field];
    }
}

if (empty($updates)) {
    die(json_encode(['success' => false, 'message' => 'Aucun champ à mettre à jour']));
}

try {
    // Connexion à la base de données
    $database = new Database();
    $conn = $database->getConnection();

    // Vérifier si la commande appartient à l'affilié
    $stmt = $conn->prepare("SELECT affiliate_id FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();

    if (!$order || $order['affiliate_id'] != $_SESSION['user_id']) {
        die(json_encode(['success' => false, 'message' => 'Commande non trouvée']));
    }

    // Mettre à jour la commande
    $sql = "UPDATE orders SET " . implode(', ', $updates) . ", updated_at = CURRENT_TIMESTAMP WHERE id = ?";
    $params[] = $order_id;
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);

    // Recalcul automatique des totaux et commissions
    $stmt = $conn->prepare("SELECT SUM(total) as total_amount, SUM(commission) as total_commission FROM order_items WHERE order_id = ?");
    $stmt->execute([$order_id]);
    $totals = $stmt->fetch();
    $stmt = $conn->prepare("UPDATE orders SET total_amount = ?, affiliate_margin = ?, commission = ? WHERE id = ?");
    $stmt->execute([
        $totals['total_amount'] ?? 0,
        $totals['total_commission'] ?? 0,
        $totals['total_commission'] ?? 0,
        $order_id
    ]);

    // Récupérer les données mises à jour
    $stmt = $conn->prepare("
        SELECT 
            o.*,
            GROUP_CONCAT(p.name SEPARATOR ', ') as products
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE o.id = ?
        GROUP BY o.id
    ");
    $stmt->execute([$order_id]);
    $updated_order = $stmt->fetch();

    die(json_encode([
        'success' => true,
        'message' => 'Commande mise à jour avec succès',
        'order' => $updated_order
    ]));

} catch (PDOException $e) {
    die(json_encode([
        'success' => false,
        'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage()
    ]));
} 