<?php
require_once '../config/database.php';
header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID manquant']);
    exit;
}
$id = (int)$_GET['id'];
$database = new Database();
$conn = $database->getConnection();

// Récupérer l'image
$stmt = $conn->prepare("SELECT image_url FROM product_images WHERE id = ?");
$stmt->execute([$id]);
$image = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$image) {
    http_response_code(404);
    echo json_encode(['error' => 'Image introuvable']);
    exit;
}

// Supprimer le fichier physique
$file = '../' . $image['image_url'];
if (file_exists($file)) {
    unlink($file);
}

// Supprimer en base
$stmt = $conn->prepare("DELETE FROM product_images WHERE id = ?");
$stmt->execute([$id]);

echo json_encode(['success' => true]); 