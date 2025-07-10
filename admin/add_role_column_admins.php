<?php
$host = "localhost";
$username = "root";
$password = "";
$dbname = "chic_affiliate";

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $sql = "ALTER TABLE admins ADD COLUMN role VARCHAR(50) NOT NULL DEFAULT 'admin'";
    $conn->exec($sql);
    echo "Colonne 'role' ajoutée à la table admins.";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "La colonne 'role' existe déjà.";
    } else {
        echo "Erreur : " . $e->getMessage();
    }
} 