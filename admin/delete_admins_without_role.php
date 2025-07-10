<?php
$host = "localhost";
$username = "root";
$password = "";
$dbname = "chic_affiliate";

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $conn->prepare("DELETE FROM admins WHERE role IS NULL OR role = ''");
    $stmt->execute();
    echo "Comptes admin sans rôle supprimés avec succès.";
} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage();
} 