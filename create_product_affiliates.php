<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "<h2>🔗 Création de la table product_affiliates</h2>";
    
    // Créer la table product_affiliates
    $sql = "CREATE TABLE IF NOT EXISTS product_affiliates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        affiliate_id INT NOT NULL,
        commission_rate DECIMAL(5,2) DEFAULT 10.00,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        FOREIGN KEY (affiliate_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_product_affiliate (product_id, affiliate_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->exec($sql);
    echo "✅ Table product_affiliates créée avec succès !<br>";
    
    // Ajouter la colonne affiliate_visibility à la table products si elle n'existe pas
    try {
        $conn->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS affiliate_visibility ENUM('all', 'specific') DEFAULT 'all' AFTER commission_rate");
        echo "✅ Colonne affiliate_visibility ajoutée à la table products<br>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "ℹ️ Colonne affiliate_visibility déjà existante<br>";
        } else {
            echo "❌ Erreur: " . $e->getMessage() . "<br>";
        }
    }
    
    echo "<br><h3>🎉 Configuration terminée !</h3>";
    echo "<p>Vous pouvez maintenant :</p>";
    echo "<ul>";
    echo "<li><a href='admin/products.php'>📦 Gérer les produits</a></li>";
    echo "<li><a href='products.php'>🛍️ Voir les produits</a></li>";
    echo "<li><a href='admin/users.php'>👥 Gérer les affiliés</a></li>";
    echo "</ul>";
    
    echo "<br><h4>📋 Fonctionnalités disponibles :</h4>";
    echo "<ul>";
    echo "<li>✅ <strong>Visibilité globale</strong> : Tous les affiliés voient le produit</li>";
    echo "<li>✅ <strong>Visibilité spécifique</strong> : Seuls les affiliés assignés voient le produit</li>";
    echo "<li>✅ <strong>Commission personnalisée</strong> : Taux de commission par affilié</li>";
    echo "</ul>";
    
} catch(PDOException $e) {
    echo "❌ Erreur : " . $e->getMessage();
}
?> 