<?php
session_start();
require_once '../config/database.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'message' => 'Non autorisé']));
}

try {
    // Connexion à la base de données
    $database = new Database();
    $conn = $database->getConnection();

    // Récupérer tous les produits actifs
    $stmt = $conn->prepare("
        SELECT id, name, price, commission_rate, sku, quantity 
        FROM products 
        WHERE status = 'active'
        ORDER BY name ASC
    ");
    $stmt->execute();
    $products = $stmt->fetchAll();

    die(json_encode([
        'success' => true,
        'products' => $products
    ]));

} catch (PDOException $e) {
    die(json_encode([
        'success' => false,
        'message' => 'Erreur lors de la récupération des produits: ' . $e->getMessage()
    ]));
} 