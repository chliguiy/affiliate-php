<?php
$host = "localhost";
$username = "root";
$password = "";

try {
    echo "<h2>Diagnostic de la base de données</h2>";

    // 1. Test de connexion au serveur MySQL
    echo "<h3>1. Connexion au serveur MySQL</h3>";
    $conn = new PDO("mysql:host=$host", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Connexion au serveur MySQL réussie<br>";

    // 2. Vérification de l'existence de la base de données
    echo "<h3>2. Vérification de la base de données</h3>";
    $stmt = $conn->query("SHOW DATABASES LIKE 'chic_affiliate'");
    $dbExists = $stmt->fetch();
    
    if ($dbExists) {
        echo "✅ La base de données 'chic_affiliate' existe<br>";
        
        // 3. Connexion à la base de données
        $conn->exec("USE chic_affiliate");
        echo "✅ Connexion à la base de données réussie<br>";
        
        // 4. Vérification des tables
        echo "<h3>3. Vérification des tables</h3>";
        $tables = ['admins', 'categories', 'products', 'orders', 'order_items'];
        
        foreach ($tables as $table) {
            $stmt = $conn->query("SHOW TABLES LIKE '$table'");
            if ($stmt->fetch()) {
                $count = $conn->query("SELECT COUNT(*) FROM $table")->fetchColumn();
                echo "✅ Table '$table' existe avec $count enregistrement(s)<br>";
                
                if ($table === 'admins') {
                    $admins = $conn->query("SELECT * FROM admins")->fetchAll();
                    echo "Liste des administrateurs :<br>";
                    foreach ($admins as $admin) {
                        echo "- {$admin['name']} ({$admin['email']})<br>";
                    }
                }
            } else {
                echo "❌ Table '$table' n'existe pas<br>";
            }
        }
    } else {
        echo "❌ La base de données 'chic_affiliate' n'existe pas<br>";
    }
    
    echo "<br><h3>Actions disponibles :</h3>";
    echo "- <a href='reset_database.php'>Réinitialiser complètement la base de données</a><br>";
    echo "- <a href='../index.php'>Retourner à la page de connexion</a><br>";
    
} catch(PDOException $e) {
    echo "<h3>❌ Erreur :</h3>";
    echo $e->getMessage() . "<br>";
    echo "Ligne : " . $e->getLine() . "<br>";
    echo "Fichier : " . $e->getFile() . "<br>";
}
?> 