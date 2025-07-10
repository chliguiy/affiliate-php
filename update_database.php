<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Lecture et exécution du script de mise à jour de la table users
    $sql = file_get_contents('database/update_users_table.sql');
    $conn->exec($sql);

    // Lecture et exécution du script de création de la table order_stats
    $sql = file_get_contents('database/create_order_stats_table.sql');
    $conn->exec($sql);

    // Création d'une entrée par défaut dans order_stats pour chaque affilié existant
    $sql = "INSERT IGNORE INTO order_stats (affiliate_id)
            SELECT id FROM users 
            WHERE type = 'affiliate' 
            AND id NOT IN (SELECT affiliate_id FROM order_stats)";
    $conn->exec($sql);

    echo "La base de données a été mise à jour avec succès !";
} catch(PDOException $e) {
    echo "Erreur lors de la mise à jour de la base de données : " . $e->getMessage();
}
?> 