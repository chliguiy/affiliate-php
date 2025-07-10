<?php
$host = "localhost";
$username = "root";
$password = "";

try {
    // Connexion au serveur MySQL
    $conn = new PDO("mysql:host=$host", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Vérifier si la base de données existe
    $stmt = $conn->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = 'chic_affiliate'");
    $dbExists = $stmt->fetch();
    
    if (!$dbExists) {
        echo "La base de données 'chic_affiliate' n'existe pas. <br>";
        echo "<a href='create_database.php'>Cliquez ici pour créer la base de données</a>";
        exit;
    }
    
    echo "✅ La base de données 'chic_affiliate' existe.<br><br>";
    
    // Se connecter à la base de données
    $conn->exec("USE chic_affiliate");
    
    // Liste des tables à vérifier
    $tables = ['admins', 'categories', 'products', 'orders', 'order_items'];
    
    foreach ($tables as $table) {
        $stmt = $conn->query("SHOW TABLES LIKE '$table'");
        if ($stmt->fetch()) {
            // La table existe, vérifions si elle contient des données
            $count = $conn->query("SELECT COUNT(*) FROM $table")->fetchColumn();
            echo "✅ Table '$table' existe avec $count enregistrement(s)<br>";
        } else {
            echo "❌ Table '$table' n'existe pas<br>";
        }
    }
    
    // Vérifier spécifiquement le compte admin
    $stmt = $conn->query("SELECT COUNT(*) FROM admins WHERE email = 'admin@chicaffiliate.com'");
    $adminExists = $stmt->fetchColumn();
    
    if ($adminExists) {
        echo "<br>✅ Le compte administrateur existe<br>";
    } else {
        echo "<br>❌ Le compte administrateur n'existe pas<br>";
    }
    
    echo "<br>Actions disponibles :<br>";
    echo "- <a href='create_database.php'>Recréer la base de données et les tables</a><br>";
    echo "- <a href='../index.php'>Aller à la page de connexion</a>";
    
} catch(PDOException $e) {
    echo "Erreur : " . $e->getMessage();
    echo "<br><br><a href='create_database.php'>Cliquez ici pour créer/recréer la base de données</a>";
}
?> 