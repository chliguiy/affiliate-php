<?php
require_once '../config/database.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

$database = new Database();
$conn = $database->getConnection();

try {
    $conn->beginTransaction();

    if ($_POST['action'] === 'edit') {
        $product_id = (int)$_POST['id'];

        // Mise à jour des informations de base du produit
        $stmt = $conn->prepare("
            UPDATE products SET
                name = ?,
                description = ?,
                seller_price = ?,
                reseller_price = ?,
                category_id = ?,
                status = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");

        $stmt->execute([
            $_POST['name'],
            $_POST['description'],
            $_POST['seller_price'],
            $_POST['reseller_price'],
            $_POST['category_id'],
            $_POST['status'],
            $product_id
        ]);

        // Mise à jour des stocks des couleurs
        if (isset($_POST['color_stock']) && is_array($_POST['color_stock'])) {
            foreach ($_POST['color_stock'] as $color_id => $stock) {
                $stmt = $conn->prepare("
                    UPDATE product_colors 
                    SET stock = ? 
                    WHERE product_id = ? AND color_id = ?
                ");
                $stmt->execute([(int)$stock, $product_id, (int)$color_id]);
            }
        }

        // Mise à jour des stocks des tailles
        if (isset($_POST['size_stock']) && is_array($_POST['size_stock'])) {
            foreach ($_POST['size_stock'] as $size_id => $stock) {
                $stmt = $conn->prepare("
                    UPDATE product_sizes 
                    SET stock = ? 
                    WHERE product_id = ? AND size_id = ?
                ");
                $stmt->execute([(int)$stock, $product_id, (int)$size_id]);
            }
        }

        // Gestion des nouvelles images
        if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
            $upload_dir = '../uploads/products/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                $file_name = $_FILES['images']['name'][$key];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                $new_file_name = uniqid() . '.' . $file_ext;
                $file_path = $upload_dir . $new_file_name;

                if (move_uploaded_file($tmp_name, $file_path)) {
                    $stmt = $conn->prepare("INSERT INTO product_images (product_id, image_url) VALUES (?, ?)");
                    $stmt->execute([$product_id, 'uploads/products/' . $new_file_name]);
                }
            }
        }

        // Calculer le stock total du produit
        $stmt = $conn->prepare("SELECT SUM(stock) FROM product_colors WHERE product_id = ?");
        $stmt->execute([$product_id]);
        $stock_colors = (int)$stmt->fetchColumn();

        $stmt = $conn->prepare("SELECT SUM(stock) FROM product_sizes WHERE product_id = ?");
        $stmt->execute([$product_id]);
        $stock_sizes = (int)$stmt->fetchColumn();

        $total_stock = max($stock_colors, $stock_sizes);

        // Mettre à jour le stock global du produit
        $stmt = $conn->prepare("UPDATE products SET stock = ? WHERE id = ?");
        $stmt->execute([$total_stock, $product_id]);

        $conn->commit();
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Action non reconnue');
    }
} catch (Exception $e) {
    $conn->rollBack();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} 