<?php
require_once __DIR__ . '/../config/app.php';

/**
 * Fonctions utilitaires pour l'application
 */

/**
 * Vérifier si l'utilisateur est connecté
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Vérifier si l'utilisateur est un administrateur
 */
function isAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

/**
 * Vérifier si l'utilisateur est un affilié
 */
function isAffiliate() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'affiliate';
}

/**
 * Rediriger vers une page avec un message
 */
function redirectWithMessage($url, $message, $type = 'success') {
    $_SESSION[$type] = $message;
    header('Location: ' . $url);
    exit();
}

/**
 * Afficher un message flash
 */
function displayFlashMessage() {
    if (isset($_SESSION['success'])) {
        echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['success']) . '</div>';
        unset($_SESSION['success']);
    }
    
    if (isset($_SESSION['error'])) {
        echo '<div class="alert alert-danger">' . htmlspecialchars($_SESSION['error']) . '</div>';
        unset($_SESSION['error']);
    }
    
    if (isset($_SESSION['warning'])) {
        echo '<div class="alert alert-warning">' . htmlspecialchars($_SESSION['warning']) . '</div>';
        unset($_SESSION['warning']);
    }
    
    if (isset($_SESSION['info'])) {
        echo '<div class="alert alert-info">' . htmlspecialchars($_SESSION['info']) . '</div>';
        unset($_SESSION['info']);
    }
}

/**
 * Générer un numéro de commande unique
 */
function generateOrderNumber() {
    return 'ORD-' . date('Ymd') . '-' . rand(1000, 9999);
}

/**
 * Calculer les frais de livraison
 */
function calculateShippingCost($city) {
    return (strtolower($city) === 'casablanca') ? 
        AppConfig::SHIPPING_COST_CASABLANCA : 
        AppConfig::SHIPPING_COST_OTHER;
}

/**
 * Calculer la commission
 */
function calculateCommission($amount) {
    return $amount * AppConfig::COMMISSION_RATE;
}

/**
 * Valider et nettoyer les données d'entrée
 */
function validateAndSanitize($data, $type = 'string') {
    switch ($type) {
        case 'email':
            return AppConfig::validateEmail($data) ? $data : false;
        case 'rib':
            return AppConfig::validateRIB($data) ? $data : false;
        case 'int':
            return filter_var($data, FILTER_VALIDATE_INT);
        case 'float':
            return filter_var($data, FILTER_VALIDATE_FLOAT);
        case 'url':
            return filter_var($data, FILTER_VALIDATE_URL);
        default:
            return AppConfig::sanitizeInput($data);
    }
}

/**
 * Formater un montant
 */
function formatAmount($amount) {
    return number_format($amount, 2, ',', ' ') . ' DH';
}

/**
 * Formater une date
 */
function formatDate($date, $format = 'd/m/Y H:i') {
    return date($format, strtotime($date));
}

/**
 * Vérifier les permissions d'accès
 */
function checkAccess($requiredType = null) {
    if (!isLoggedIn()) {
        redirectWithMessage('login.php', 'Vous devez être connecté pour accéder à cette page.', 'error');
    }
    
    if ($requiredType && $_SESSION['user_type'] !== $requiredType) {
        redirectWithMessage('dashboard.php', 'Vous n\'avez pas les permissions nécessaires.', 'error');
    }
}

/**
 * Logger une action
 */
function logAction($action, $details = '') {
    $log = date('Y-m-d H:i:s') . ' - ' . $action;
    if ($details) {
        $log .= ' - ' . $details;
    }
    if (isset($_SESSION['user_id'])) {
        $log .= ' - User ID: ' . $_SESSION['user_id'];
    }
    $log .= PHP_EOL;
    
    file_put_contents(__DIR__ . '/../logs/app.log', $log, FILE_APPEND | LOCK_EX);
}
?> 