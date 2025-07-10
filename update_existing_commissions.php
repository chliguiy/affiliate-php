<?php
require_once 'config/database.php';
$database = new Database();
$conn = $database->getConnection();

echo "=== Mise à jour des commissions existantes ===\n\n";

try {
    // Compter les commandes à mettre à jour
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count
        FROM orders 
        WHERE affiliate_id IS NOT NULL 
        AND commission_amount != affiliate_margin
    ");
    $stmt->execute();
    $count = $stmt->fetchColumn();
    
    echo "Commandes à mettre à jour: $count\n\n";
    
    if ($count > 0) {
        // Mettre à jour les commissions
        $stmt = $conn->prepare("
            UPDATE orders 
            SET commission_amount = affiliate_margin 
            WHERE affiliate_id IS NOT NULL 
            AND commission_amount != affiliate_margin
        ");
        $stmt->execute();
        $updated = $stmt->rowCount();
        
        echo "✅ $updated commandes mises à jour avec succès!\n";
        
        // Vérifier le résultat
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count
            FROM orders 
            WHERE affiliate_id IS NOT NULL 
            AND commission_amount != affiliate_margin
        ");
        $stmt->execute();
        $remaining = $stmt->fetchColumn();
        
        echo "Commandes restantes à mettre à jour: $remaining\n";
        
        if ($remaining == 0) {
            echo "✅ Toutes les commandes ont été mises à jour!\n";
        }
    } else {
        echo "✅ Toutes les commandes sont déjà à jour!\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}

echo "\n=== Fin de la mise à jour ===\n";
?> 