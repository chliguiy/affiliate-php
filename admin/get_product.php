<?php
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    if (!isset($_GET['id'])) {
        throw new Exception('ID du produit manquant');
    }
    
    $product_id = $_GET['id'];
    
    // Récupérer les données du produit
    $stmt = $conn->prepare("
        SELECT p.*, c.name as category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.id = ?
    ");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        throw new Exception('Produit non trouvé');
    }
    
    // Récupérer les couleurs du produit
    $stmt = $conn->prepare("
        SELECT c.id as color_id, c.name, c.color_code, pc.stock
        FROM product_colors pc
        JOIN colors c ON pc.color_id = c.id
        WHERE pc.product_id = ?
    ");
    $stmt->execute([$product_id]);
    $colors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer les tailles du produit
    $stmt = $conn->prepare("
        SELECT s.id as size_id, s.name, ps.stock as stock
        FROM product_sizes ps
        JOIN sizes s ON ps.size_id = s.id
        WHERE ps.product_id = ?
    ");
    $stmt->execute([$product_id]);
    $sizes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer les images du produit
    $stmt = $conn->prepare("
        SELECT id, image_url
        FROM product_images
        WHERE product_id = ?
        ORDER BY id
    ");
    $stmt->execute([$product_id]);
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Construire la réponse
    $response = [
        'id' => $product['id'],
        'name' => $product['name'],
        'description' => $product['description'],
        'seller_price' => $product['seller_price'],
        'reseller_price' => $product['reseller_price'],
        'category_id' => $product['category_id'],
        'status' => $product['status'],
        'colors' => $colors,
        'sizes' => $sizes,
        'images' => $images
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>