<?php
require_once 'config/database.php';

echo "=== Test de la logique de commission = marge affiliée ===\n\n";

// Récupérer une commande récente pour tester
$stmt = $conn->prepare("
    SELECT 
        id,
        order_number,
        total_amount,
        commission_amount,
        affiliate_margin,
        final_sale_price,
        delivery_fee,
        created_at
    FROM orders 
    WHERE affiliate_id IS NOT NULL 
    ORDER BY created_at DESC 
    LIMIT 1
");
$stmt->execute();
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if ($order) {
    echo "Commande trouvée:\n";
    echo "ID: " . $order['id'] . "\n";
    echo "Numéro: " . $order['order_number'] . "\n";
    echo "Total amount: " . number_format($order['total_amount'], 2) . " DH\n";
    echo "Commission amount: " . number_format($order['commission_amount'], 2) . " DH\n";
    echo "Affiliate margin: " . number_format($order['affiliate_margin'], 2) . " DH\n";
    echo "Final sale price: " . number_format($order['final_sale_price'], 2) . " DH\n";
    echo "Delivery fee: " . number_format($order['delivery_fee'], 2) . " DH\n";
    
    echo "\nVérification:\n";
    echo "Commission = Marge affiliée: " . ($order['commission_amount'] == $order['affiliate_margin'] ? "✅ OUI" : "❌ NON") . "\n";
    echo "Marge calculée: " . number_format($order['final_sale_price'] - $order['total_amount'] - $order['delivery_fee'], 2) . " DH\n";
    echo "Marge stockée: " . number_format($order['affiliate_margin'], 2) . " DH\n";
    
    if ($order['commission_amount'] == $order['affiliate_margin']) {
        echo "\n✅ SUCCÈS: La commission est bien égale à la marge affiliée!\n";
    } else {
        echo "\n❌ ERREUR: La commission n'est pas égale à la marge affiliée!\n";
    }
} else {
    echo "Aucune commande trouvée avec un affilié.\n";
}

echo "\n=== Fin du test ===\n";
?> 