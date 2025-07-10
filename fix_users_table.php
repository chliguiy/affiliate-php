<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "<h2>🔧 Correction de la table users</h2>";
    
    // Vérifier la structure actuelle de la table users
    $stmt = $conn->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    
    echo "<h3>📋 Colonnes actuelles :</h3>";
    echo "<ul>";
    foreach ($columns as $column) {
        echo "<li>" . htmlspecialchars($column ?? '') . "</li>";
    }
    echo "</ul>";
    
    // Ajouter les colonnes manquantes
    $alterQueries = [
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS bank_name VARCHAR(100) DEFAULT NULL AFTER email",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS rib VARCHAR(24) DEFAULT NULL AFTER bank_name",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS phone VARCHAR(20) DEFAULT NULL AFTER rib",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS address TEXT DEFAULT NULL AFTER phone",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS city VARCHAR(100) DEFAULT NULL AFTER address",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS full_name VARCHAR(255) DEFAULT NULL AFTER id",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS type ENUM('affiliate', 'customer', 'vendor', 'admin') DEFAULT 'affiliate' AFTER full_name"
    ];
    
    echo "<h3>🔧 Ajout des colonnes manquantes :</h3>";
    foreach ($alterQueries as $query) {
        try {
            $conn->exec($query);
            echo "✅ Colonne ajoutée avec succès<br>";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                echo "ℹ️ Colonne déjà existante<br>";
            } else {
                echo "❌ Erreur: " . $e->getMessage() . "<br>";
            }
        }
    }
    
    // Mettre à jour les données existantes si nécessaire
    $updateQueries = [
        "UPDATE users SET full_name = CONCAT(first_name, ' ', last_name) WHERE full_name IS NULL AND first_name IS NOT NULL AND last_name IS NOT NULL",
        "UPDATE users SET type = 'affiliate' WHERE type IS NULL",
        "UPDATE users SET bank_name = 'CIH Bank' WHERE bank_name IS NULL AND type = 'affiliate'"
    ];
    
    echo "<h3>🔄 Mise à jour des données :</h3>";
    foreach ($updateQueries as $query) {
        try {
            $affected = $conn->exec($query);
            echo "✅ $affected enregistrements mis à jour<br>";
        } catch (PDOException $e) {
            echo "ℹ️ " . $e->getMessage() . "<br>";
        }
    }
    
    // Vérifier la structure finale
    $stmt = $conn->query("DESCRIBE users");
    $finalColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<br><h3>📋 Structure finale de la table users :</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Colonne</th><th>Type</th><th>Null</th><th>Clé</th><th>Défaut</th><th>Extra</th></tr>";
    
    foreach ($finalColumns as $column) {
        echo "<tr>";
        foreach ($column as $value) {
            echo "<td style='padding: 5px;'>" . htmlspecialchars($value ?? '') . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    
    // Vérifier les données
    $stmt = $conn->query("SELECT COUNT(*) as total, type, status FROM users GROUP BY type, status");
    $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<br><h3>📊 Statistiques des utilisateurs :</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Type</th><th>Status</th><th>Nombre</th></tr>";
    
    foreach ($stats as $stat) {
        echo "<tr>";
        echo "<td style='padding: 5px;'>" . htmlspecialchars($stat['type'] ?? '') . "</td>";
        echo "<td style='padding: 5px;'>" . htmlspecialchars($stat['status'] ?? '') . "</td>";
        echo "<td style='padding: 5px;'>" . ($stat['total'] ?? 0) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<br><h3>🎉 Table users corrigée avec succès !</h3>";
    echo "<p>Vous pouvez maintenant :</p>";
    echo "<ul>";
    echo "<li><a href='admin/users.php'>👥 Gérer les utilisateurs</a></li>";
    echo "<li><a href='register.php'>📝 Inscription</a></li>";
    echo "<li><a href='login.php'>🔐 Connexion</a></li>";
    echo "</ul>";
    
} catch(PDOException $e) {
    echo "❌ Erreur : " . $e->getMessage();
}
?> 