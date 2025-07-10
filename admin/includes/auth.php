<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier si l'utilisateur est connecté en tant qu'admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

require_once '../config/database.php';

// Vérifier si l'administrateur existe toujours dans la base de données
$database = new Database();
$conn = $database->getConnection();

$stmt = $conn->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->execute([$_SESSION['admin_id']]);
$admin = $stmt->fetch();

if (!$admin) {
    session_destroy();
    header('Location: index.php');
    exit();
}

// Définir les variables globales pour l'admin
$admin_id = $admin['id'];
$admin_name = $admin['full_name'];
$admin_email = $admin['email'];
?> 