<?php
$host = "localhost";
$username = "root";
$password = "";

try {
    echo "Test de connexion MySQL...\n";
    $pdo = new PDO("mysql:host=$host", $username, $password);
    echo "Connexion réussie !\n";
    
    // Test de création de base de données
    echo "Test de création de base de données...\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS chic_affiliate");
    echo "Base de données créée ou déjà existante.\n";
    
    // Test de sélection de la base de données
    echo "Test de sélection de la base de données...\n";
    $pdo->exec("USE chic_affiliate");
    echo "Base de données sélectionnée avec succès.\n";
    
} catch(PDOException $e) {
    die("Erreur : " . $e->getMessage() . "\n");
}
?> 