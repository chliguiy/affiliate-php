<?php
require_once '../config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Démarrer la transaction
    $conn->beginTransaction();

    // Ajout des nouvelles colonnes
    $conn->exec("ALTER TABLE products 
                 ADD COLUMN seller_price DECIMAL(10,2) NOT NULL AFTER description,
                 ADD COLUMN reseller_price DECIMAL(10,2) NOT NULL AFTER seller_price");

    // Migration des données existantes
    $conn->exec("UPDATE products 
                 SET seller_price = price,
                     reseller_price = price + ((price * commission_rate) / 100)");

    // Suppression des anciennes colonnes
    $conn->exec("ALTER TABLE products 
                 DROP COLUMN price,
                 DROP COLUMN commission_rate");

    // Valider la transaction
    $conn->commit();
    
    echo "Mise à jour de la base de données réussie !";
} catch (PDOException $e) {
    // Annuler la transaction en cas d'erreur
    $conn->rollBack();
    echo "Erreur lors de la mise à jour : " . $e->getMessage();
} 