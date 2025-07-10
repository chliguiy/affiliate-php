<?php
session_start();
require_once '../config/database.php';
require_once '../includes/system_integration.php';
require_once 'includes/auth.php';

// Vérification de l'authentification admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$database = new Database();
$conn = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['affiliate_id'])) {
    try {
        $affiliate_id = (int)$_POST['affiliate_id'];
        $admin_id = $_SESSION['admin_id'];
        
        // Utiliser le système d'intégration pour approuver l'affilié
        $result = approveAffiliate($affiliate_id, $admin_id);
        
        if ($result['success']) {
            $_SESSION['success_message'] = "Affilié approuvé avec succès !";
        } else {
            $_SESSION['error_message'] = "Erreur lors de l'approbation : " . $result['error'];
        }
        
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Erreur : " . $e->getMessage();
    }
    
    header('Location: affiliates.php');
    exit;
} else {
    // Rediriger si accès direct
    header('Location: affiliates.php');
    exit;
}
?> 