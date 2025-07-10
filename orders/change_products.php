<?php
session_start();
require_once '../config/database.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'message' => 'Non autorisé']));
}

// Vérifier si les paramètres nécessaires sont présents
if (!isset($_POST['order_id']) || !isset($_POST['products'])) {
    die(json_encode(['success' => false, 'message' => 'Paramètres manquants']));
}

$order_id = intval($_POST['order_id']);
$products = json_decode($_POST['products'], true);

if (!is_array($products)) {
    die(json_encode(['success' => false, 'message' => 'Format de produits invalide']));
}

try {
    // Connexion à la base de données
    $database = new Database();
    $conn = $database->getConnection();

    // Vérifier si la commande appartient à l'affilié
    $stmt = $conn->prepare("
        SELECT o.*, GROUP_CONCAT(oi.product_id) as current_products 
        FROM orders o 
        LEFT JOIN order_items oi ON o.id = oi.order_id 
        WHERE o.id = ? AND o.affiliate_id = ?
        GROUP BY o.id
    ");
    $stmt->execute([$order_id, $_SESSION['user_id']]);
    $order = $stmt->fetch();

    if (!$order) {
        die(json_encode(['success' => false, 'message' => 'Commande non trouvée']));
    }

    // Commencer une transaction
    $conn->beginTransaction();

    // Supprimer les anciens articles
    $stmt = $conn->prepare("DELETE FROM order_items WHERE order_id = ?");
    $stmt->execute([$order_id]);

    // Calculer les nouveaux totaux
    $total_amount = 0;
    $total_commission = 0;

    // Insérer les nouveaux articles
    $stmt = $conn->prepare("
        INSERT INTO order_items (
            order_id, product_id, name, sku, quantity, 
            price, commission_rate, commission, total
        ) VALUES (
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?
        )
    ");

    foreach ($products as $product) {
        // Récupérer les informations du produit
        $prod_stmt = $conn->prepare("
            SELECT name, sku, price, commission_rate 
            FROM products 
            WHERE id = ?
        ");
        $prod_stmt->execute([$product['product_id']]);
        $prod_info = $prod_stmt->fetch();

        if (!$prod_info) {
            $conn->rollBack();
            die(json_encode(['success' => false, 'message' => 'Produit non trouvé']));
        }

        $quantity = intval($product['quantity']);
        $price = floatval($prod_info['price']);
        $commission_rate = floatval($prod_info['commission_rate']);
        
        $total = $price * $quantity;
        $commission = ($total * $commission_rate) / 100;

        $stmt->execute([
            $order_id,
            $product['product_id'],
            $prod_info['name'],
            $prod_info['sku'],
            $quantity,
            $price,
            $commission_rate,
            $commission,
            $total
        ]);

        $total_amount += $total;
        $total_commission += $commission;
    }

    // Mettre à jour la commande
    $stmt = $conn->prepare("
        UPDATE orders 
        SET 
            total_products = ?,
            total_amount = ?,
            affiliate_margin = ?,
            commission = ?,
            status = 'changed',
            updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    $stmt->execute([$total_amount, $total_amount, $total_commission, $total_commission, $order_id]);

    // Valider la transaction
    $conn->commit();

    // Récupérer la commande mise à jour
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
        'message' => 'Produits modifiés avec succès',
        'order' => $updated_order
    ]));

} catch (PDOException $e) {
    if (isset($conn)) {
        $conn->rollBack();
    }
    die(json_encode([
        'success' => false,
        'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage()
    ]));
} 